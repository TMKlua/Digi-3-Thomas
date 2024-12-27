<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241211205710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manager_project ADD CONSTRAINT FK_917EE5F862290B03 FOREIGN KEY (project_leader_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_917EE5F862290B03 ON manager_project (project_leader_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manager_project DROP FOREIGN KEY FK_917EE5F862290B03');
        $this->addSql('DROP INDEX IDX_917EE5F862290B03 ON manager_project');
    }
}
