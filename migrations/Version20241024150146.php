<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241024150146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE project_leader_id project_leader_id INT NOT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE62290B03 FOREIGN KEY (project_leader_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE62290B03 ON project (project_leader_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE62290B03');
        $this->addSql('DROP INDEX IDX_2FB3D0EE62290B03 ON project');
        $this->addSql('ALTER TABLE project CHANGE project_leader_id project_leader_id VARCHAR(255) DEFAULT NULL');
    }
}
