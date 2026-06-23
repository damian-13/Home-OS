<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add soft delete support for blood tests.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blood_tests ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blood_tests DROP deleted_at');
    }
}
