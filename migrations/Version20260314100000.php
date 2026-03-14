<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional max team size, team-to-competition relation and team members';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE competition ADD max_team_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE team ADD competition_id INT NOT NULL');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F7DF2D13F FOREIGN KEY (competition_id) REFERENCES competition (id)');
        $this->addSql('CREATE INDEX IDX_C4E0A61F7DF2D13F ON team (competition_id)');

        $this->addSql('CREATE TABLE team_member (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, person_id INT NOT NULL, discipline_id INT NOT NULL, INDEX IDX_94169F9472969D3C (team_id), INDEX IDX_94169F94217BBB47 (person_id), INDEX IDX_94169F94A5522701 (discipline_id), UNIQUE INDEX team_member_unique_assignment (team_id, person_id, discipline_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_94169F9472969D3C FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_94169F94217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_94169F94A5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team_member DROP FOREIGN KEY FK_94169F9472969D3C');
        $this->addSql('ALTER TABLE team_member DROP FOREIGN KEY FK_94169F94217BBB47');
        $this->addSql('ALTER TABLE team_member DROP FOREIGN KEY FK_94169F94A5522701');
        $this->addSql('DROP TABLE team_member');

        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F7DF2D13F');
        $this->addSql('DROP INDEX IDX_C4E0A61F7DF2D13F ON team');
        $this->addSql('ALTER TABLE team DROP competition_id');

        $this->addSql('ALTER TABLE competition DROP max_team_size');
    }
}
