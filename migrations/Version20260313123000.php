<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add many-to-many relation between competition and discipline';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE competitions_disciplines_mm (competition INT NOT NULL, discipline INT NOT NULL, INDEX IDX_20D4A865AB0A3F8D (competition), INDEX IDX_20D4A865A5522701 (discipline), PRIMARY KEY(competition, discipline)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE competitions_disciplines_mm ADD CONSTRAINT FK_20D4A865AB0A3F8D FOREIGN KEY (competition) REFERENCES competition (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE competitions_disciplines_mm ADD CONSTRAINT FK_20D4A865A5522701 FOREIGN KEY (discipline) REFERENCES discipline (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE competitions_disciplines_mm');
    }
}
