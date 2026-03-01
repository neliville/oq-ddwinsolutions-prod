<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301101021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE download_request (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', email VARCHAR(255) NOT NULL, resource_slug VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, token VARCHAR(64) DEFAULT NULL, expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', accessed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', access_count INT DEFAULT 0 NOT NULL, INDEX idx_download_request_status (status), INDEX idx_download_request_token (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE download_request');
    }
}
