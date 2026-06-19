<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create expense categories, expenses, and recurring bills.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE expense_categories (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, name VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL, color VARCHAR(7) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EXPENSE_CATEGORY_HOUSEHOLD_SLUG ON expense_categories (household_id, slug)');
        $this->addSql('CREATE TABLE expenses (id VARCHAR(36) NOT NULL, category_id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, description VARCHAR(120) NOT NULL, amount_cents INT NOT NULL, currency VARCHAR(3) NOT NULL, spent_on DATE NOT NULL, paid_by_member_id VARCHAR(36) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EXPENSES_HOUSEHOLD_SPENT_ON ON expenses (household_id, spent_on)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA12469DE2 ON expenses (category_id)');
        $this->addSql('CREATE TABLE recurring_bills (id VARCHAR(36) NOT NULL, category_id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, name VARCHAR(120) NOT NULL, amount_cents INT NOT NULL, currency VARCHAR(3) NOT NULL, due_day SMALLINT NOT NULL, paid_by_member_id VARCHAR(36) DEFAULT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_RECURRING_BILLS_HOUSEHOLD_DUE_DAY ON recurring_bills (household_id, due_day)');
        $this->addSql('CREATE INDEX IDX_55E69E7A12469DE2 ON recurring_bills (category_id)');
        $this->addSql('ALTER TABLE expenses ADD CONSTRAINT FK_2D3A8DA12469DE2 FOREIGN KEY (category_id) REFERENCES expense_categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE recurring_bills ADD CONSTRAINT FK_55E69E7A12469DE2 FOREIGN KEY (category_id) REFERENCES expense_categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expenses DROP CONSTRAINT FK_2D3A8DA12469DE2');
        $this->addSql('ALTER TABLE recurring_bills DROP CONSTRAINT FK_55E69E7A12469DE2');
        $this->addSql('DROP TABLE recurring_bills');
        $this->addSql('DROP TABLE expenses');
        $this->addSql('DROP TABLE expense_categories');
    }
}
