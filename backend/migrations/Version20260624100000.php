<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add transaction review metadata for imported finance data.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE expenses ADD review_status VARCHAR(16) NOT NULL DEFAULT 'needs_review'");
        $this->addSql('ALTER TABLE expenses ADD review_reason VARCHAR(160) DEFAULT NULL');
        $this->addSql("ALTER TABLE income_entries ADD income_kind VARCHAR(16) NOT NULL DEFAULT 'other'");
        $this->addSql("ALTER TABLE income_entries ADD review_status VARCHAR(16) NOT NULL DEFAULT 'needs_review'");
        $this->addSql('ALTER TABLE income_entries ADD review_reason VARCHAR(160) DEFAULT NULL');
        $this->addSql("UPDATE expenses SET review_reason = 'Imported bank transaction needs category check' WHERE deleted_at IS NULL");
        $this->addSql("UPDATE income_entries SET review_reason = 'Imported bank income needs type check' WHERE deleted_at IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE income_entries DROP review_reason');
        $this->addSql('ALTER TABLE income_entries DROP review_status');
        $this->addSql('ALTER TABLE income_entries DROP income_kind');
        $this->addSql('ALTER TABLE expenses DROP review_reason');
        $this->addSql('ALTER TABLE expenses DROP review_status');
    }
}
