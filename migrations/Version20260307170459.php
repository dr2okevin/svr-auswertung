<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260307170459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE competition (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE discipline (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, shots_per_series INT NOT NULL, scoring_mode VARCHAR(255) NOT NULL, max_scores_per_shot DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE person (id INT AUTO_INCREMENT NOT NULL, frist_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birthdate DATE DEFAULT NULL, professional TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE round (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, competition_id INT NOT NULL, INDEX IDX_C5EEEA347B39D312 (competition_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE series (id INT AUTO_INCREMENT NOT NULL, shots_count INT NOT NULL, import_file VARCHAR(255) DEFAULT NULL, person_id INT NOT NULL, discipline_id INT NOT NULL, round_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_3A10012D217BBB47 (person_id), INDEX IDX_3A10012DA5522701 (discipline_id), INDEX IDX_3A10012DA6005CA0 (round_id), INDEX IDX_3A10012D296CD8AE (team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE shot (id INT AUTO_INCREMENT NOT NULL, shot_index INT NOT NULL, value DOUBLE PRECISION NOT NULL, xposition DOUBLE PRECISION DEFAULT NULL, yposition DOUBLE PRECISION DEFAULT NULL, record_time DATETIME NOT NULL, series_id INT NOT NULL, INDEX IDX_AB0788BB5278319C (series_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE round ADD CONSTRAINT FK_C5EEEA347B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id)');
        $this->addSql('ALTER TABLE series ADD CONSTRAINT FK_3A10012D217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE series ADD CONSTRAINT FK_3A10012DA5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id)');
        $this->addSql('ALTER TABLE series ADD CONSTRAINT FK_3A10012DA6005CA0 FOREIGN KEY (round_id) REFERENCES round (id)');
        $this->addSql('ALTER TABLE series ADD CONSTRAINT FK_3A10012D296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE shot ADD CONSTRAINT FK_AB0788BB5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE round DROP FOREIGN KEY FK_C5EEEA347B39D312');
        $this->addSql('ALTER TABLE series DROP FOREIGN KEY FK_3A10012D217BBB47');
        $this->addSql('ALTER TABLE series DROP FOREIGN KEY FK_3A10012DA5522701');
        $this->addSql('ALTER TABLE series DROP FOREIGN KEY FK_3A10012DA6005CA0');
        $this->addSql('ALTER TABLE series DROP FOREIGN KEY FK_3A10012D296CD8AE');
        $this->addSql('ALTER TABLE shot DROP FOREIGN KEY FK_AB0788BB5278319C');
        $this->addSql('DROP TABLE competition');
        $this->addSql('DROP TABLE discipline');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE round');
        $this->addSql('DROP TABLE series');
        $this->addSql('DROP TABLE shot');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE user');
    }
}
