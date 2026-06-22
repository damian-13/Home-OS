<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622101000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align blood test marker foreign key index name with Doctrine mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_8946652d374173 RENAME TO IDX_9F7210E3A1FBDFF8');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_9f7210e3a1fbdff8 RENAME TO IDX_8946652D374173');
    }
}
