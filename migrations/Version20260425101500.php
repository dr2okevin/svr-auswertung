<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional series final score override';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE series ADD final_score_override DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE series DROP final_score_override');
    }
}
