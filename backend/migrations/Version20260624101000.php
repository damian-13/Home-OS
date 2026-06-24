<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624101000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align finance review metadata defaults with Doctrine mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expenses ALTER review_status DROP DEFAULT');
        $this->addSql('ALTER TABLE income_entries ALTER income_kind DROP DEFAULT');
        $this->addSql('ALTER TABLE income_entries ALTER review_status DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE expenses ALTER review_status SET DEFAULT 'needs_review'");
        $this->addSql("ALTER TABLE income_entries ALTER income_kind SET DEFAULT 'other'");
        $this->addSql("ALTER TABLE income_entries ALTER review_status SET DEFAULT 'needs_review'");
    }
}
