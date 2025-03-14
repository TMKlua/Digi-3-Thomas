<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241114151738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customers (id INT AUTO_INCREMENT NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_address_street VARCHAR(255) DEFAULT NULL, customer_address_zipcode VARCHAR(35) DEFAULT NULL, customer_address_city VARCHAR(255) DEFAULT NULL, customer_address_country VARCHAR(35) DEFAULT NULL, customer_vat VARCHAR(35) DEFAULT NULL, customer_siren VARCHAR(35) DEFAULT NULL, customer_reference VARCHAR(255) DEFAULT NULL, customer_date_from DATETIME DEFAULT NULL, customer_date_to DATETIME DEFAULT NULL, customer_user_maj INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_details (id INT AUTO_INCREMENT NOT NULL, invoice_details_number VARCHAR(35) NOT NULL, invoice_details_tasks VARCHAR(35) NOT NULL, invoice_header_date DATETIME NOT NULL, invoice_header_customer INT NOT NULL, invoice_details_ht INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_header (id INT AUTO_INCREMENT NOT NULL, invoice_header_number VARCHAR(35) NOT NULL, invoice_header_type VARCHAR(35) DEFAULT NULL, invoice_header_date DATETIME NOT NULL, invoice_header_customer INT NOT NULL, invoice_header_ht INT NOT NULL, invoice_header_vat INT NOT NULL, invoice_header_ttc INT NOT NULL, invoice_header_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parameters (id INT AUTO_INCREMENT NOT NULL, param_key VARCHAR(35) NOT NULL, param_value VARCHAR(35) NOT NULL, param_date_from DATETIME DEFAULT NULL, param_date_to DATETIME DEFAULT NULL, param_user_maj INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task_workload (id INT AUTO_INCREMENT NOT NULL, task_workload_task INT NOT NULL, task_workload_user INT NOT NULL, task_workload_duration INT NOT NULL, task_workload_date_from DATETIME NOT NULL, task_workload_date_to DATETIME NOT NULL, task_workload_user_maj INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tasks (id INT AUTO_INCREMENT NOT NULL, task_type VARCHAR(35) NOT NULL, task_name VARCHAR(35) NOT NULL, task_text VARCHAR(255) NULL, task_parent INT DEFAULT NULL, task_user INT DEFAULT NULL, task_real_start_date DATETIME DEFAULT NULL, task_real_end_date DATETIME DEFAULT NULL, task_target_start_date DATETIME DEFAULT NULL, task_complexity VARCHAR(35) DEFAULT NULL, task_priority VARCHAR(35) DEFAULT NULL, task_target_end_date DATETIME DEFAULT NULL, task_date_from DATETIME DEFAULT NULL, task_date_to DATETIME DEFAULT NULL, task_user_maj INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tasks_attachments (id INT AUTO_INCREMENT NOT NULL, task_attachments_id INT NOT NULL, task_attachments_value VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tasks_comments (id INT AUTO_INCREMENT NOT NULL, task_comments_id INT NOT NULL, task_comments_value VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tasks_label (id INT AUTO_INCREMENT NOT NULL, task_label_id INT NOT NULL, task_label_value VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tasks_rates (id INT AUTO_INCREMENT NOT NULL, task_rates_user_role INT NOT NULL, task_rates_task INT NOT NULL, task_rates_amount INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, user_first_name VARCHAR(35) NOT NULL, user_last_name VARCHAR(35) NOT NULL, user_email VARCHAR(35) NOT NULL, user_avatar VARCHAR(255) NOT NULL, user_role VARCHAR(35) NOT NULL, user_password VARCHAR(255) NOT NULL, user_date_from DATETIME DEFAULT NULL, user_date_to DATETIME DEFAULT NULL, user_user_maj INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users_vacation (id INT AUTO_INCREMENT NOT NULL, users_vacation_user INT NOT NULL, users_vacation_from DATETIME DEFAULT NULL, users_vacation_to DATETIME DEFAULT NULL, users_date_from DATETIME NOT NULL, users_date_to DATETIME DEFAULT NULL, users_user_maj INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE customers');
        $this->addSql('DROP TABLE invoice_details');
        $this->addSql('DROP TABLE invoice_header');
        $this->addSql('DROP TABLE parameters');
        $this->addSql('DROP TABLE task_workload');
        $this->addSql('DROP TABLE tasks');
        $this->addSql('DROP TABLE tasks_attachments');
        $this->addSql('DROP TABLE tasks_comments');
        $this->addSql('DROP TABLE tasks_label');
        $this->addSql('DROP TABLE tasks_rates');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE users_vacation');
    }
}
