<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add max_series_count to discipline';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discipline ADD max_series_count INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discipline DROP max_series_count');
    }
}
