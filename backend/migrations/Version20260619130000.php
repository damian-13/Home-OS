<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add soft delete support to expenses and recurring bills.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expenses ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE recurring_bills ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expenses DROP deleted_at');
        $this->addSql('ALTER TABLE recurring_bills DROP deleted_at');
    }
}
