<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link imported blood tests to their source health document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blood_tests ADD source_document_id VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blood_tests DROP source_document_id');
    }
}
