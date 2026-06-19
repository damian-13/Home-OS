<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user account table for email/password authentication.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_accounts (id VARCHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, display_name VARCHAR(120) NOT NULL, household_id VARCHAR(36) NOT NULL, linked_member_id VARCHAR(36) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_ACCOUNTS_EMAIL ON user_accounts (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_accounts');
    }
}
