<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create household and household member tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE households (id VARCHAR(36) NOT NULL, name VARCHAR(120) NOT NULL, default_currency VARCHAR(3) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE household_members (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, display_name VARCHAR(120) NOT NULL, member_type VARCHAR(20) NOT NULL, birth_date DATE DEFAULT NULL, color VARCHAR(24) DEFAULT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_285438D1E79FF843 ON household_members (household_id)');
        $this->addSql('ALTER TABLE household_members ADD CONSTRAINT FK_4D9460C2D2919A68 FOREIGN KEY (household_id) REFERENCES households (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household_members DROP CONSTRAINT FK_4D9460C2D2919A68');
        $this->addSql('DROP TABLE household_members');
        $this->addSql('DROP TABLE households');
    }
}
