<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add saved finance review rules for bulk transaction cleanup.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE finance_review_rules (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, target_type VARCHAR(16) NOT NULL, match_text VARCHAR(80) NOT NULL, category_id VARCHAR(36) DEFAULT NULL, income_kind VARCHAR(16) DEFAULT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_applied_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FINANCE_REVIEW_RULES_HOUSEHOLD_TARGET ON finance_review_rules (household_id, target_type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE finance_review_rules');
    }
}
