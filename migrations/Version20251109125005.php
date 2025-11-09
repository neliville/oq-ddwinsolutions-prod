<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109125005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amdec_analysis (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, subject LONGTEXT DEFAULT NULL, data LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_amdec_user (user_id), INDEX idx_amdec_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eight_danalysis (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, data LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_eightd_user (user_id), INDEX idx_eightd_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pareto_analysis (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, data LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_pareto_user (user_id), INDEX idx_pareto_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qqoqccp_analysis (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, subject LONGTEXT DEFAULT NULL, data LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_qqoqccp_user (user_id), INDEX idx_qqoqccp_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE amdec_analysis ADD CONSTRAINT FK_714BE938A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE eight_danalysis ADD CONSTRAINT FK_BB457A53A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE pareto_analysis ADD CONSTRAINT FK_CE98DB1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE qqoqccp_analysis ADD CONSTRAINT FK_3770ABE0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ishikawa_share RENAME INDEX uniq_ishikawa_share_token TO UNIQ_CC7ECE725F37A13B');
        $this->addSql('ALTER TABLE ishikawa_share_visit RENAME INDEX idx_share_visit_share TO IDX_CADC475E2AE63FDB');
        $this->addSql('ALTER TABLE ishikawa_share_visit RENAME INDEX idx_share_visit_user TO IDX_CADC475EA76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amdec_analysis DROP FOREIGN KEY FK_714BE938A76ED395');
        $this->addSql('ALTER TABLE eight_danalysis DROP FOREIGN KEY FK_BB457A53A76ED395');
        $this->addSql('ALTER TABLE pareto_analysis DROP FOREIGN KEY FK_CE98DB1A76ED395');
        $this->addSql('ALTER TABLE qqoqccp_analysis DROP FOREIGN KEY FK_3770ABE0A76ED395');
        $this->addSql('DROP TABLE amdec_analysis');
        $this->addSql('DROP TABLE eight_danalysis');
        $this->addSql('DROP TABLE pareto_analysis');
        $this->addSql('DROP TABLE qqoqccp_analysis');
        $this->addSql('ALTER TABLE ishikawa_share RENAME INDEX uniq_cc7ece725f37a13b TO uniq_ishikawa_share_token');
        $this->addSql('ALTER TABLE ishikawa_share_visit RENAME INDEX idx_cadc475e2ae63fdb TO idx_share_visit_share');
        $this->addSql('ALTER TABLE ishikawa_share_visit RENAME INDEX idx_cadc475ea76ed395 TO idx_share_visit_user');
    }
}
