<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-user language preference.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_accounts ADD language VARCHAR(2) DEFAULT 'en' NOT NULL");
        $this->addSql('ALTER TABLE user_accounts ALTER language DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_accounts DROP language');
    }
}
