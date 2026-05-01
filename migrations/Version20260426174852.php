<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426174852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discipline ADD short_name VARCHAR(255) NOT NULL');

    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE discipline DROP short_name');
    }
}
