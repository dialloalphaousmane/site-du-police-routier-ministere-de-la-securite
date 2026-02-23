<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210021152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE configuration (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, cle VARCHAR(255) NOT NULL, valeur LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, categorie VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, actif TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_A5E2A5D741401D17 (cle), INDEX IDX_A5E2A5D7B03A8386 (created_by_id), INDEX IDX_A5E2A5D7896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, entity VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, ip_address VARCHAR(255) NOT NULL, user_agent VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', level VARCHAR(50) NOT NULL, old_value LONGTEXT DEFAULT NULL, new_value LONGTEXT DEFAULT NULL, INDEX IDX_8F3F68C5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, titre VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, lu TINYINT(1) NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rapport (id INT AUTO_INCREMENT NOT NULL, auteur_id INT NOT NULL, validateur_id INT DEFAULT NULL, region_id INT DEFAULT NULL, brigade_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_validation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', observations LONGTEXT DEFAULT NULL, actif TINYINT(1) NOT NULL, INDEX IDX_BE34A09C60BB6FE6 (auteur_id), INDEX IDX_BE34A09CE57AEF2F (validateur_id), INDEX IDX_BE34A09C98260155 (region_id), INDEX IDX_BE34A09C539A88F2 (brigade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE configuration ADD CONSTRAINT FK_A5E2A5D7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE configuration ADD CONSTRAINT FK_A5E2A5D7896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE log ADD CONSTRAINT FK_8F3F68C5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE rapport ADD CONSTRAINT FK_BE34A09C60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE rapport ADD CONSTRAINT FK_BE34A09CE57AEF2F FOREIGN KEY (validateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE rapport ADD CONSTRAINT FK_BE34A09C98260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE rapport ADD CONSTRAINT FK_BE34A09C539A88F2 FOREIGN KEY (brigade_id) REFERENCES brigade (id)');
        $this->addSql('ALTER TABLE brigade ADD chef VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD telephone VARCHAR(50) DEFAULT NULL, ADD adresse LONGTEXT DEFAULT NULL, ADD zone_couverture LONGTEXT DEFAULT NULL, ADD actif TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE controle ADD validated_by_id INT DEFAULT NULL, ADD statut VARCHAR(50) DEFAULT NULL, ADD date_validation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE controle ADD CONSTRAINT FK_E39396EC69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_E39396EC69DE5E5 ON controle (validated_by_id)');
        $this->addSql('ALTER TABLE region ADD directeur VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD telephone VARCHAR(50) DEFAULT NULL, ADD adresse LONGTEXT DEFAULT NULL, ADD actif TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649539A88F2');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64998260155');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649D60322AC');
        $this->addSql('DROP INDEX IDX_8D93D649D60322AC ON user');
        $this->addSql('DROP INDEX IDX_8D93D64998260155 ON user');
        $this->addSql('DROP INDEX IDX_8D93D649539A88F2 ON user');
        $this->addSql('ALTER TABLE user DROP role_id, DROP region_id, DROP brigade_id');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE configuration DROP FOREIGN KEY FK_A5E2A5D7B03A8386');
        $this->addSql('ALTER TABLE configuration DROP FOREIGN KEY FK_A5E2A5D7896DBBDE');
        $this->addSql('ALTER TABLE log DROP FOREIGN KEY FK_8F3F68C5A76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE rapport DROP FOREIGN KEY FK_BE34A09C60BB6FE6');
        $this->addSql('ALTER TABLE rapport DROP FOREIGN KEY FK_BE34A09CE57AEF2F');
        $this->addSql('ALTER TABLE rapport DROP FOREIGN KEY FK_BE34A09C98260155');
        $this->addSql('ALTER TABLE rapport DROP FOREIGN KEY FK_BE34A09C539A88F2');
        $this->addSql('DROP TABLE configuration');
        $this->addSql('DROP TABLE log');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE rapport');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('ALTER TABLE brigade DROP chef, DROP email, DROP telephone, DROP adresse, DROP zone_couverture, DROP actif');
        $this->addSql('ALTER TABLE `user` ADD role_id INT NOT NULL, ADD region_id INT DEFAULT NULL, ADD brigade_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649539A88F2 FOREIGN KEY (brigade_id) REFERENCES brigade (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64998260155 FOREIGN KEY (region_id) REFERENCES region (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8D93D649D60322AC ON `user` (role_id)');
        $this->addSql('CREATE INDEX IDX_8D93D64998260155 ON `user` (region_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649539A88F2 ON `user` (brigade_id)');
        $this->addSql('ALTER TABLE controle DROP FOREIGN KEY FK_E39396EC69DE5E5');
        $this->addSql('DROP INDEX IDX_E39396EC69DE5E5 ON controle');
        $this->addSql('ALTER TABLE controle DROP validated_by_id, DROP statut, DROP date_validation');
        $this->addSql('ALTER TABLE region DROP directeur, DROP email, DROP telephone, DROP adresse, DROP actif');
    }
}
