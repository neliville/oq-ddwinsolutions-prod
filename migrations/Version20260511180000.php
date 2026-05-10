<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Préférences utilisateur : table user_preferences (1:1 avec user).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_preferences (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, first_name VARCHAR(120) DEFAULT NULL, last_name VARCHAR(120) DEFAULT NULL, company_name VARCHAR(255) DEFAULT NULL, job_title VARCHAR(120) DEFAULT NULL, sector VARCHAR(120) DEFAULT NULL, company_size VARCHAR(32) DEFAULT NULL, main_activity VARCHAR(32) DEFAULT NULL, primary_standard VARCHAR(32) NOT NULL, qhse_priority VARCHAR(32) NOT NULL, piloting_focus VARCHAR(32) NOT NULL, notify_overdue_actions TINYINT(1) DEFAULT 1 NOT NULL, notify_audits_to_prepare TINYINT(1) DEFAULT 1 NOT NULL, notify_capa_verification TINYINT(1) DEFAULT 1 NOT NULL, notify_critical_risks TINYINT(1) DEFAULT 1 NOT NULL, notify_weekly_digest TINYINT(1) DEFAULT 0 NOT NULL, notification_frequency VARCHAR(32) NOT NULL, export_display_name VARCHAR(255) DEFAULT NULL, export_job_title VARCHAR(255) DEFAULT NULL, export_company_name VARCHAR(255) DEFAULT NULL, export_pdf_footer VARCHAR(500) DEFAULT NULL, export_logo_filename VARCHAR(255) DEFAULT NULL, dashboard_visibility JSON DEFAULT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_USER_PREFERENCES_USER (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_preferences ADD CONSTRAINT FK_USER_PREFERENCES_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_preferences DROP FOREIGN KEY FK_USER_PREFERENCES_USER');
        $this->addSql('DROP TABLE user_preferences');
    }
}
