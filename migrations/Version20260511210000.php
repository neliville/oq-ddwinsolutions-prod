<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Onboarding profil : colonnes, acquisition, fonction ; cartographie tailles d’entreprise ;
 * marquer les préférences existantes comme onboarding complété.
 */
final class Version20260511210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User preferences: onboarding (profile_onboarding_completed, job_function, acquisition_source), company_size mapping.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $cols = $sm->listTableColumns('user_preferences');
        $names = array_map(static fn ($c) => $c->getName(), $cols);

        $addedProfileCompleted = false;
        if (!in_array('profile_onboarding_completed', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences ADD profile_onboarding_completed TINYINT(1) DEFAULT 0 NOT NULL');
            $addedProfileCompleted = true;
        }
        if (!in_array('job_function', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences ADD job_function VARCHAR(32) DEFAULT NULL');
        }
        if (!in_array('acquisition_source', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences ADD acquisition_source VARCHAR(32) DEFAULT NULL');
        }

        if ($addedProfileCompleted) {
            $this->addSql('UPDATE user_preferences SET profile_onboarding_completed = 1');
        }

        $this->addSql("UPDATE user_preferences SET company_size = 'p2_10' WHERE company_size = 'tpe'");
        $this->addSql("UPDATE user_preferences SET company_size = 'p11_50' WHERE company_size = 'pme'");
        $this->addSql("UPDATE user_preferences SET company_size = 'p51_250' WHERE company_size = 'eti'");
        $this->addSql("UPDATE user_preferences SET company_size = 'p1000_plus' WHERE company_size = 'large'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE user_preferences SET company_size = 'tpe' WHERE company_size = 'p2_10'");
        $this->addSql("UPDATE user_preferences SET company_size = 'pme' WHERE company_size = 'p11_50'");
        $this->addSql("UPDATE user_preferences SET company_size = 'eti' WHERE company_size = 'p51_250'");
        $this->addSql("UPDATE user_preferences SET company_size = 'large' WHERE company_size = 'p1000_plus'");

        $sm = $this->connection->createSchemaManager();
        $cols = $sm->listTableColumns('user_preferences');
        $names = array_map(static fn ($c) => $c->getName(), $cols);

        if (in_array('acquisition_source', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences DROP acquisition_source');
        }
        if (in_array('job_function', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences DROP job_function');
        }
        if (in_array('profile_onboarding_completed', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences DROP profile_onboarding_completed');
        }
    }
}
