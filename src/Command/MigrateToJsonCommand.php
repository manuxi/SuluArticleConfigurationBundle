<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Copies the values of the fixed columns of bundle versions before 3.0 into the "data" JSON column.
 * Must run before doctrine:schema:update drops the old columns.
 */
#[AsCommand(
    name: 'sulu:article-configuration:migrate-to-json',
    description: 'Copies the legacy configuration columns into the JSON data column'
)]
class MigrateToJsonCommand extends Command
{
    public const TABLE = 'ar_article_configuration';

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
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report what would be migrated');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist([self::TABLE])) {
            $io->success('Table "' . self::TABLE . '" does not exist, nothing to migrate.');

            return Command::SUCCESS;
        }

        $columns = \array_map('strtolower', \array_keys($schemaManager->listTableColumns(self::TABLE)));
        $legacyColumns = \array_values(\array_filter(
            \array_keys(self::LEGACY_COLUMNS),
            static fn (string $column): bool => \in_array($column, $columns, true)
        ));

        if ([] === $legacyColumns) {
            $io->success('No legacy columns found, nothing to migrate.');

            return Command::SUCCESS;
        }

        $platform = $this->connection->getDatabasePlatform();
        $table = $platform->quoteIdentifier(self::TABLE);
        $dataColumn = $platform->quoteIdentifier('data');

        if (!\in_array('data', $columns, true)) {
            $io->text('Adding column "data".');
            if (!$dryRun) {
                $this->connection->executeStatement(\sprintf(
                    'ALTER TABLE %s ADD %s %s DEFAULT NULL',
                    $table,
                    $dataColumn,
                    $platform->getJsonTypeDeclarationSQL(['notnull' => false])
                ));
            }
        }

        $select = \sprintf(
            'SELECT id, %s FROM %s%s',
            \implode(', ', \array_map([$platform, 'quoteIdentifier'], $legacyColumns)),
            $table,
            \in_array('data', $columns, true) ? \sprintf(' WHERE %s IS NULL', $dataColumn) : ''
        );

        $migrated = 0;
        foreach ($this->connection->fetchAllAssociative($select) as $row) {
            $data = [];
            foreach ($legacyColumns as $column) {
                [$name, $type] = self::LEGACY_COLUMNS[$column];
                $value = $row[$column];
                $data[$name] = 'bool' === $type
                    ? \filter_var($value, \FILTER_VALIDATE_BOOLEAN)
                    : (null === $value ? null : (string) $value);
            }

            if (!$dryRun) {
                $this->connection->executeStatement(
                    \sprintf('UPDATE %s SET %s = :data WHERE id = :id', $table, $dataColumn),
                    ['data' => \json_encode($data, \JSON_THROW_ON_ERROR), 'id' => $row['id']]
                );
            }

            ++$migrated;
        }

        $io->success(\sprintf(
            '%s %d configuration(s). Now run "doctrine:schema:update --force" to drop the legacy columns.',
            $dryRun ? 'Would migrate' : 'Migrated',
            $migrated
        ));

        return Command::SUCCESS;
    }
}
