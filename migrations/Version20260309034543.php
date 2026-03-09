<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309034543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accident_media (id INT AUTO_INCREMENT NOT NULL, accident_id INT NOT NULL, created_by_id INT NOT NULL, type VARCHAR(20) NOT NULL, original_name VARCHAR(255) NOT NULL, file_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, size INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_456D7D2E16D8554C (accident_id), INDEX IDX_456D7D2EB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accident_media ADD CONSTRAINT FK_456D7D2E16D8554C FOREIGN KEY (accident_id) REFERENCES accident (id)');
        $this->addSql('ALTER TABLE accident_media ADD CONSTRAINT FK_456D7D2EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accident_media DROP FOREIGN KEY FK_456D7D2E16D8554C');
        $this->addSql('ALTER TABLE accident_media DROP FOREIGN KEY FK_456D7D2EB03A8386');
        $this->addSql('DROP TABLE accident_media');
    }
}
