<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260113091357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'larger fields';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gift ALTER url TYPE VARCHAR(1024)');
        $this->addSql('ALTER TABLE gift ALTER image_url TYPE VARCHAR(1024)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gift ALTER url TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE gift ALTER image_url TYPE VARCHAR(500)');
    }
}
