<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Dashboard\DashboardLayout;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renomme dashboard_visibility → dashboard_layout et normalise le JSON versionné.
 */
final class Version20260515180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme user_preferences.dashboard_visibility en dashboard_layout et migre vers le format widgets v1.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_preferences CHANGE dashboard_visibility dashboard_layout JSON DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, dashboard_layout FROM user_preferences WHERE dashboard_layout IS NOT NULL',
        );

        foreach ($rows as $row) {
            $raw = json_decode((string) $row['dashboard_layout'], true);
            if (!is_array($raw)) {
                continue;
            }

            $layout = DashboardLayout::fromStored($raw);
            $this->connection->update(
                'user_preferences',
                ['dashboard_layout' => json_encode($layout->toStorage(), JSON_THROW_ON_ERROR)],
                ['id' => $row['id']],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, dashboard_layout FROM user_preferences WHERE dashboard_layout IS NOT NULL',
        );

        foreach ($rows as $row) {
            $raw = json_decode((string) $row['dashboard_layout'], true);
            if (!is_array($raw)) {
                continue;
            }

            $legacy = DashboardLayout::fromStored($raw)->toLegacyVisibilityMap();
            unset($legacy['kpi_stats'], $legacy['kpi_ai']);
            $this->connection->update(
                'user_preferences',
                ['dashboard_layout' => json_encode($legacy, JSON_THROW_ON_ERROR)],
                ['id' => $row['id']],
            );
        }

        $this->addSql('ALTER TABLE user_preferences CHANGE dashboard_layout dashboard_visibility JSON DEFAULT NULL');
    }
}
