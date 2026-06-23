<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add income, budgets, and recurring bill payments for monthly expense control.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE income_sources (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, member_id VARCHAR(36) DEFAULT NULL, name VARCHAR(120) NOT NULL, amount_cents INT NOT NULL, currency VARCHAR(3) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_INCOME_SOURCES_HOUSEHOLD_ACTIVE ON income_sources (household_id, active)');
        $this->addSql('CREATE TABLE income_entries (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, source_id VARCHAR(36) DEFAULT NULL, member_id VARCHAR(36) DEFAULT NULL, description VARCHAR(120) NOT NULL, amount_cents INT NOT NULL, currency VARCHAR(3) NOT NULL, received_on DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_INCOME_ENTRIES_HOUSEHOLD_RECEIVED_ON ON income_entries (household_id, received_on)');
        $this->addSql('CREATE TABLE expense_budgets (id VARCHAR(36) NOT NULL, category_id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, budget_month VARCHAR(7) NOT NULL, amount_cents INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EXPENSE_BUDGET_MONTH_CATEGORY ON expense_budgets (household_id, category_id, budget_month)');
        $this->addSql('CREATE INDEX IDX_B1B1DE7212469DE2 ON expense_budgets (category_id)');
        $this->addSql('ALTER TABLE expense_budgets ADD CONSTRAINT FK_B1B1DE7212469DE2 FOREIGN KEY (category_id) REFERENCES expense_categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE recurring_bill_payments (id VARCHAR(36) NOT NULL, recurring_bill_id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, payment_month VARCHAR(7) NOT NULL, status VARCHAR(16) NOT NULL, paid_on DATE DEFAULT NULL, amount_override_cents INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_RECURRING_BILL_PAYMENT_MONTH ON recurring_bill_payments (recurring_bill_id, payment_month)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_budgets DROP CONSTRAINT FK_B1B1DE7212469DE2');
        $this->addSql('DROP TABLE recurring_bill_payments');
        $this->addSql('DROP TABLE expense_budgets');
        $this->addSql('DROP TABLE income_entries');
        $this->addSql('DROP TABLE income_sources');
    }
}
