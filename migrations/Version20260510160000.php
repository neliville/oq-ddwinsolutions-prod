<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table tracking_event : journal d’événements métier (RGPD : IP hachée, pas d’IP en clair).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tracking_event (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, event_type VARCHAR(64) NOT NULL, tool VARCHAR(64) DEFAULT NULL, action VARCHAR(128) DEFAULT NULL, source VARCHAR(32) DEFAULT NULL, context LONGTEXT DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ip_hash VARCHAR(64) DEFAULT NULL, session_key VARCHAR(64) DEFAULT NULL, INDEX idx_tracking_event_created_at (created_at), INDEX idx_tracking_event_type_created (event_type, created_at), INDEX idx_tracking_event_user_created (user_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tracking_event ADD CONSTRAINT FK_TRACKING_EVENT_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracking_event DROP FOREIGN KEY FK_TRACKING_EVENT_USER');
        $this->addSql('DROP TABLE tracking_event');
    }
}
