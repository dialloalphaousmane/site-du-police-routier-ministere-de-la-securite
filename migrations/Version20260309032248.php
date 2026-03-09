<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309032248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accident (id INT AUTO_INCREMENT NOT NULL, brigade_id INT NOT NULL, region_id INT NOT NULL, agent_enqueteur_id INT DEFAULT NULL, created_by_id INT NOT NULL, reference VARCHAR(50) NOT NULL, date_accident DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', localisation VARCHAR(255) NOT NULL, ville VARCHAR(255) DEFAULT NULL, commune VARCHAR(255) DEFAULT NULL, route VARCHAR(255) DEFAULT NULL, carrefour VARCHAR(255) DEFAULT NULL, meteo VARCHAR(100) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, description LONGTEXT NOT NULL, gravite VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, nb_victimes INT NOT NULL, nb_morts INT NOT NULL, nb_blesses_graves INT NOT NULL, nb_blesses_legers INT NOT NULL, cause_principale VARCHAR(50) NOT NULL, causes_secondaires JSON NOT NULL, solutions_proposees LONGTEXT DEFAULT NULL, mesures_prevention LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_validation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8F31DB6FAEA34913 (reference), INDEX IDX_8F31DB6F539A88F2 (brigade_id), INDEX IDX_8F31DB6F98260155 (region_id), INDEX IDX_8F31DB6FAD64583A (agent_enqueteur_id), INDEX IDX_8F31DB6FB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE accident_vehicle (id INT AUTO_INCREMENT NOT NULL, accident_id INT NOT NULL, immatriculation VARCHAR(50) NOT NULL, marque VARCHAR(255) DEFAULT NULL, modele VARCHAR(255) DEFAULT NULL, type_vehicule VARCHAR(20) NOT NULL, niveau_dommage VARCHAR(20) NOT NULL, couleur VARCHAR(255) DEFAULT NULL, annee_fabrication INT DEFAULT NULL, proprietaire_nom VARCHAR(255) DEFAULT NULL, proprietaire_prenom VARCHAR(255) DEFAULT NULL, proprietaire_telephone VARCHAR(20) DEFAULT NULL, assurance_compagnie VARCHAR(255) DEFAULT NULL, assurance_numero VARCHAR(50) DEFAULT NULL, assurance_validite DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', description_dommages LONGTEXT DEFAULT NULL, observations LONGTEXT DEFAULT NULL, remorque TINYINT(1) DEFAULT NULL, en_stationnement TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3357131816D8554C (accident_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE accident_victim (id INT AUTO_INCREMENT NOT NULL, accident_id INT NOT NULL, evacuation_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, age VARCHAR(20) DEFAULT NULL, sexe VARCHAR(10) DEFAULT NULL, type_victime VARCHAR(20) NOT NULL, gravite VARCHAR(20) NOT NULL, nationalite VARCHAR(255) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, blessures LONGTEXT DEFAULT NULL, observations LONGTEXT DEFAULT NULL, evacue TINYINT(1) DEFAULT NULL, decede TINYINT(1) DEFAULT NULL, date_deces DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D0830F1116D8554C (accident_id), INDEX IDX_D0830F11B4670DCB (evacuation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evacuation (id INT AUTO_INCREMENT NOT NULL, accident_id INT NOT NULL, brigade_id INT NOT NULL, agent_responsable_id INT DEFAULT NULL, created_by_id INT NOT NULL, reference VARCHAR(50) NOT NULL, date_evacuation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', type_evacuation VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, urgence VARCHAR(20) NOT NULL, hopital_destination VARCHAR(255) NOT NULL, contact_hopital VARCHAR(255) DEFAULT NULL, observations LONGTEXT DEFAULT NULL, date_arrivee DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_fin DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', nb_victimes_evacuees INT NOT NULL, distance_km INT NOT NULL, duree_minutes INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E56BD59CAEA34913 (reference), INDEX IDX_E56BD59C16D8554C (accident_id), INDEX IDX_E56BD59C539A88F2 (brigade_id), INDEX IDX_E56BD59C6CC7B0A (agent_responsable_id), INDEX IDX_E56BD59CB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accident ADD CONSTRAINT FK_8F31DB6F539A88F2 FOREIGN KEY (brigade_id) REFERENCES brigade (id)');
        $this->addSql('ALTER TABLE accident ADD CONSTRAINT FK_8F31DB6F98260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE accident ADD CONSTRAINT FK_8F31DB6FAD64583A FOREIGN KEY (agent_enqueteur_id) REFERENCES agent (id)');
        $this->addSql('ALTER TABLE accident ADD CONSTRAINT FK_8F31DB6FB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE accident_vehicle ADD CONSTRAINT FK_3357131816D8554C FOREIGN KEY (accident_id) REFERENCES accident (id)');
        $this->addSql('ALTER TABLE accident_victim ADD CONSTRAINT FK_D0830F1116D8554C FOREIGN KEY (accident_id) REFERENCES accident (id)');
        $this->addSql('ALTER TABLE accident_victim ADD CONSTRAINT FK_D0830F11B4670DCB FOREIGN KEY (evacuation_id) REFERENCES evacuation (id)');
        $this->addSql('ALTER TABLE evacuation ADD CONSTRAINT FK_E56BD59C16D8554C FOREIGN KEY (accident_id) REFERENCES accident (id)');
        $this->addSql('ALTER TABLE evacuation ADD CONSTRAINT FK_E56BD59C539A88F2 FOREIGN KEY (brigade_id) REFERENCES brigade (id)');
        $this->addSql('ALTER TABLE evacuation ADD CONSTRAINT FK_E56BD59C6CC7B0A FOREIGN KEY (agent_responsable_id) REFERENCES agent (id)');
        $this->addSql('ALTER TABLE evacuation ADD CONSTRAINT FK_E56BD59CB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accident DROP FOREIGN KEY FK_8F31DB6F539A88F2');
        $this->addSql('ALTER TABLE accident DROP FOREIGN KEY FK_8F31DB6F98260155');
        $this->addSql('ALTER TABLE accident DROP FOREIGN KEY FK_8F31DB6FAD64583A');
        $this->addSql('ALTER TABLE accident DROP FOREIGN KEY FK_8F31DB6FB03A8386');
        $this->addSql('ALTER TABLE accident_vehicle DROP FOREIGN KEY FK_3357131816D8554C');
        $this->addSql('ALTER TABLE accident_victim DROP FOREIGN KEY FK_D0830F1116D8554C');
        $this->addSql('ALTER TABLE accident_victim DROP FOREIGN KEY FK_D0830F11B4670DCB');
        $this->addSql('ALTER TABLE evacuation DROP FOREIGN KEY FK_E56BD59C16D8554C');
        $this->addSql('ALTER TABLE evacuation DROP FOREIGN KEY FK_E56BD59C539A88F2');
        $this->addSql('ALTER TABLE evacuation DROP FOREIGN KEY FK_E56BD59C6CC7B0A');
        $this->addSql('ALTER TABLE evacuation DROP FOREIGN KEY FK_E56BD59CB03A8386');
        $this->addSql('DROP TABLE accident');
        $this->addSql('DROP TABLE accident_vehicle');
        $this->addSql('DROP TABLE accident_victim');
        $this->addSql('DROP TABLE evacuation');
    }
}
