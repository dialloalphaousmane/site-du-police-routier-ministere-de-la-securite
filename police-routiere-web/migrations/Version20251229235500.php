<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251229235500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agent (id INT AUTO_INCREMENT NOT NULL, region_id INT NOT NULL, brigade_id INT NOT NULL, matricule VARCHAR(50) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, grade VARCHAR(255) NOT NULL, date_embauche DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_actif TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_268B9C9D12B2DC9C (matricule), INDEX IDX_268B9C9D98260155 (region_id), INDEX IDX_268B9C9D539A88F2 (brigade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE amende (id INT AUTO_INCREMENT NOT NULL, infraction_id INT NOT NULL, reference VARCHAR(50) NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, montant_paye NUMERIC(10, 2) NOT NULL, statut VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_paiement DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_613014CFAEA34913 (reference), INDEX IDX_613014CF7697C467 (infraction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(255) NOT NULL, entite VARCHAR(255) NOT NULL, entite_id INT DEFAULT NULL, ancienne_valeur LONGTEXT DEFAULT NULL, nouvelle_valeur LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', adresse_ip VARCHAR(255) DEFAULT NULL, INDEX IDX_F6E1C0F5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE brigade (id INT AUTO_INCREMENT NOT NULL, region_id INT NOT NULL, code VARCHAR(255) NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, localite VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_80206E9D77153098 (code), INDEX IDX_80206E9D98260155 (region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE controle (id INT AUTO_INCREMENT NOT NULL, agent_id INT NOT NULL, brigade_id INT NOT NULL, date_controle DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', lieu_controle VARCHAR(255) NOT NULL, marque_vehicule VARCHAR(255) NOT NULL, immatriculation VARCHAR(50) NOT NULL, nom_conducteur VARCHAR(255) NOT NULL, prenom_conducteur VARCHAR(255) NOT NULL, no_conducteur VARCHAR(50) NOT NULL, observation VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E39396E3414710B (agent_id), INDEX IDX_E39396E539A88F2 (brigade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE infraction (id INT AUTO_INCREMENT NOT NULL, controle_id INT NOT NULL, reference VARCHAR(50) NOT NULL, libelle VARCHAR(255) NOT NULL, code VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, montant_amende NUMERIC(10, 2) NOT NULL, statut VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C1A458F5AEA34913 (reference), INDEX IDX_C1A458F5758926A6 (controle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, amende_id INT NOT NULL, reference VARCHAR(50) NOT NULL, montant NUMERIC(10, 2) NOT NULL, date_paiement DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', mode_paiement VARCHAR(100) NOT NULL, numero_transaction VARCHAR(255) DEFAULT NULL, observation LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_B1DC7A1EAEA34913 (reference), INDEX IDX_B1DC7A1EF4C5F3D5 (amende_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE region (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F62F17677153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_57698A6A77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, role_id INT NOT NULL, region_id INT DEFAULT NULL, brigade_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649D60322AC (role_id), INDEX IDX_8D93D64998260155 (region_id), INDEX IDX_8D93D649539A88F2 (brigade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9D98260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9D539A88F2 FOREIGN KEY (brigade_id) REFERENCES brigade (id)');
        $this->addSql('ALTER TABLE amende ADD CONSTRAINT FK_613014CF7697C467 FOREIGN KEY (infraction_id) REFERENCES infraction (id)');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE brigade ADD CONSTRAINT FK_80206E9D98260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE controle ADD CONSTRAINT FK_E39396E3414710B FOREIGN KEY (agent_id) REFERENCES agent (id)');
        $this->addSql('ALTER TABLE controle ADD CONSTRAINT FK_E39396E539A88F2 FOREIGN KEY (brigade_id) REFERENCES brigade (id)');
        $this->addSql('ALTER TABLE infraction ADD CONSTRAINT FK_C1A458F5758926A6 FOREIGN KEY (controle_id) REFERENCES controle (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1EF4C5F3D5 FOREIGN KEY (amende_id) REFERENCES amende (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64998260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649539A88F2 FOREIGN KEY (brigade_id) REFERENCES brigade (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agent DROP FOREIGN KEY FK_268B9C9D98260155');
        $this->addSql('ALTER TABLE agent DROP FOREIGN KEY FK_268B9C9D539A88F2');
        $this->addSql('ALTER TABLE amende DROP FOREIGN KEY FK_613014CF7697C467');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5A76ED395');
        $this->addSql('ALTER TABLE brigade DROP FOREIGN KEY FK_80206E9D98260155');
        $this->addSql('ALTER TABLE controle DROP FOREIGN KEY FK_E39396E3414710B');
        $this->addSql('ALTER TABLE controle DROP FOREIGN KEY FK_E39396E539A88F2');
        $this->addSql('ALTER TABLE infraction DROP FOREIGN KEY FK_C1A458F5758926A6');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EF4C5F3D5');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649D60322AC');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64998260155');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649539A88F2');
        $this->addSql('DROP TABLE agent');
        $this->addSql('DROP TABLE amende');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE brigade');
        $this->addSql('DROP TABLE controle');
        $this->addSql('DROP TABLE infraction');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
