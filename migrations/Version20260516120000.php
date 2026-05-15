<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260516120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Slots homepage éditables depuis l’admin (hero, FAQ intro, meta description).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE homepage_content_slot (id INT AUTO_INCREMENT NOT NULL, slot_key VARCHAR(80) NOT NULL, label VARCHAR(180) NOT NULL, content LONGTEXT NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_homepage_slot_key (slot_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ([
            ['hero_title', 'Hero — Titre principal'],
            ['hero_subtitle', 'Hero — Sous-titre'],
            ['hero_usp_note', 'Hero — Note sous les CTA'],
            ['faq_section_title', 'FAQ — Titre de section'],
            ['faq_section_subtitle', 'FAQ — Sous-titre de section'],
            ['seo_home_meta_description', 'SEO — Meta description (homepage)'],
        ] as [$key, $label]) {
            $this->addSql(
                'INSERT INTO homepage_content_slot (slot_key, label, content, active, updated_at) VALUES (?, ?, ?, 1, ?)',
                [$key, $label, '', $now]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE homepage_content_slot');
    }
}
