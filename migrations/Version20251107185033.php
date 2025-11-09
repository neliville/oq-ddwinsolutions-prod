<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107185033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE export_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, tool VARCHAR(100) NOT NULL, format VARCHAR(20) NOT NULL, source_url VARCHAR(500) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, referer VARCHAR(500) DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E7392FF5A76ED395 (user_id), INDEX idx_exportlog_created_at (created_at), INDEX idx_exportlog_tool (tool), INDEX idx_exportlog_format (format), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE export_log ADD CONSTRAINT FK_E7392FF5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE export_log DROP FOREIGN KEY FK_E7392FF5A76ED395');
        $this->addSql('DROP TABLE export_log');
    }
}
