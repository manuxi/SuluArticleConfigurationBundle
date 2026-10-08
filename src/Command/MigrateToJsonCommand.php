<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\SmallIntType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Copies the values of the columns of bundle versions before 3.0 into the "data" JSON column and can drop the old
 * columns afterwards. Values already stored in "data" always win, so the command can be run repeatedly.
 */
#[AsCommand(
    name: 'sulu:article-configuration:migrate-to-json',
    description: 'Copies the legacy configuration columns into the JSON data column'
)]
class MigrateToJsonCommand extends Command
{
    public const TABLE = 'ar_article_configuration';

    private const CORE_COLUMNS = ['id', 'article_id', 'template_key', 'is_default', 'data'];

    /**
     * Legacy column => [field name, type].
     */
    private const LEGACY_COLUMNS = [
        'layout_style' => ['layoutStyle', 'string'],
        'enable_sidebar' => ['enableSidebar', 'bool'],
        'sidebar_position' => ['sidebarPosition', 'string'],
        'show_toc' => ['showToc', 'bool'],
        'show_reading_time' => ['showReadingTime', 'bool'],
        'show_author_box' => ['showAuthorBox', 'bool'],
        'show_related' => ['showRelated', 'bool'],
        'enable_share_buttons' => ['enableShareButtons', 'bool'],
        'enable_print' => ['enablePrint', 'bool'],
        'hide_publish_date' => ['hidePublishDate', 'bool'],
        'custom_css_class' => ['customCssClass', 'string'],
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report what would be done')
            ->addOption('drop-legacy-columns', null, InputOption::VALUE_NONE, 'Drop the old columns after copying their values')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Drop without asking, only together with --drop-legacy-columns');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $drop = (bool) $input->getOption('drop-legacy-columns');
        $force = (bool) $input->getOption('force');

        if ($force && !$drop) {
            $io->error('--force is only valid together with --drop-legacy-columns.');

            return Command::INVALID;
        }

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist([self::TABLE])) {
            $io->success('Table "' . self::TABLE . '" does not exist, nothing to migrate.');

            return Command::SUCCESS;
        }

        /** @var array<string, Column> $columns */
        $columns = \array_change_key_case($schemaManager->listTableColumns(self::TABLE), \CASE_LOWER);

        $legacyColumns = \array_values(\array_filter(
            \array_keys(self::LEGACY_COLUMNS),
            static fn (string $name): bool => isset($columns[$name])
        ));
        $extraColumns = \array_values(\array_filter(
            \array_keys($columns),
            static fn (string $name): bool => !\in_array($name, self::CORE_COLUMNS, true) && !isset(self::LEGACY_COLUMNS[$name])
        ));

        if ([] === $legacyColumns && [] === $extraColumns) {
            $io->success('No legacy columns found, nothing to migrate.');

            return Command::SUCCESS;
        }

        $platform = $this->connection->getDatabasePlatform();
        $table = $platform->quoteIdentifier(self::TABLE);
        $dataColumn = $platform->quoteIdentifier('data');
        $hasDataColumn = isset($columns['data']);

        if (!$hasDataColumn) {
            $io->text(($dryRun ? 'Would add' : 'Adding') . ' column "data".');
            if (!$dryRun) {
                $this->connection->executeStatement(\sprintf(
                    'ALTER TABLE %s ADD %s %s DEFAULT NULL',
                    $table,
                    $dataColumn,
                    $platform->getJsonTypeDeclarationSQL(['notnull' => false])
                ));
            }
        }

        $selected = \array_merge($legacyColumns, $extraColumns);
        if ($hasDataColumn) {
            \array_unshift($selected, 'data');
        }
        $rows = $this->connection->fetchAllAssociative(\sprintf(
            'SELECT id, %s FROM %s',
            \implode(', ', \array_map([$platform, 'quoteIdentifier'], $selected)),
            $table
        ));

