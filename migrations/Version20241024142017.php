<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241024142017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE name name VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE end_date end_date DATE DEFAULT NULL, CHANGE start_date_forecast start_date_forecast DATE DEFAULT NULL, CHANGE end_date_forecast end_date_forecast DATE DEFAULT NULL, CHANGE validity_date_from validity_date_from DATE DEFAULT NULL, CHANGE validity_date_to validity_date_to DATE DEFAULT NULL, CHANGE project_leader_id project_leader_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE end_date end_date DATE NOT NULL, CHANGE start_date_forecast start_date_forecast DATE NOT NULL, CHANGE end_date_forecast end_date_forecast DATE NOT NULL, CHANGE validity_date_from validity_date_from DATE NOT NULL, CHANGE validity_date_to validity_date_to DATE NOT NULL, CHANGE project_leader_id project_leader_id VARCHAR(255) NOT NULL');
    }
}
