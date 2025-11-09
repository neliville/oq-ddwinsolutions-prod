<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251108113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ishikawa_share_visit table to track public link usage';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('ishikawa_share_visit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('share_id', 'integer');
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('visited_at', 'datetime_immutable');
        $table->addColumn('ip_address', 'string', ['length' => 64, 'notnull' => false]);
        $table->addColumn('user_agent', 'text', ['notnull' => false]);
        $table->addColumn('referer', 'text', ['notnull' => false]);
        $table->addColumn('session_id', 'string', ['length' => 128, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['share_id'], 'idx_share_visit_share');
        $table->addIndex(['user_id'], 'idx_share_visit_user');
        $table->addForeignKeyConstraint('ishikawa_share', ['share_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_share_visit_share');
        $table->addForeignKeyConstraint('user', ['user_id'], ['id'], ['onDelete' => 'SET NULL'], 'fk_share_visit_user');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('ishikawa_share_visit');
    }
}


