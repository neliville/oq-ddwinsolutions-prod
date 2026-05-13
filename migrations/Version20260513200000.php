<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Onboarding activation : persistance activation_state + origine CAPA système onboarding-cockpit.
 */
final class Version20260513200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'user_preferences.activation_state JSON + origine CAPA système onboarding-cockpit.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $cols = $sm->listTableColumns('user_preferences');
        $names = array_map(static fn ($c) => $c->getName(), $cols);

        if (!in_array('activation_state', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences ADD activation_state JSON DEFAULT NULL');
        }

        $this->addSql("INSERT INTO qse_capa_origin (name, slug, kind, active, owner_id)
            SELECT 'Onboarding cockpit', 'onboarding-cockpit', 'system', 1, NULL
            WHERE NOT EXISTS (
                SELECT 1 FROM qse_capa_origin WHERE slug = 'onboarding-cockpit' AND owner_id IS NULL
            )");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE qse_capa_action c
            INNER JOIN qse_capa_origin o_old ON c.origin_id = o_old.id
            INNER JOIN qse_capa_origin o_new ON o_new.slug = 'audit-interne' AND o_new.owner_id IS NULL
            SET c.origin_id = o_new.id
            WHERE o_old.slug = 'onboarding-cockpit' AND o_old.owner_id IS NULL");

        $this->addSql("DELETE FROM qse_capa_origin WHERE slug = 'onboarding-cockpit' AND owner_id IS NULL");

        $sm = $this->connection->createSchemaManager();
        $cols = $sm->listTableColumns('user_preferences');
        $names = array_map(static fn ($c) => $c->getName(), $cols);

        if (in_array('activation_state', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences DROP activation_state');
        }
    }
}
