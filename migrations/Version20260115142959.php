<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115142959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `lead` (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, source VARCHAR(100) DEFAULT NULL, tool VARCHAR(50) DEFAULT NULL, utm_source VARCHAR(255) DEFAULT NULL, utm_medium VARCHAR(255) DEFAULT NULL, utm_campaign VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, session_id VARCHAR(255) DEFAULT NULL, gdpr_consent TINYINT(1) NOT NULL, score INT DEFAULT NULL, type VARCHAR(10) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_289161CBA76ED395 (user_id), INDEX idx_lead_email (email), INDEX idx_lead_created_at (created_at), INDEX idx_lead_source (source), INDEX idx_lead_tool (tool), INDEX idx_lead_type (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `lead` ADD CONSTRAINT FK_289161CBA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `lead` DROP FOREIGN KEY FK_289161CBA76ED395');
        $this->addSql('DROP TABLE `lead`');
    }
}
