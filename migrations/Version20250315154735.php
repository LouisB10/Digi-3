<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250315154735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE work_logs');
        $this->addSql('ALTER TABLE customers CHANGE customer_address_street customer_address_street VARCHAR(255) DEFAULT NULL, CHANGE customer_address_zipcode customer_address_zipcode VARCHAR(35) DEFAULT NULL, CHANGE customer_address_city customer_address_city VARCHAR(255) DEFAULT NULL, CHANGE customer_address_country customer_address_country VARCHAR(35) DEFAULT NULL, CHANGE customer_created_at customer_created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE customers RENAME INDEX customer_updated_by TO IDX_62534E21EB377663');
        $this->addSql('ALTER TABLE parameters CHANGE param_value param_value LONGTEXT NOT NULL, CHANGE param_description param_description LONGTEXT DEFAULT NULL, CHANGE param_created_at param_created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE parameters RENAME INDEX param_key TO UNIQ_69348FE35A9B410');
        $this->addSql('ALTER TABLE parameters RENAME INDEX param_updated_by TO IDX_69348FEB8B34CE7');
        $this->addSql('ALTER TABLE projects DROP project_created_at, CHANGE project_name project_name VARCHAR(100) NOT NULL, CHANGE project_description project_description LONGTEXT DEFAULT NULL, CHANGE project_status project_status VARCHAR(255) NOT NULL, CHANGE project_start_date project_start_date DATETIME DEFAULT NULL, CHANGE project_end_date project_end_date DATETIME DEFAULT NULL, CHANGE project_target_date project_target_date DATETIME DEFAULT NULL, CHANGE project_updated_at project_updated_at DATETIME NOT NULL, CHANGE project_updated_by project_updated_by INT NOT NULL');
        $this->addSql('ALTER TABLE projects RENAME INDEX project_customer_id TO IDX_5C93B3A45B5A3408');
        $this->addSql('ALTER TABLE projects RENAME INDEX project_manager_id TO IDX_5C93B3A460984F51');
        $this->addSql('ALTER TABLE projects RENAME INDEX project_updated_by TO IDX_5C93B3A4E5809D3');
        $this->addSql('ALTER TABLE task_attachments ADD attachment_description LONGTEXT DEFAULT NULL, CHANGE attachment_created_at attachment_created_at DATETIME NOT NULL, CHANGE attachment_path attachment_original_name VARCHAR(255) NOT NULL, CHANGE attachment_size attachment_file_size INT NOT NULL, CHANGE attachment_type attachment_mime_type VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE task_attachments RENAME INDEX attachment_task_id TO IDX_1B157E445235400');
        $this->addSql('ALTER TABLE task_attachments RENAME INDEX attachment_uploaded_by TO IDX_1B157E4D82B0047');
        $this->addSql('ALTER TABLE task_comments CHANGE comment_content comment_content LONGTEXT NOT NULL, CHANGE comment_created_at comment_created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE task_comments RENAME INDEX comment_task_id TO IDX_1F5E7C667EC56396');
        $this->addSql('ALTER TABLE task_comments RENAME INDEX comment_user_id TO IDX_1F5E7C66541DB185');
        $this->addSql('DROP INDEX task_assigned_to ON tasks');
        $this->addSql('DROP INDEX task_updated_by ON tasks');
        $this->addSql('ALTER TABLE tasks ADD task_assigned_to_id INT DEFAULT NULL, ADD task_updated_by_id INT NOT NULL, ADD task_type VARCHAR(35) NOT NULL, DROP task_assigned_to, DROP task_created_at, DROP task_updated_by, CHANGE task_name task_name VARCHAR(100) NOT NULL, CHANGE task_description task_description LONGTEXT NOT NULL, CHANGE task_status task_status VARCHAR(255) NOT NULL, CHANGE task_priority task_priority VARCHAR(255) NOT NULL, CHANGE task_complexity task_complexity VARCHAR(255) NOT NULL, CHANGE task_start_date task_start_date DATETIME DEFAULT NULL, CHANGE task_end_date task_end_date DATETIME DEFAULT NULL, CHANGE task_target_date task_target_date DATETIME DEFAULT NULL, CHANGE task_updated_at task_updated_at DATETIME NOT NULL');
        $this->addSql('CREATE INDEX IDX_50586597E8940E88 ON tasks (task_assigned_to_id)');
        $this->addSql('CREATE INDEX IDX_50586597CC7A768F ON tasks (task_updated_by_id)');
        $this->addSql('ALTER TABLE tasks RENAME INDEX task_project_id TO IDX_50586597A7E5433');
        $this->addSql('DROP INDEX user_updated_by ON users');
        $this->addSql('ALTER TABLE users ADD user_date_to DATETIME DEFAULT NULL, ADD reset_token VARCHAR(100) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL, DROP user_created_at, CHANGE user_first_name user_first_name VARCHAR(50) NOT NULL, CHANGE user_last_name user_last_name VARCHAR(50) NOT NULL, CHANGE user_email user_email VARCHAR(180) NOT NULL, CHANGE user_avatar user_avatar VARCHAR(255) NOT NULL, CHANGE user_updated_at user_date_from DATETIME DEFAULT NULL, CHANGE user_updated_by user_user_maj INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX user_email TO UNIQ_1483A5E9550872C');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE work_logs (id INT AUTO_INCREMENT NOT NULL, work_task_id INT NOT NULL, work_user_id INT NOT NULL, work_hours NUMERIC(5, 2) NOT NULL, work_date DATE NOT NULL, work_description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, work_created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX work_task_id (work_task_id), INDEX work_user_id (work_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('ALTER TABLE task_comments DROP FOREIGN KEY FK_1F5E7C667EC56396');
        $this->addSql('ALTER TABLE task_comments DROP FOREIGN KEY FK_1F5E7C66541DB185');
        $this->addSql('ALTER TABLE task_comments CHANGE comment_content comment_content TEXT NOT NULL, CHANGE comment_created_at comment_created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE task_comments RENAME INDEX idx_1f5e7c667ec56396 TO comment_task_id');
        $this->addSql('ALTER TABLE task_comments RENAME INDEX idx_1f5e7c66541db185 TO comment_user_id');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597A7E5433');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597E8940E88');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597CC7A768F');
        $this->addSql('DROP INDEX IDX_50586597E8940E88 ON tasks');
        $this->addSql('DROP INDEX IDX_50586597CC7A768F ON tasks');
        $this->addSql('ALTER TABLE tasks ADD task_created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD task_updated_by INT DEFAULT NULL, DROP task_updated_by_id, DROP task_type, CHANGE task_name task_name VARCHAR(255) NOT NULL, CHANGE task_description task_description TEXT DEFAULT NULL, CHANGE task_status task_status VARCHAR(255) DEFAULT \'todo\' NOT NULL, CHANGE task_start_date task_start_date DATE DEFAULT NULL, CHANGE task_end_date task_end_date DATE DEFAULT NULL, CHANGE task_target_date task_target_date DATE NOT NULL, CHANGE task_complexity task_complexity VARCHAR(255) DEFAULT NULL, CHANGE task_priority task_priority VARCHAR(255) DEFAULT \'medium\' NOT NULL, CHANGE task_updated_at task_updated_at DATETIME DEFAULT NULL, CHANGE task_assigned_to_id task_assigned_to INT DEFAULT NULL');
        $this->addSql('CREATE INDEX task_assigned_to ON tasks (task_assigned_to)');
        $this->addSql('CREATE INDEX task_updated_by ON tasks (task_updated_by)');
        $this->addSql('ALTER TABLE tasks RENAME INDEX idx_50586597a7e5433 TO task_project_id');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A45B5A3408');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A460984F51');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4E5809D3');
        $this->addSql('ALTER TABLE projects ADD project_created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE project_updated_by project_updated_by INT DEFAULT NULL, CHANGE project_name project_name VARCHAR(255) NOT NULL, CHANGE project_description project_description TEXT DEFAULT NULL, CHANGE project_status project_status VARCHAR(255) DEFAULT \'draft\' NOT NULL, CHANGE project_start_date project_start_date DATE NOT NULL, CHANGE project_target_date project_target_date DATE NOT NULL, CHANGE project_end_date project_end_date DATE DEFAULT NULL, CHANGE project_updated_at project_updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE projects RENAME INDEX idx_5c93b3a45b5a3408 TO project_customer_id');
        $this->addSql('ALTER TABLE projects RENAME INDEX idx_5c93b3a460984f51 TO project_manager_id');
        $this->addSql('ALTER TABLE projects RENAME INDEX idx_5c93b3a4e5809d3 TO project_updated_by');
        $this->addSql('ALTER TABLE users ADD user_created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD user_updated_at DATETIME DEFAULT NULL, DROP user_date_from, DROP user_date_to, DROP reset_token, DROP reset_token_expires_at, CHANGE user_first_name user_first_name VARCHAR(35) NOT NULL, CHANGE user_last_name user_last_name VARCHAR(35) NOT NULL, CHANGE user_email user_email VARCHAR(35) NOT NULL, CHANGE user_avatar user_avatar VARCHAR(255) DEFAULT \'/img/account/default-avatar.jpg\', CHANGE user_user_maj user_updated_by INT DEFAULT NULL');
        $this->addSql('CREATE INDEX user_updated_by ON users (user_updated_by)');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_1483a5e9550872c TO user_email');
        $this->addSql('ALTER TABLE customers DROP FOREIGN KEY FK_62534E21EB377663');
        $this->addSql('ALTER TABLE customers CHANGE customer_address_street customer_address_street VARCHAR(255) NOT NULL, CHANGE customer_address_zipcode customer_address_zipcode VARCHAR(35) NOT NULL, CHANGE customer_address_city customer_address_city VARCHAR(255) NOT NULL, CHANGE customer_address_country customer_address_country VARCHAR(35) NOT NULL, CHANGE customer_created_at customer_created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE customers RENAME INDEX idx_62534e21eb377663 TO customer_updated_by');
        $this->addSql('ALTER TABLE parameters DROP FOREIGN KEY FK_69348FEB8B34CE7');
        $this->addSql('ALTER TABLE parameters CHANGE param_value param_value TEXT NOT NULL, CHANGE param_description param_description TEXT DEFAULT NULL, CHANGE param_created_at param_created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE parameters RENAME INDEX uniq_69348fe35a9b410 TO param_key');
        $this->addSql('ALTER TABLE parameters RENAME INDEX idx_69348feb8b34ce7 TO param_updated_by');
        $this->addSql('ALTER TABLE task_attachments DROP FOREIGN KEY FK_1B157E445235400');
        $this->addSql('ALTER TABLE task_attachments DROP FOREIGN KEY FK_1B157E4D82B0047');
        $this->addSql('ALTER TABLE task_attachments DROP attachment_description, CHANGE attachment_created_at attachment_created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE attachment_original_name attachment_path VARCHAR(255) NOT NULL, CHANGE attachment_mime_type attachment_type VARCHAR(100) NOT NULL, CHANGE attachment_file_size attachment_size INT NOT NULL');
        $this->addSql('ALTER TABLE task_attachments RENAME INDEX idx_1b157e445235400 TO attachment_task_id');
        $this->addSql('ALTER TABLE task_attachments RENAME INDEX idx_1b157e4d82b0047 TO attachment_uploaded_by');
    }
}
