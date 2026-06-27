<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add household reminders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reminders (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, title VARCHAR(160) NOT NULL, note TEXT DEFAULT NULL, due_at DATE NOT NULL, recurrence_type VARCHAR(16) NOT NULL, related_type VARCHAR(40) DEFAULT NULL, related_id VARCHAR(36) DEFAULT NULL, status VARCHAR(16) NOT NULL, priority VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, skipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_REMINDERS_HOUSEHOLD_STATUS_DUE ON reminders (household_id, status, due_at)');
        $this->addSql('CREATE INDEX IDX_REMINDERS_HOUSEHOLD_DELETED ON reminders (household_id, deleted_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reminders');
    }
}
