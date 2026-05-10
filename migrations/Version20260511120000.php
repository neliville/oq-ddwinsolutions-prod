<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Référentiels audit : table qse_audit_standard, FK Audit / AuditRequirement / AuditPlan, unicité (standard, legacy_key).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE qse_audit_standard (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(120) NOT NULL, code VARCHAR(32) NOT NULL, version VARCHAR(32) DEFAULT NULL, description LONGTEXT DEFAULT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, display_order INT NOT NULL, visible TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX uniq_qse_audit_standard_code (code), INDEX IDX_7F8A7C7E7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE qse_audit_standard ADD CONSTRAINT FK_7F8A7C7E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE SET NULL');

        $this->addSql("INSERT INTO qse_audit_standard (name, code, version, description, active, display_order, visible, owner_id) VALUES
            ('ISO 9001 — Management de la qualité', 'iso_9001', '2015', 'Référentiel qualité', 1, 1, 1, NULL),
            ('ISO 14001 — Management environnemental', 'iso_14001', '2015', 'Référentiel environnement', 1, 2, 1, NULL),
            ('ISO 45001 — Santé et sécurité au travail', 'iso_45001', '2018', 'Référentiel SST', 1, 3, 1, NULL)");

        $this->addSql('ALTER TABLE qse_audit ADD audit_standard_id INT DEFAULT NULL');
        $this->addSql('UPDATE qse_audit a SET a.audit_standard_id = (SELECT s.id FROM qse_audit_standard s WHERE s.code = \'iso_9001\' LIMIT 1)');
        $this->addSql('ALTER TABLE qse_audit MODIFY audit_standard_id INT NOT NULL');
        $this->addSql('ALTER TABLE qse_audit ADD CONSTRAINT FK_5362844F5675F5E5 FOREIGN KEY (audit_standard_id) REFERENCES qse_audit_standard (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_5362844F5675F5E5 ON qse_audit (audit_standard_id)');
        $this->addSql('ALTER TABLE qse_audit ADD concerned_site VARCHAR(255) DEFAULT NULL, ADD concerned_process VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE qse_audit_requirement ADD audit_standard_id INT DEFAULT NULL');
        $this->addSql('UPDATE qse_audit_requirement r SET r.audit_standard_id = (SELECT s.id FROM qse_audit_standard s WHERE s.code = r.referential_type LIMIT 1)');
        $this->addSql('ALTER TABLE qse_audit_requirement DROP INDEX uniq_qse_audit_requirement_legacy');
        $this->addSql('ALTER TABLE qse_audit_requirement DROP INDEX idx_qse_audit_req_chapter_order');
        $this->addSql('ALTER TABLE qse_audit_requirement MODIFY audit_standard_id INT NOT NULL');
        $this->addSql('ALTER TABLE qse_audit_requirement ADD CONSTRAINT FK_7B576F775675F5E5 FOREIGN KEY (audit_standard_id) REFERENCES qse_audit_standard (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX uniq_qse_audit_req_standard_legacy ON qse_audit_requirement (audit_standard_id, legacy_key)');
        $this->addSql('CREATE INDEX idx_qse_audit_req_std_chapter ON qse_audit_requirement (audit_standard_id, chapter, display_order)');
        $this->addSql('ALTER TABLE qse_audit_requirement DROP COLUMN referential_type');

        $this->addSql('ALTER TABLE qse_audit_plan ADD audit_standard_id INT DEFAULT NULL');
        $this->addSql('UPDATE qse_audit_plan p SET p.audit_standard_id = (SELECT s.id FROM qse_audit_standard s WHERE s.code = p.referential LIMIT 1)');
        $this->addSql('ALTER TABLE qse_audit_plan MODIFY audit_standard_id INT NOT NULL');
        $this->addSql('ALTER TABLE qse_audit_plan ADD CONSTRAINT FK_E222D44B5675F5E5 FOREIGN KEY (audit_standard_id) REFERENCES qse_audit_standard (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_E222D44B5675F5E5 ON qse_audit_plan (audit_standard_id)');
        $this->addSql('ALTER TABLE qse_audit_plan DROP COLUMN referential');

        $this->addSql('ALTER TABLE qse_audit_requirement ADD sub_chapter VARCHAR(160) DEFAULT NULL, ADD business_link VARCHAR(500) DEFAULT NULL, ADD pdca_phase VARCHAR(16) DEFAULT NULL, ADD source_version VARCHAR(64) DEFAULT NULL, ADD requirement_updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qse_audit_requirement DROP COLUMN sub_chapter, DROP COLUMN business_link, DROP COLUMN pdca_phase, DROP COLUMN source_version, DROP COLUMN requirement_updated_at');

        $this->addSql('ALTER TABLE qse_audit_plan DROP FOREIGN KEY FK_E222D44B5675F5E5');
        $this->addSql('DROP INDEX IDX_E222D44B5675F5E5 ON qse_audit_plan');
        $this->addSql('ALTER TABLE qse_audit_plan ADD referential VARCHAR(32) NOT NULL');
        $this->addSql('UPDATE qse_audit_plan p INNER JOIN qse_audit_standard s ON s.id = p.audit_standard_id SET p.referential = s.code');
        $this->addSql('ALTER TABLE qse_audit_plan DROP COLUMN audit_standard_id');

        $this->addSql('ALTER TABLE qse_audit_requirement DROP FOREIGN KEY FK_7B576F775675F5E5');
        $this->addSql('DROP INDEX uniq_qse_audit_req_standard_legacy ON qse_audit_requirement');
        $this->addSql('DROP INDEX idx_qse_audit_req_std_chapter ON qse_audit_requirement');
        $this->addSql('ALTER TABLE qse_audit_requirement ADD referential_type VARCHAR(32) NOT NULL');
        $this->addSql('UPDATE qse_audit_requirement r INNER JOIN qse_audit_standard s ON s.id = r.audit_standard_id SET r.referential_type = s.code');
        $this->addSql('ALTER TABLE qse_audit_requirement DROP COLUMN audit_standard_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_qse_audit_requirement_legacy ON qse_audit_requirement (legacy_key)');
        $this->addSql('CREATE INDEX idx_qse_audit_req_chapter_order ON qse_audit_requirement (chapter, display_order)');

        $this->addSql('ALTER TABLE qse_audit DROP FOREIGN KEY FK_5362844F5675F5E5');
        $this->addSql('DROP INDEX IDX_5362844F5675F5E5 ON qse_audit');
        $this->addSql('ALTER TABLE qse_audit DROP COLUMN concerned_site, DROP COLUMN concerned_process');
        $this->addSql('ALTER TABLE qse_audit DROP COLUMN audit_standard_id');

        $this->addSql('DROP TABLE qse_audit_standard');
    }
}
