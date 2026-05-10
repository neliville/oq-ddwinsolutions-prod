<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CAPA : table qse_capa_origin, FK origin_id, statuts FR, statuts audit/plan/risque FR.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE qse_capa_origin (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(120) NOT NULL, kind VARCHAR(16) NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, owner_id INT DEFAULT NULL, INDEX idx_qse_capa_origin_owner (owner_id), UNIQUE INDEX uniq_qse_capa_origin_slug (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE qse_capa_origin ADD CONSTRAINT FK_QSE_CAPA_ORIGIN_USER FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO qse_capa_origin (name, slug, kind, active, owner_id) VALUES
            ('Ishikawa', 'ishikawa', 'system', 1, NULL),
            ('5 Pourquoi', 'cinq-pourquoi', 'system', 1, NULL),
            ('AMDEC', 'amdec', 'system', 1, NULL),
            ('8D', '8d', 'system', 1, NULL),
            ('QQOQCCP', 'qqoqccp', 'system', 1, NULL),
            ('Pareto', 'pareto', 'system', 1, NULL),
            ('Audit interne', 'audit-interne', 'system', 1, NULL),
            ('Matrice des risques', 'matrice-risques', 'system', 1, NULL)");

        $this->addSql('ALTER TABLE qse_capa_action ADD origin_id INT DEFAULT NULL');
        $this->addSql('UPDATE qse_capa_action SET metadata = JSON_MERGE_PATCH(COALESCE(metadata, \'{}\'), JSON_OBJECT(\'_legacy_origin\', origin)) WHERE origin IN (\'incident\',\'other\')');
        $this->addSql('UPDATE qse_capa_action c INNER JOIN qse_capa_origin o ON o.slug = (CASE c.origin WHEN \'audit\' THEN \'audit-interne\' WHEN \'ishikawa\' THEN \'ishikawa\' WHEN \'five_why\' THEN \'cinq-pourquoi\' WHEN \'amdec\' THEN \'amdec\' WHEN \'eight_d\' THEN \'8d\' WHEN \'qqoqccp\' THEN \'qqoqccp\' WHEN \'pareto\' THEN \'pareto\' WHEN \'risk\' THEN \'matrice-risques\' WHEN \'incident\' THEN \'audit-interne\' WHEN \'other\' THEN \'audit-interne\' ELSE \'audit-interne\' END) SET c.origin_id = o.id');
        $this->addSql('ALTER TABLE qse_capa_action DROP COLUMN origin');
        $this->addSql('ALTER TABLE qse_capa_action MODIFY origin_id INT NOT NULL');
        $this->addSql('ALTER TABLE qse_capa_action ADD CONSTRAINT FK_9B2B70E5F1295ABC FOREIGN KEY (origin_id) REFERENCES qse_capa_origin (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE qse_capa_action MODIFY status VARCHAR(40) NOT NULL');
        $this->addSql("UPDATE qse_capa_action SET status = CASE status WHEN 'draft' THEN 'brouillon' WHEN 'validated' THEN 'validee' WHEN 'in_progress' THEN 'en_cours' WHEN 'pending_verification' THEN 'en_attente_de_verification' WHEN 'closed' THEN 'cloturee' WHEN 'reopened' THEN 'reouverte' ELSE status END");

        $this->addSql("UPDATE qse_audit SET status = CASE status WHEN 'draft' THEN 'brouillon' WHEN 'in_progress' THEN 'en_cours' WHEN 'completed' THEN 'termine' WHEN 'archived' THEN 'archive' ELSE status END");

        $this->addSql("UPDATE qse_audit_plan SET status = CASE status WHEN 'draft' THEN 'brouillon' WHEN 'planned' THEN 'planifie' WHEN 'in_progress' THEN 'en_cours' WHEN 'completed' THEN 'termine' WHEN 'archived' THEN 'archive' ELSE status END");

        $this->addSql("UPDATE qse_risk_matrix_entry SET status = CASE status WHEN 'draft' THEN 'identifie' WHEN 'active' THEN 'sous_surveillance' WHEN 'mitigated' THEN 'maitrise' WHEN 'closed' THEN 'cloture' ELSE status END");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qse_capa_action DROP FOREIGN KEY FK_9B2B70E5F1295ABC');
        $this->addSql('ALTER TABLE qse_capa_action ADD origin VARCHAR(32) NOT NULL DEFAULT \'other\'');
        $this->addSql('UPDATE qse_capa_action c INNER JOIN qse_capa_origin o ON o.id = c.origin_id SET c.origin = CASE o.slug WHEN \'audit-interne\' THEN \'audit\' WHEN \'ishikawa\' THEN \'ishikawa\' WHEN \'cinq-pourquoi\' THEN \'five_why\' WHEN \'amdec\' THEN \'amdec\' WHEN \'8d\' THEN \'eight_d\' WHEN \'qqoqccp\' THEN \'qqoqccp\' WHEN \'pareto\' THEN \'pareto\' WHEN \'matrice-risques\' THEN \'risk\' ELSE \'other\' END');
        $this->addSql('ALTER TABLE qse_capa_action DROP COLUMN origin_id');
        $this->addSql('DROP TABLE qse_capa_origin');

        $this->addSql("UPDATE qse_capa_action SET status = CASE status WHEN 'brouillon' THEN 'draft' WHEN 'a_valider' THEN 'draft' WHEN 'validee' THEN 'validated' WHEN 'en_cours' THEN 'in_progress' WHEN 'en_attente_de_verification' THEN 'pending_verification' WHEN 'cloturee' THEN 'closed' WHEN 'reouverte' THEN 'reopened' WHEN 'annulee' THEN 'closed' ELSE status END");
        $this->addSql('ALTER TABLE qse_capa_action MODIFY status VARCHAR(32) NOT NULL');

        $this->addSql("UPDATE qse_audit SET status = CASE status WHEN 'brouillon' THEN 'draft' WHEN 'prepare' THEN 'draft' WHEN 'en_cours' THEN 'in_progress' WHEN 'termine' THEN 'completed' WHEN 'valide' THEN 'completed' WHEN 'archive' THEN 'archived' ELSE status END");
        $this->addSql("UPDATE qse_audit_plan SET status = CASE status WHEN 'brouillon' THEN 'draft' WHEN 'planifie' THEN 'planned' WHEN 'programme' THEN 'planned' WHEN 'en_cours' THEN 'in_progress' WHEN 'termine' THEN 'completed' WHEN 'archive' THEN 'archived' ELSE status END");
        $this->addSql("UPDATE qse_risk_matrix_entry SET status = CASE status WHEN 'identifie' THEN 'draft' WHEN 'en_analyse' THEN 'active' WHEN 'maitrise' THEN 'mitigated' WHEN 'sous_surveillance' THEN 'active' WHEN 'critique' THEN 'active' WHEN 'cloture' THEN 'closed' ELSE status END");
    }
}
