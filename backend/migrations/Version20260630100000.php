<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add minimal user notification digest preferences.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_accounts ADD notification_digest_enabled BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE user_accounts ALTER notification_digest_enabled DROP DEFAULT');
        $this->addSql('ALTER TABLE user_accounts ADD notification_digest_hour SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_accounts DROP notification_digest_enabled');
        $this->addSql('ALTER TABLE user_accounts DROP notification_digest_hour');
    }
}
