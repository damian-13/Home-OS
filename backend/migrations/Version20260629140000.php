<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit logs for sensitive household data changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_logs (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, actor_user_id VARCHAR(36) NOT NULL, entity_type VARCHAR(80) NOT NULL, entity_id VARCHAR(80) NOT NULL, action VARCHAR(24) NOT NULL, changed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, summary VARCHAR(255) NOT NULL, metadata JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AUDIT_LOGS_HOUSEHOLD_CHANGED ON audit_logs (household_id, changed_at)');
        $this->addSql('CREATE INDEX IDX_AUDIT_LOGS_ENTITY ON audit_logs (entity_type, entity_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_logs');
    }
}
