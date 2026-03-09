<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309045701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accident ADD validated_by_brigade_id INT DEFAULT NULL, ADD validated_by_id INT DEFAULT NULL, ADD date_validation_brigade DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE accident ADD CONSTRAINT FK_8F31DB6FA44A7AAC FOREIGN KEY (validated_by_brigade_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE accident ADD CONSTRAINT FK_8F31DB6FC69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_8F31DB6FA44A7AAC ON accident (validated_by_brigade_id)');
        $this->addSql('CREATE INDEX IDX_8F31DB6FC69DE5E5 ON accident (validated_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accident DROP FOREIGN KEY FK_8F31DB6FA44A7AAC');
        $this->addSql('ALTER TABLE accident DROP FOREIGN KEY FK_8F31DB6FC69DE5E5');
        $this->addSql('DROP INDEX IDX_8F31DB6FA44A7AAC ON accident');
        $this->addSql('DROP INDEX IDX_8F31DB6FC69DE5E5 ON accident');
        $this->addSql('ALTER TABLE accident DROP validated_by_brigade_id, DROP validated_by_id, DROP date_validation_brigade');
    }
}
