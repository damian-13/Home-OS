<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add health documents for uploaded lab result files';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE health_documents (id UUID NOT NULL, household_id UUID NOT NULL, member_id UUID DEFAULT NULL, document_type VARCHAR(40) NOT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, mime_type VARCHAR(120) NOT NULL, size INT NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_HEALTH_DOCUMENTS_HOUSEHOLD_UPLOADED_AT ON health_documents (household_id, uploaded_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE health_documents');
    }
}
