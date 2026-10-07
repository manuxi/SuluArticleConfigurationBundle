<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Manuxi\SuluArticleConfigurationBundle\Command\MigrateToJsonCommand;
use PHPUnit\Framework\TestCase;
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

    public function testIsIdempotent(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id, layout_style) VALUES ('a-1', 'wide')");

        $this->tester->execute([]);
        $this->connection->executeStatement("UPDATE ar_article_configuration SET data = '{\"layoutStyle\":\"changed\"}' WHERE id = 1");
        $this->tester->execute([]);

        $this->assertStringContainsString('Migrated 0 configuration(s)', $this->tester->getDisplay());
        $this->assertSame('changed', $this->fetchData(1)['layoutStyle']);
    }

    public function testDryRunChangesNothing(): void
    {
        $this->createLegacyTable();
        $this->connection->executeStatement("INSERT INTO ar_article_configuration (article_id) VALUES ('a-1')");

        $this->tester->execute(['--dry-run' => true]);

        $this->assertStringContainsString('Would migrate 1 configuration(s)', $this->tester->getDisplay());
        $columns = \array_keys($this->connection->createSchemaManager()->listTableColumns('ar_article_configuration'));
        $this->assertNotContains('data', $columns);
    }
}
