<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'QSE audit: verdict ISO sur les évaluations + journal d’activité audit.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qse_audit_evaluation ADD verdict VARCHAR(32) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE qse_audit_evaluation SET verdict = CASE score
                WHEN 0 THEN 'not_applicable'
                WHEN 1 THEN 'minor_nc'
                WHEN 2 THEN 'observation'
                WHEN 3 THEN 'conform'
                ELSE NULL
            END
            SQL);

        $this->addSql('CREATE TABLE qse_audit_activity_log (id INT AUTO_INCREMENT NOT NULL, audit_id INT NOT NULL, audit_evaluation_id INT DEFAULT NULL, actor_id INT DEFAULT NULL, action VARCHAR(64) NOT NULL, payload JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_qse_audit_activity_audit (audit_id), INDEX idx_qse_audit_activity_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE qse_audit_activity_log ADD CONSTRAINT FK_AAL_AUDIT FOREIGN KEY (audit_id) REFERENCES qse_audit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE qse_audit_activity_log ADD CONSTRAINT FK_AAL_EVAL FOREIGN KEY (audit_evaluation_id) REFERENCES qse_audit_evaluation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE qse_audit_activity_log ADD CONSTRAINT FK_AAL_ACTOR FOREIGN KEY (actor_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qse_audit_activity_log DROP FOREIGN KEY FK_AAL_ACTOR');
        $this->addSql('ALTER TABLE qse_audit_activity_log DROP FOREIGN KEY FK_AAL_EVAL');
        $this->addSql('ALTER TABLE qse_audit_activity_log DROP FOREIGN KEY FK_AAL_AUDIT');
        $this->addSql('DROP TABLE qse_audit_activity_log');
        $this->addSql('ALTER TABLE qse_audit_evaluation DROP verdict');
    }
}
