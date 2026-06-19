<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align expense foreign key index names with Doctrine mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_2d3a8da12469de2 RENAME TO IDX_2496F35B12469DE2');
        $this->addSql('ALTER INDEX idx_55e69e7a12469de2 RENAME TO IDX_4868908F12469DE2');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_2496f35b12469de2 RENAME TO IDX_2D3A8DA12469DE2');
        $this->addSql('ALTER INDEX idx_4868908f12469de2 RENAME TO IDX_55E69E7A12469DE2');
    }
}
