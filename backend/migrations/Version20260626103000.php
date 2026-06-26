<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add undo batches for finance review rules.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE finance_review_batches (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, rule_id VARCHAR(36) NOT NULL, target_type VARCHAR(16) NOT NULL, match_text VARCHAR(80) NOT NULL, applied_count INT NOT NULL, items JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, undone_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FINANCE_REVIEW_BATCHES_HOUSEHOLD_CREATED ON finance_review_batches (household_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE finance_review_batches');
    }
}
