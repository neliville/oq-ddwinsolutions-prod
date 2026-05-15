<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table homepage_testimonial + données initiales témoignages homepage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE homepage_testimonial (
            id INT AUTO_INCREMENT NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            job_title VARCHAR(255) NOT NULL,
            company VARCHAR(255) NOT NULL,
            quote LONGTEXT NOT NULL,
            rating INT NOT NULL,
            initials VARCHAR(10) NOT NULL,
            display_order INT NOT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_homepage_testimonial_active_order (is_active, display_order),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->addSql("INSERT INTO homepage_testimonial (full_name, job_title, company, quote, rating, initials, display_order, is_active, created_at) VALUES
            ('Claire D.', 'Responsable QSE', 'Industrie manufacturière', 'L''Ishikawa en 10 minutes avant ma revue d''écart : simple, lisible, et l''export PDF part directement dans le dossier audit.', 5, 'C', 1, 1, '{$now}'),
            ('Marc L.', 'Chef de projet QSE', 'PME services', 'On commence par les outils gratuits, puis le cockpit quand il faut relier CAPA, risques et audits dans une même vue.', 5, 'M', 2, 1, '{$now}')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE homepage_testimonial');
    }
}
