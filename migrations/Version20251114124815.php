<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114124815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amdec_share (id INT AUTO_INCREMENT NOT NULL, analysis_id INT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_29E28FBE7941003F (analysis_id), UNIQUE INDEX UNIQ_29E28FBE5F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE amdec_share_visit (id INT AUTO_INCREMENT NOT NULL, share_id INT NOT NULL, user_id INT DEFAULT NULL, visited_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ip_address VARCHAR(64) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, referer LONGTEXT DEFAULT NULL, session_id VARCHAR(128) DEFAULT NULL, INDEX IDX_91DAA43F2AE63FDB (share_id), INDEX IDX_91DAA43FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eight_dshare (id INT AUTO_INCREMENT NOT NULL, analysis_id INT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_73FB68407941003F (analysis_id), UNIQUE INDEX UNIQ_73FB68405F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eight_dshare_visit (id INT AUTO_INCREMENT NOT NULL, share_id INT NOT NULL, user_id INT DEFAULT NULL, visited_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ip_address VARCHAR(64) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, referer LONGTEXT DEFAULT NULL, session_id VARCHAR(128) DEFAULT NULL, INDEX IDX_688C74EA2AE63FDB (share_id), INDEX IDX_688C74EAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE five_why_share (id INT AUTO_INCREMENT NOT NULL, analysis_id INT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B4E9FECA7941003F (analysis_id), UNIQUE INDEX UNIQ_B4E9FECA5F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE five_why_share_visit (id INT AUTO_INCREMENT NOT NULL, share_id INT NOT NULL, user_id INT DEFAULT NULL, visited_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ip_address VARCHAR(64) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, referer LONGTEXT DEFAULT NULL, session_id VARCHAR(128) DEFAULT NULL, INDEX IDX_E7118E352AE63FDB (share_id), INDEX IDX_E7118E35A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pareto_share (id INT AUTO_INCREMENT NOT NULL, analysis_id INT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AB95EC2E7941003F (analysis_id), UNIQUE INDEX UNIQ_AB95EC2E5F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pareto_share_visit (id INT AUTO_INCREMENT NOT NULL, share_id INT NOT NULL, user_id INT DEFAULT NULL, visited_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ip_address VARCHAR(64) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, referer LONGTEXT DEFAULT NULL, session_id VARCHAR(128) DEFAULT NULL, INDEX IDX_4677E82A2AE63FDB (share_id), INDEX IDX_4677E82AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qqoqccp_share (id INT AUTO_INCREMENT NOT NULL, analysis_id INT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E40B8377941003F (analysis_id), UNIQUE INDEX UNIQ_E40B8375F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qqoqccp_share_visit (id INT AUTO_INCREMENT NOT NULL, share_id INT NOT NULL, user_id INT DEFAULT NULL, visited_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ip_address VARCHAR(64) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, referer LONGTEXT DEFAULT NULL, session_id VARCHAR(128) DEFAULT NULL, INDEX IDX_610404522AE63FDB (share_id), INDEX IDX_61040452A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE amdec_share ADD CONSTRAINT FK_29E28FBE7941003F FOREIGN KEY (analysis_id) REFERENCES amdec_analysis (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE amdec_share_visit ADD CONSTRAINT FK_91DAA43F2AE63FDB FOREIGN KEY (share_id) REFERENCES amdec_share (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE amdec_share_visit ADD CONSTRAINT FK_91DAA43FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE eight_dshare ADD CONSTRAINT FK_73FB68407941003F FOREIGN KEY (analysis_id) REFERENCES eight_danalysis (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE eight_dshare_visit ADD CONSTRAINT FK_688C74EA2AE63FDB FOREIGN KEY (share_id) REFERENCES eight_dshare (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE eight_dshare_visit ADD CONSTRAINT FK_688C74EAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE five_why_share ADD CONSTRAINT FK_B4E9FECA7941003F FOREIGN KEY (analysis_id) REFERENCES five_why_analysis (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE five_why_share_visit ADD CONSTRAINT FK_E7118E352AE63FDB FOREIGN KEY (share_id) REFERENCES five_why_share (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE five_why_share_visit ADD CONSTRAINT FK_E7118E35A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE pareto_share ADD CONSTRAINT FK_AB95EC2E7941003F FOREIGN KEY (analysis_id) REFERENCES pareto_analysis (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pareto_share_visit ADD CONSTRAINT FK_4677E82A2AE63FDB FOREIGN KEY (share_id) REFERENCES pareto_share (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pareto_share_visit ADD CONSTRAINT FK_4677E82AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE qqoqccp_share ADD CONSTRAINT FK_E40B8377941003F FOREIGN KEY (analysis_id) REFERENCES qqoqccp_analysis (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qqoqccp_share_visit ADD CONSTRAINT FK_610404522AE63FDB FOREIGN KEY (share_id) REFERENCES qqoqccp_share (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qqoqccp_share_visit ADD CONSTRAINT FK_61040452A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE amdec_analysis ADD description LONGTEXT DEFAULT NULL, CHANGE subject subject VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE qqoqccp_analysis ADD description LONGTEXT DEFAULT NULL, CHANGE subject subject VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amdec_share DROP FOREIGN KEY FK_29E28FBE7941003F');
        $this->addSql('ALTER TABLE amdec_share_visit DROP FOREIGN KEY FK_91DAA43F2AE63FDB');
        $this->addSql('ALTER TABLE amdec_share_visit DROP FOREIGN KEY FK_91DAA43FA76ED395');
        $this->addSql('ALTER TABLE eight_dshare DROP FOREIGN KEY FK_73FB68407941003F');
        $this->addSql('ALTER TABLE eight_dshare_visit DROP FOREIGN KEY FK_688C74EA2AE63FDB');
        $this->addSql('ALTER TABLE eight_dshare_visit DROP FOREIGN KEY FK_688C74EAA76ED395');
        $this->addSql('ALTER TABLE five_why_share DROP FOREIGN KEY FK_B4E9FECA7941003F');
        $this->addSql('ALTER TABLE five_why_share_visit DROP FOREIGN KEY FK_E7118E352AE63FDB');
        $this->addSql('ALTER TABLE five_why_share_visit DROP FOREIGN KEY FK_E7118E35A76ED395');
        $this->addSql('ALTER TABLE pareto_share DROP FOREIGN KEY FK_AB95EC2E7941003F');
        $this->addSql('ALTER TABLE pareto_share_visit DROP FOREIGN KEY FK_4677E82A2AE63FDB');
        $this->addSql('ALTER TABLE pareto_share_visit DROP FOREIGN KEY FK_4677E82AA76ED395');
        $this->addSql('ALTER TABLE qqoqccp_share DROP FOREIGN KEY FK_E40B8377941003F');
        $this->addSql('ALTER TABLE qqoqccp_share_visit DROP FOREIGN KEY FK_610404522AE63FDB');
        $this->addSql('ALTER TABLE qqoqccp_share_visit DROP FOREIGN KEY FK_61040452A76ED395');
        $this->addSql('DROP TABLE amdec_share');
        $this->addSql('DROP TABLE amdec_share_visit');
        $this->addSql('DROP TABLE eight_dshare');
        $this->addSql('DROP TABLE eight_dshare_visit');
        $this->addSql('DROP TABLE five_why_share');
        $this->addSql('DROP TABLE five_why_share_visit');
        $this->addSql('DROP TABLE pareto_share');
        $this->addSql('DROP TABLE pareto_share_visit');
        $this->addSql('DROP TABLE qqoqccp_share');
        $this->addSql('DROP TABLE qqoqccp_share_visit');
        $this->addSql('ALTER TABLE amdec_analysis DROP description, CHANGE subject subject LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE qqoqccp_analysis DROP description, CHANGE subject subject LONGTEXT DEFAULT NULL');
    }
}
