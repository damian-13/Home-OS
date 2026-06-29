<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add generic household documents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE documents (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, title VARCHAR(180) NOT NULL, document_type VARCHAR(24) NOT NULL, owner_member_id VARCHAR(36) DEFAULT NULL, issued_at DATE DEFAULT NULL, expires_at DATE DEFAULT NULL, tags VARCHAR(255) DEFAULT NULL, note TEXT DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, stored_name VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(120) DEFAULT NULL, file_size INTEGER DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DOCUMENTS_HOUSEHOLD_TYPE ON documents (household_id, document_type)');
        $this->addSql('CREATE INDEX IDX_DOCUMENTS_HOUSEHOLD_EXPIRES ON documents (household_id, expires_at)');
        $this->addSql('CREATE INDEX IDX_DOCUMENTS_HOUSEHOLD_DELETED ON documents (household_id, deleted_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE documents');
    }
}
