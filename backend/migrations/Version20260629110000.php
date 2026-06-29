<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store finance import fingerprints for duplicate detection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expenses ADD import_source VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE expenses ADD import_fingerprint VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_EXPENSES_IMPORT_FINGERPRINT ON expenses (household_id, import_source, import_fingerprint)');
        $this->addSql('ALTER TABLE income_entries ADD import_source VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE income_entries ADD import_fingerprint VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_INCOME_ENTRIES_IMPORT_FINGERPRINT ON income_entries (household_id, import_source, import_fingerprint)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_EXPENSES_IMPORT_FINGERPRINT');
        $this->addSql('ALTER TABLE expenses DROP import_source');
        $this->addSql('ALTER TABLE expenses DROP import_fingerprint');
        $this->addSql('DROP INDEX IDX_INCOME_ENTRIES_IMPORT_FINGERPRINT');
        $this->addSql('ALTER TABLE income_entries DROP import_source');
        $this->addSql('ALTER TABLE income_entries DROP import_fingerprint');
    }
}
