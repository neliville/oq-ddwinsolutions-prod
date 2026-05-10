<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260510100054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tables QSE : audit ISO, CAPA, matrice des risques, liaisons.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE qse_audit (id INT AUTO_INCREMENT NOT NULL, audit_plan_id INT DEFAULT NULL, owner_id INT NOT NULL, main_auditor VARCHAR(255) DEFAULT NULL, audited_parties LONGTEXT DEFAULT NULL, audited_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', objective LONGTEXT DEFAULT NULL, scope LONGTEXT DEFAULT NULL, general_conclusion LONGTEXT DEFAULT NULL, global_compliance_rate DOUBLE PRECISION DEFAULT NULL, global_score INT DEFAULT NULL, status VARCHAR(32) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, audit_version VARCHAR(50) DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5362844FF9BBD7EB (audit_plan_id), INDEX idx_qse_audit_owner (owner_id), INDEX idx_qse_audit_audited_at (audited_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qse_audit_evaluation (id INT AUTO_INCREMENT NOT NULL, audit_id INT NOT NULL, requirement_id INT NOT NULL, owner_id INT NOT NULL, score INT DEFAULT NULL, audit_comment LONGTEXT DEFAULT NULL, evidence LONGTEXT DEFAULT NULL, field_observation LONGTEXT DEFAULT NULL, criticality VARCHAR(50) DEFAULT NULL, mandatory TINYINT(1) DEFAULT 1 NOT NULL, metadata JSON DEFAULT NULL, INDEX IDX_730A4C587B576F77 (requirement_id), INDEX IDX_730A4C587E3C61F9 (owner_id), INDEX idx_qse_audit_eval_audit (audit_id), UNIQUE INDEX uniq_qse_audit_eval_audit_req (audit_id, requirement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qse_audit_finding (id INT AUTO_INCREMENT NOT NULL, audit_evaluation_id INT NOT NULL, owner_id INT NOT NULL, finding_type VARCHAR(32) NOT NULL, description LONGTEXT NOT NULL, probable_cause LONGTEXT DEFAULT NULL, criticality VARCHAR(50) DEFAULT NULL, impact LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_653AF7A17E3C61F9 (owner_id), INDEX idx_qse_audit_finding_eval (audit_evaluation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qse_audit_plan (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, title VARCHAR(255) NOT NULL, audit_type VARCHAR(32) NOT NULL, referential VARCHAR(32) NOT NULL, scope LONGTEXT DEFAULT NULL, concerned_process VARCHAR(255) DEFAULT NULL, concerned_site VARCHAR(255) DEFAULT NULL, frequency VARCHAR(100) DEFAULT NULL, planned_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', performed_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', status VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_qse_audit_plan_owner (owner_id), INDEX idx_qse_audit_plan_planned_at (planned_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qse_audit_requirement (id INT AUTO_INCREMENT NOT NULL, chapter VARCHAR(120) NOT NULL, iso_article VARCHAR(40) NOT NULL, requirement_text LONGTEXT NOT NULL, iso_comment LONGTEXT DEFAULT NULL, referential_type VARCHAR(32) NOT NULL, display_order INT NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, legacy_key VARCHAR(64) NOT NULL, INDEX idx_qse_audit_req_chapter_order (chapter, display_order), UNIQUE INDEX uniq_qse_audit_requirement_legacy (legacy_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qse_capa_action (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, source_audit_evaluation_id INT DEFAULT NULL, source_audit_finding_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, capa_type VARCHAR(32) NOT NULL, origin VARCHAR(32) NOT NULL, priority VARCHAR(50) DEFAULT NULL, criticality VARCHAR(50) DEFAULT NULL, status VARCHAR(32) NOT NULL, responsible VARCHAR(255) DEFAULT NULL, due_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', implementation_done_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', closure_proof LONGTEXT DEFAULT NULL, effectiveness_verification LONGTEXT DEFAULT NULL, effectiveness_comment LONGTEXT DEFAULT NULL, pdca_phase VARCHAR(32) DEFAULT NULL, source_tool VARCHAR(50) DEFAULT NULL, source_tool_entity_id INT DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9B2B70E57E3C61F9 (owner_id), INDEX IDX_9B2B70E5FB9E1F4 (source_audit_evaluation_id), INDEX IDX_9B2B70E5A9896BBE (source_audit_finding_id), INDEX idx_qse_capa_owner_status (owner_id, status), INDEX idx_qse_capa_due_at (due_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qse_risk_matrix_entry (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, identified_risk VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, concerned_process VARCHAR(255) DEFAULT NULL, risk_category VARCHAR(32) NOT NULL, severity INT DEFAULT NULL, probability INT DEFAULT NULL, detection INT DEFAULT NULL, criticality_score INT DEFAULT NULL, risk_level VARCHAR(50) DEFAULT NULL, existing_actions LONGTEXT DEFAULT NULL, responsible VARCHAR(255) DEFAULT NULL, review_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', status VARCHAR(32) NOT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_qse_risk_owner (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE qse_risk_matrix_entry_capa_action (risk_matrix_entry_id INT NOT NULL, capaaction_id INT NOT NULL, INDEX IDX_55AB1EC6BD02312C (risk_matrix_entry_id), INDEX IDX_55AB1EC6B4860953 (capaaction_id), PRIMARY KEY(risk_matrix_entry_id, capaaction_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE qse_audit ADD CONSTRAINT FK_5362844FF9BBD7EB FOREIGN KEY (audit_plan_id) REFERENCES qse_audit_plan (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE qse_audit ADD CONSTRAINT FK_5362844F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_audit_evaluation ADD CONSTRAINT FK_730A4C58BD29F359 FOREIGN KEY (audit_id) REFERENCES qse_audit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_audit_evaluation ADD CONSTRAINT FK_730A4C587B576F77 FOREIGN KEY (requirement_id) REFERENCES qse_audit_requirement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_audit_evaluation ADD CONSTRAINT FK_730A4C587E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_audit_finding ADD CONSTRAINT FK_653AF7A11884055C FOREIGN KEY (audit_evaluation_id) REFERENCES qse_audit_evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_audit_finding ADD CONSTRAINT FK_653AF7A17E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_audit_plan ADD CONSTRAINT FK_E222D44B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_capa_action ADD CONSTRAINT FK_9B2B70E57E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_capa_action ADD CONSTRAINT FK_9B2B70E5FB9E1F4 FOREIGN KEY (source_audit_evaluation_id) REFERENCES qse_audit_evaluation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE qse_capa_action ADD CONSTRAINT FK_9B2B70E5A9896BBE FOREIGN KEY (source_audit_finding_id) REFERENCES qse_audit_finding (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE qse_risk_matrix_entry ADD CONSTRAINT FK_90B91307E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_risk_matrix_entry_capa_action ADD CONSTRAINT FK_55AB1EC6BD02312C FOREIGN KEY (risk_matrix_entry_id) REFERENCES qse_risk_matrix_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_risk_matrix_entry_capa_action ADD CONSTRAINT FK_55AB1EC6B4860953 FOREIGN KEY (capaaction_id) REFERENCES qse_capa_action (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE qse_audit DROP FOREIGN KEY FK_5362844FF9BBD7EB');
        $this->addSql('ALTER TABLE qse_audit DROP FOREIGN KEY FK_5362844F7E3C61F9');
        $this->addSql('ALTER TABLE qse_audit_evaluation DROP FOREIGN KEY FK_730A4C58BD29F359');
        $this->addSql('ALTER TABLE qse_audit_evaluation DROP FOREIGN KEY FK_730A4C587B576F77');
        $this->addSql('ALTER TABLE qse_audit_evaluation DROP FOREIGN KEY FK_730A4C587E3C61F9');
        $this->addSql('ALTER TABLE qse_audit_finding DROP FOREIGN KEY FK_653AF7A11884055C');
        $this->addSql('ALTER TABLE qse_audit_finding DROP FOREIGN KEY FK_653AF7A17E3C61F9');
        $this->addSql('ALTER TABLE qse_audit_plan DROP FOREIGN KEY FK_E222D44B7E3C61F9');
        $this->addSql('ALTER TABLE qse_capa_action DROP FOREIGN KEY FK_9B2B70E57E3C61F9');
        $this->addSql('ALTER TABLE qse_capa_action DROP FOREIGN KEY FK_9B2B70E5FB9E1F4');
        $this->addSql('ALTER TABLE qse_capa_action DROP FOREIGN KEY FK_9B2B70E5A9896BBE');
        $this->addSql('ALTER TABLE qse_risk_matrix_entry DROP FOREIGN KEY FK_90B91307E3C61F9');
        $this->addSql('ALTER TABLE qse_risk_matrix_entry_capa_action DROP FOREIGN KEY FK_55AB1EC6BD02312C');
        $this->addSql('ALTER TABLE qse_risk_matrix_entry_capa_action DROP FOREIGN KEY FK_55AB1EC6B4860953');
        $this->addSql('DROP TABLE qse_audit');
        $this->addSql('DROP TABLE qse_audit_evaluation');
        $this->addSql('DROP TABLE qse_audit_finding');
        $this->addSql('DROP TABLE qse_audit_plan');
        $this->addSql('DROP TABLE qse_audit_requirement');
        $this->addSql('DROP TABLE qse_capa_action');
        $this->addSql('DROP TABLE qse_risk_matrix_entry');
        $this->addSql('DROP TABLE qse_risk_matrix_entry_capa_action');
    }
}
