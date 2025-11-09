<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107192115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cms_page (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, slug VARCHAR(180) NOT NULL, content LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_cms_page_slug (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $now = new DateTimeImmutable();

        $privacyContent = <<< 'MD'
# Politique de confidentialité

Nous décrivons dans cette politique la manière dont OUTILS-QUALITÉ collecte et traite vos données.

## Données collectées

- Adresse e-mail et informations de compte lorsque vous utilisez nos outils ;
- Statistiques anonymisées pour améliorer l'application ;
- Messages envoyés via les formulaires de contact.

## Finalités

Les données sont uniquement utilisées pour fournir les services, assurer un suivi de sécurité et vous informer des mises à jour.

## Conservation

Les journaux techniques sont conservés 12 mois maximum. Vous pouvez demander la suppression de vos données à tout moment.

## Contact

Pour toute question, écrivez à [contact@outils-qualite.com](mailto:contact@outils-qualite.com).
MD;

        $termsContent = <<< 'MD'
# Conditions d'utilisation

Les présentes conditions encadrent l'accès et l'usage des outils gratuits proposés par OUTILS-QUALITÉ.

## Accès au service

Les outils sont mis à disposition « en l'état ». L'utilisateur doit vérifier que les résultats obtenus sont adaptés à son contexte.

## Responsabilités

OUTILS-QUALITÉ ne saurait être tenue responsable des conséquences liées à un usage inapproprié ou à l'absence d'analyse humaine.

## Propriété

Les contenus et interfaces restent la propriété de DDWin Solutions. Toute reproduction non autorisée est interdite.

## Modifications

Les conditions peuvent être modifiées à tout moment. La date de mise à jour est indiquée sur la page.
MD;

        $legalContent = <<< 'MD'
# Mentions légales

Conformément à la loi LCEN du 21 juin 2004.

## Éditeur du site

DDWin Solutions — contact@outils-qualite.com.

## Hébergeur

Microsoft Azure (Microsoft Ireland Operations Ltd., One Microsoft Place, Dublin 18, Irlande).

## Propriété intellectuelle

Les éléments du site (textes, visuels, code) sont protégés par le droit d'auteur. Toute reproduction doit faire l'objet d'une autorisation écrite.

## Contact

Pour toute question, contactez [contact@outils-qualite.com](mailto:contact@outils-qualite.com).
MD;

        $this->addSql(
            'INSERT INTO cms_page (title, slug, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [
                'Politique de confidentialité',
                'privacy-policy',
                $privacyContent,
                $now,
                $now,
            ],
            [
                Types::STRING,
                Types::STRING,
                Types::TEXT,
                Types::DATETIME_IMMUTABLE,
                Types::DATETIME_IMMUTABLE,
            ]
        );

        $this->addSql(
            'INSERT INTO cms_page (title, slug, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [
                'Conditions d\'utilisation',
                'terms-of-use',
                $termsContent,
                $now,
                $now,
            ],
            [
                Types::STRING,
                Types::STRING,
                Types::TEXT,
                Types::DATETIME_IMMUTABLE,
                Types::DATETIME_IMMUTABLE,
            ]
        );

        $this->addSql(
            'INSERT INTO cms_page (title, slug, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [
                'Mentions légales',
                'legal-notice',
                $legalContent,
                $now,
                $now,
            ],
            [
                Types::STRING,
                Types::STRING,
                Types::TEXT,
                Types::DATETIME_IMMUTABLE,
                Types::DATETIME_IMMUTABLE,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE cms_page');
    }
}
