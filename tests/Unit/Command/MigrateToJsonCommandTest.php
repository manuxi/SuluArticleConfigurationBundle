<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Manuxi\SuluArticleConfigurationBundle\Command\MigrateToJsonCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateToJsonCommandTest extends TestCase
{
    private Connection $connection;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->tester = new CommandTester(new MigrateToJsonCommand($this->connection));
    }

    private function createLegacyTable(): void
    {
        $this->connection->executeStatement(
            'CREATE TABLE ar_article_configuration (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                article_id VARCHAR(36) NOT NULL,
                template_key VARCHAR(128) DEFAULT NULL,
                is_default BOOLEAN DEFAULT 0 NOT NULL,
                layout_style VARCHAR(32) DEFAULT \'default\' NOT NULL,
                enable_sidebar BOOLEAN DEFAULT 1 NOT NULL,
                sidebar_position VARCHAR(16) DEFAULT \'right\' NOT NULL,
                show_toc BOOLEAN DEFAULT 1 NOT NULL,
                show_reading_time BOOLEAN DEFAULT 1 NOT NULL,
                show_author_box BOOLEAN DEFAULT 1 NOT NULL,
                show_related BOOLEAN DEFAULT 1 NOT NULL,
                enable_share_buttons BOOLEAN DEFAULT 1 NOT NULL,
                enable_print BOOLEAN DEFAULT 1 NOT NULL,
                hide_publish_date BOOLEAN DEFAULT 0 NOT NULL,
                custom_css_class VARCHAR(128) DEFAULT NULL
            )'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchData(int $id): ?array
    {
        $json = $this->connection->fetchOne('SELECT data FROM ar_article_configuration WHERE id = ?', [$id]);

        return null === $json || false === $json ? null : \json_decode((string) $json, true, 512, \JSON_THROW_ON_ERROR);
    }

    public function testDoesNothingWithoutTable(): void
    {
        $this->assertSame(0, $this->tester->execute([]));
        $this->assertStringContainsString('does not exist', $this->tester->getDisplay());
    }

    public function testDoesNothingWithoutLegacyColumns(): void
    {
        $this->connection->executeStatement('CREATE TABLE ar_article_configuration (id INTEGER PRIMARY KEY, article_id VARCHAR(36), data CLOB DEFAULT NULL)');

        $this->assertSame(0, $this->tester->execute([]));
        $this->assertStringContainsString('No legacy columns', $this->tester->getDisplay());
    }

    public function testCopiesLegacyColumnsIntoJson(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement(
            "INSERT INTO ar_article_configuration (article_id, template_key, is_default, layout_style, show_toc, hide_publish_date, custom_css_class)
             VALUES ('a-1', 'blog', 1, 'wide', 0, 1, 'highlight')"
        );
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id) VALUES ('a-2')");

        $this->assertSame(0, $this->tester->execute([]));
        $this->assertStringContainsString('Migrated 2 configuration(s)', $this->tester->getDisplay());

        $first = $this->fetchData(1);
        $this->assertSame('wide', $first['layoutStyle']);
        $this->assertFalse($first['showToc']);
        $this->assertTrue($first['hidePublishDate']);
        $this->assertTrue($first['enableSidebar']);
        $this->assertSame('highlight', $first['customCssClass']);
        $this->assertCount(11, $first);

        $second = $this->fetchData(2);
        $this->assertNull($second['customCssClass']);
        $this->assertSame('right', $second['sidebarPosition']);
    }

    public function testIsIdempotentAndKeepsValuesAlreadyStoredInJson(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id, layout_style) VALUES ('a-1', 'wide')");

        $this->tester->execute([]);
        $data = $this->fetchData(1);
        $data['layoutStyle'] = 'changed';
        $this->connection->executeStatement('UPDATE ar_article_configuration SET data = ? WHERE id = 1', [\json_encode($data)]);

        $this->tester->execute([]);

        $this->assertStringContainsString('Migrated 0 configuration(s)', $this->tester->getDisplay());
        $this->assertSame('changed', $this->fetchData(1)['layoutStyle']);
    }

    public function testFillsMissingKeysOfAnAlreadyMigratedRowWithoutOverwriting(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement('ALTER TABLE ar_article_configuration ADD data CLOB DEFAULT NULL');
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id, layout_style, show_toc, data) VALUES ('a-1', 'wide', 0, '{\"layoutStyle\":\"narrow\"}')");

        $this->tester->execute([]);

        $data = $this->fetchData(1);
        $this->assertStringContainsString('Migrated 1 configuration(s)', $this->tester->getDisplay());
        $this->assertSame('narrow', $data['layoutStyle'], 'the stored value wins over the legacy column');
        $this->assertFalse($data['showToc'], 'missing keys are taken from the legacy columns');
    }

    public function testCopiesUnknownColumnsTypedAndWarns(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement('ALTER TABLE ar_article_configuration ADD custom_data CLOB DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE ar_article_configuration ADD is_featured BOOLEAN DEFAULT 0 NOT NULL');
        $this->connection->executeStatement('ALTER TABLE ar_article_configuration ADD cache_lifetime INTEGER DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE ar_article_configuration ADD header_bg_color VARCHAR(16) DEFAULT NULL');
        $this->connection->executeStatement(
            "INSERT INTO ar_article_configuration (article_id, custom_data, is_featured, cache_lifetime, header_bg_color)
             VALUES ('a-1', '{\"foo\":[1,2]}', 1, 3600, '#fff')"
        );
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id, custom_data) VALUES ('a-2', 'plain text')");
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id) VALUES ('a-3')");

        $this->assertSame(0, $this->tester->execute([]));

        $first = $this->fetchData(1);
        $this->assertSame(['foo' => [1, 2]], $first['customData']);
        $this->assertTrue($first['isFeatured']);
        $this->assertSame(3600, $first['cacheLifetime']);
        $this->assertSame('#fff', $first['headerBgColor']);

        $second = $this->fetchData(2);
        $this->assertSame('plain text', $second['customData']);
        $this->assertArrayNotHasKey('cacheLifetime', $second, 'NULL values are skipped');
        $this->assertArrayNotHasKey('customData', $this->fetchData(3));

        $display = (string) \preg_replace('/\s+/', ' ', $this->tester->getDisplay());
        $this->assertStringContainsString('Column "custom_data"', $display);
        $this->assertStringContainsString('2 row(s) have a value', $display);
        $this->assertStringContainsString('"customData"', $display);
    }

    public function testRepeatedRunAddsUnknownColumnsToRowsMigratedBefore(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id, layout_style) VALUES ('a-1', 'wide')");
        $this->tester->execute([]);
        $before = $this->fetchData(1);

        $this->connection->executeStatement('ALTER TABLE ar_article_configuration ADD custom_data CLOB DEFAULT NULL');
        $this->connection->executeStatement("UPDATE ar_article_configuration SET custom_data = '[\"x\"]' WHERE id = 1");
        $this->tester->execute([]);

        $after = $this->fetchData(1);
        $this->assertSame(['x'], $after['customData']);
        unset($after['customData']);
        $this->assertSame($before, $after);
    }

    public function testDryRunChangesNothing(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id) VALUES ('a-1')");

        $this->tester->execute(['--dry-run' => true]);

        $this->assertStringContainsString('Would add column "data"', $this->tester->getDisplay());
        $this->assertStringContainsString('Would migrate 1 configuration(s)', $this->tester->getDisplay());
        $this->assertNotContains('data', $this->columnNames());
    }

    public function testDropLegacyColumnsWithForceKeepsTheCoreColumns(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement('ALTER TABLE ar_article_configuration ADD custom_data CLOB DEFAULT NULL');
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id, template_key, is_default, layout_style) VALUES ('a-1', 'blog', 1, 'wide')");

        $this->assertSame(0, $this->tester->execute(['--drop-legacy-columns' => true, '--force' => true]));

        $this->assertEqualsCanonicalizing(['id', 'article_id', 'template_key', 'is_default', 'data'], $this->columnNames());
        $this->assertSame('wide', $this->fetchData(1)['layoutStyle']);
        $this->assertStringContainsString('Dropped 12 column(s)', $this->tester->getDisplay());
    }

    public function testDropWithoutForceAndWithoutInteractionKeepsTheColumns(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id) VALUES ('a-1')");

        $this->assertSame(0, $this->tester->execute(['--drop-legacy-columns' => true], ['interactive' => false]));

        $this->assertContains('layout_style', $this->columnNames());
        $this->assertStringContainsString('The columns are kept', $this->tester->getDisplay());
    }

    public function testDropCanBeConfirmedInteractively(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id) VALUES ('a-1')");

        $this->tester->setInputs(['yes']);
        $this->tester->execute(['--drop-legacy-columns' => true]);

        $this->assertNotContains('layout_style', $this->columnNames());
    }

    public function testDryRunWithDropOnlyLists(): void
    {
        $this->createLegacyTable();

        $this->tester->execute(['--drop-legacy-columns' => true, '--force' => true, '--dry-run' => true]);

        $this->assertContains('layout_style', $this->columnNames());
        $this->assertStringContainsString('Would drop these columns', $this->tester->getDisplay());
    }

    public function testForceWithoutDropIsRejected(): void
    {
        $this->createLegacyTable();

        $this->assertSame(Command::INVALID, $this->tester->execute(['--force' => true]));
        $this->assertContains('layout_style', $this->columnNames());
    }

    public function testSecondRunAfterDropHasNothingToDo(): void
    {
        $this->createLegacyTable();
        $this->tester->execute(['--drop-legacy-columns' => true, '--force' => true]);

        $this->assertSame(0, $this->tester->execute([]));
        $this->assertStringContainsString('No legacy columns', $this->tester->getDisplay());
    }

    /**
     * @return list<string>
     */
    private function columnNames(): array
    {
        return \array_keys(\array_change_key_case($this->connection->createSchemaManager()->listTableColumns('ar_article_configuration')));
    }
}
