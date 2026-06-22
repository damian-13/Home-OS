<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create health blood tests and marker observations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE blood_tests (id VARCHAR(36) NOT NULL, household_id VARCHAR(36) NOT NULL, member_id VARCHAR(36) NOT NULL, tested_at DATE NOT NULL, lab_name VARCHAR(120) DEFAULT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BLOOD_TESTS_HOUSEHOLD_TESTED_AT ON blood_tests (household_id, tested_at)');
        $this->addSql('CREATE TABLE blood_test_markers (id VARCHAR(36) NOT NULL, blood_test_id VARCHAR(36) NOT NULL, marker_name VARCHAR(80) NOT NULL, value DOUBLE PRECISION NOT NULL, unit VARCHAR(32) NOT NULL, reference_min DOUBLE PRECISION DEFAULT NULL, reference_max DOUBLE PRECISION DEFAULT NULL, status VARCHAR(16) NOT NULL, notes TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BLOOD_TEST_MARKERS_NAME ON blood_test_markers (marker_name)');
        $this->addSql('CREATE INDEX IDX_8946652D374173 ON blood_test_markers (blood_test_id)');
        $this->addSql('ALTER TABLE blood_test_markers ADD CONSTRAINT FK_8946652D374173 FOREIGN KEY (blood_test_id) REFERENCES blood_tests (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blood_test_markers DROP CONSTRAINT FK_8946652D374173');
        $this->addSql('DROP TABLE blood_test_markers');
        $this->addSql('DROP TABLE blood_tests');
    }
}
