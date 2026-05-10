<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Collaboration V1 : invitations, partages sécurisés, état UI suggestions.
 */
final class Version20260512140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User invitation, shared access (audit/capa), user_preferences.collaboration_ui_state.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_invitation (
            id INT AUTO_INCREMENT NOT NULL,
            owner_id INT NOT NULL,
            email VARCHAR(180) NOT NULL,
            first_name VARCHAR(120) DEFAULT NULL,
            role VARCHAR(32) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            status VARCHAR(32) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            invitation_type VARCHAR(32) NOT NULL,
            context JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            accepted_by_id INT DEFAULT NULL,
            INDEX idx_user_invitation_owner_email_status (owner_id, email, status),
            INDEX idx_user_invitation_expires_at (expires_at),
            INDEX IDX_USER_INVITATION_OWNER (owner_id),
            INDEX IDX_USER_INVITATION_ACCEPTED_BY (accepted_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_invitation ADD CONSTRAINT FK_UI_OWNER FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_invitation ADD CONSTRAINT FK_UI_ACCEPTED_BY FOREIGN KEY (accepted_by_id) REFERENCES `user` (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE shared_access (
            id INT AUTO_INCREMENT NOT NULL,
            owner_id INT NOT NULL,
            target_type VARCHAR(16) NOT NULL,
            target_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            access_level VARCHAR(32) NOT NULL,
            invited_email VARCHAR(180) DEFAULT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            status VARCHAR(16) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            revoked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_shared_access_token_hash (token_hash),
            INDEX idx_shared_access_owner_target (owner_id, target_type, target_id, status),
            INDEX IDX_SHARED_ACCESS_OWNER (owner_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shared_access ADD CONSTRAINT FK_SA_OWNER FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $sm = $this->connection->createSchemaManager();
        $cols = $sm->listTableColumns('user_preferences');
        $names = array_map(static fn ($c) => $c->getName(), $cols);
        if (!in_array('collaboration_ui_state', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences ADD collaboration_ui_state JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $cols = $sm->listTableColumns('user_preferences');
        $names = array_map(static fn ($c) => $c->getName(), $cols);
        if (in_array('collaboration_ui_state', $names, true)) {
            $this->addSql('ALTER TABLE user_preferences DROP collaboration_ui_state');
        }

        $this->addSql('ALTER TABLE shared_access DROP FOREIGN KEY FK_SA_OWNER');
        $this->addSql('DROP TABLE shared_access');

        $this->addSql('ALTER TABLE user_invitation DROP FOREIGN KEY FK_UI_OWNER');
        $this->addSql('ALTER TABLE user_invitation DROP FOREIGN KEY FK_UI_ACCEPTED_BY');
        $this->addSql('DROP TABLE user_invitation');
    }
}
