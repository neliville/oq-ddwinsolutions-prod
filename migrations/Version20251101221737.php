<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251101221737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_blogpost_published_at ON blog_post (published_at)');
        $this->addSql('CREATE INDEX idx_blogpost_featured ON blog_post (featured)');
        $this->addSql('CREATE INDEX idx_blogpost_views ON blog_post (views)');
        $this->addSql('CREATE INDEX idx_blogpost_created_at ON blog_post (created_at)');
        $this->addSql('CREATE INDEX idx_contactmessage_read ON contact_message (`read`)');
        $this->addSql('CREATE INDEX idx_contactmessage_replied ON contact_message (replied)');
        $this->addSql('CREATE INDEX idx_contactmessage_created_at ON contact_message (created_at)');
        $this->addSql('CREATE INDEX idx_newsletter_active ON newsletter_subscriber (active)');
        $this->addSql('CREATE INDEX idx_newsletter_subscribed_at ON newsletter_subscriber (subscribed_at)');
        $this->addSql('CREATE INDEX idx_record_type ON record (type)');
        $this->addSql('CREATE INDEX idx_record_created_at ON record (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_record_type ON record');
        $this->addSql('DROP INDEX idx_record_created_at ON record');
        $this->addSql('DROP INDEX idx_blogpost_published_at ON blog_post');
        $this->addSql('DROP INDEX idx_blogpost_featured ON blog_post');
        $this->addSql('DROP INDEX idx_blogpost_views ON blog_post');
        $this->addSql('DROP INDEX idx_blogpost_created_at ON blog_post');
        $this->addSql('DROP INDEX idx_contactmessage_read ON contact_message');
        $this->addSql('DROP INDEX idx_contactmessage_replied ON contact_message');
        $this->addSql('DROP INDEX idx_contactmessage_created_at ON contact_message');
        $this->addSql('DROP INDEX idx_newsletter_active ON newsletter_subscriber');
        $this->addSql('DROP INDEX idx_newsletter_subscribed_at ON newsletter_subscriber');
    }
}
