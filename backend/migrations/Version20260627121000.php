<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add home maintenance tasks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE home_maintenance_tasks (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, title VARCHAR(160) NOT NULL, area VARCHAR(80) NOT NULL, next_due_at DATE NOT NULL, recurrence_type VARCHAR(16) NOT NULL, assigned_member_id VARCHAR(36) DEFAULT NULL, priority VARCHAR(16) NOT NULL, notes TEXT DEFAULT NULL, status VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_HOME_MAINTENANCE_HOUSEHOLD_STATUS_DUE ON home_maintenance_tasks (household_id, status, next_due_at)');
        $this->addSql('CREATE INDEX IDX_HOME_MAINTENANCE_HOUSEHOLD_DELETED ON home_maintenance_tasks (household_id, deleted_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE home_maintenance_tasks');
    }
}
