<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251108110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ishikawa_share table for public sharing links';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('ishikawa_share');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('token', 'string', ['length' => 64]);
        $table->addColumn('created_at', 'datetime_immutable');
        $table->addColumn('expires_at', 'datetime_immutable');
        $table->addColumn('analysis_id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['token'], 'uniq_ishikawa_share_token');
        $table->addForeignKeyConstraint('ishikawa_analysis', ['analysis_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_ishikawa_share_analysis');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('ishikawa_share');
    }
}