        $extraCounts = \array_fill_keys($extraColumns, 0);
        $migrated = 0;
        foreach ($rows as $row) {
            $values = [];
            foreach ($legacyColumns as $column) {
                [$name, $type] = self::LEGACY_COLUMNS[$column];
                $value = $row[$column];
                $values[$name] = 'bool' === $type
                    ? \filter_var($value, \FILTER_VALIDATE_BOOLEAN)
                    : (null === $value ? null : (string) $value);
            }

            foreach ($extraColumns as $column) {
                if (null === $row[$column]) {
                    continue;
                }

                ++$extraCounts[$column];
                $values[self::toCamelCase($column)] = $this->convertExtraValue($row[$column], $columns[$column]);
            }

            $existing = $hasDataColumn ? $this->decodeJson($row['data'] ?? null) : [];
            $merged = \array_merge($values, $existing);
            if ($this->isSame($merged, $existing)) {
                continue;
            }

            if (!$dryRun) {
                $this->connection->executeStatement(
                    \sprintf('UPDATE %s SET %s = :data WHERE id = :id', $table, $dataColumn),
                    ['data' => \json_encode($merged, \JSON_THROW_ON_ERROR), 'id' => $row['id']]
                );
            }

            ++$migrated;
        }

        foreach ($extraColumns as $column) {
            $io->warning(\sprintf(
                'Column "%s" is not used by this bundle any more. %d row(s) have a value, it is copied to the key "%s" of "data". '
                . 'The key stays there until the article is saved again, unless you define a field of that name in your XML form.',
                $column,
                $extraCounts[$column],
                self::toCamelCase($column)
            ));
        }

        $io->success(\sprintf('%s %d configuration(s).', $dryRun ? 'Would migrate' : 'Migrated', $migrated));

        if (!$drop) {
            $io->note('Run with --drop-legacy-columns to drop the old columns, or use doctrine:schema:update --force.');

            return Command::SUCCESS;
        }

        return $this->dropColumns($io, \array_merge($legacyColumns, $extraColumns), $dryRun, $force);
    }

    /**
     * @param list<string> $columns
     */
    private function dropColumns(SymfonyStyle $io, array $columns, bool $dryRun, bool $force): int
    {
        $io->text('Legacy columns: ' . \implode(', ', $columns));

        if ($dryRun) {
            $io->text('Would drop these columns.');

            return Command::SUCCESS;
        }

        if (!$force && !$io->confirm(\sprintf('Drop %d column(s) of "%s"? This cannot be undone.', \count($columns), self::TABLE), false)) {
            $io->text('The columns are kept.');

            return Command::SUCCESS;
        }

        $platform = $this->connection->getDatabasePlatform();
        $dropped = [];
        foreach ($columns as $column) {
            try {
                $this->connection->executeStatement(\sprintf(
                    'ALTER TABLE %s DROP COLUMN %s',
                    $platform->quoteIdentifier(self::TABLE),
                    $platform->quoteIdentifier($column)
                ));
            } catch (\Throwable $exception) {
                $io->error(\sprintf(
                    'Dropping "%s" failed: %s%s',
                    $column,
                    $exception->getMessage(),
                    [] === $dropped ? '' : ' Already dropped: ' . \implode(', ', $dropped) . '.'
                ));

                return Command::FAILURE;
            }

            $dropped[] = $column;
        }

        $io->success(\sprintf('Dropped %d column(s).', \count($dropped)));

        return Command::SUCCESS;
    }

    private function convertExtraValue(mixed $value, Column $column): mixed
    {
        $type = $column->getType();

        if ($type instanceof BooleanType) {
            return \filter_var($value, \FILTER_VALIDATE_BOOLEAN);
        }

        if ($type instanceof IntegerType || $type instanceof SmallIntType || $type instanceof BigIntType) {
            return (int) $value;
        }

        if ($type instanceof FloatType || $type instanceof DecimalType) {
            return (float) $value;
        }

        if (!\is_string($value)) {
            return $value;
        }

        if ($type instanceof JsonType || \preg_match('/^\s*[\[{]/', $value)) {
            $decoded = \json_decode($value, true);
            if (\JSON_ERROR_NONE === \json_last_error() && (\is_array($decoded) || $type instanceof JsonType)) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $json): array
    {
        if (!\is_string($json) || '' === $json) {
            return [];
        }

        $decoded = \json_decode($json, true);

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function isSame(array $left, array $right): bool
    {
        \ksort($left);
        \ksort($right);

        return $left === $right;
    }

    private static function toCamelCase(string $column): string
    {
        return \lcfirst(\str_replace(' ', '', \ucwords(\str_replace('_', ' ', $column))));
    }
}
