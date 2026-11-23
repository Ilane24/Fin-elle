<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123010100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Étape 2 : tables departement, service et utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_C1765B636C6E55B5 (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, libelle VARCHAR(100) NOT NULL, departement_id INT NOT NULL, UNIQUE INDEX UNIQ_E19D9AD277153098 (code), INDEX IDX_E19D9AD2CCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, matricule VARCHAR(20) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, fonction VARCHAR(150) NOT NULL, role VARCHAR(30) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, compte_menues_depenses TINYINT NOT NULL, numero_compte_md VARCHAR(20) DEFAULT NULL, actif TINYINT NOT NULL, cree_le DATETIME NOT NULL, service_id INT NOT NULL, n1_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B312B2DC9C (matricule), UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), UNIQUE INDEX UNIQ_1D1C63B3DBBAD5BB (numero_compte_md), INDEX IDX_1D1C63B3ED5CA9E6 (service_id), INDEX IDX_1D1C63B331B7BDD7 (n1_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B331B7BDD7 FOREIGN KEY (n1_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2CCF9E01E');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3ED5CA9E6');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B331B7BDD7');
        $this->addSql('DROP TABLE departement');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE utilisateur');
    }
}
