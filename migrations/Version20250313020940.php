<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313020940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users CHANGE reset_token_expires_at reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_attachments DROP FOREIGN KEY FK_1B157E445235400');
        $this->addSql('ALTER TABLE task_attachments DROP FOREIGN KEY FK_1B157E4D82B0047');
        $this->addSql('ALTER TABLE customers DROP FOREIGN KEY FK_62534E21EB377663');
        $this->addSql('ALTER TABLE parameters DROP FOREIGN KEY FK_69348FEB8B34CE7');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9B199B40D');
        $this->addSql('ALTER TABLE users CHANGE reset_token_expires_at reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A45B5A3408');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A460984F51');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4E5809D3');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597A7E5433');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597CF9B02A3');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597AED394E');
        $this->addSql('ALTER TABLE task_comments DROP FOREIGN KEY FK_1F5E7C667EC56396');
        $this->addSql('ALTER TABLE task_comments DROP FOREIGN KEY FK_1F5E7C66541DB185');
    }
}
