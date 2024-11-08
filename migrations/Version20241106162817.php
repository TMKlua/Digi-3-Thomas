<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241106162817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, project_leader_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, start_date_forecast DATE DEFAULT NULL, end_date_forecast DATE DEFAULT NULL, validity_date_from DATE DEFAULT NULL, validity_date_to DATE DEFAULT NULL, INDEX IDX_2FB3D0EE62290B03 (project_leader_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE62290B03 FOREIGN KEY (project_leader_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD name VARCHAR(255) NOT NULL, ADD profile_picture_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE62290B03');
        $this->addSql('DROP TABLE project');
        $this->addSql('ALTER TABLE user DROP name, DROP profile_picture_url');
    }
}
