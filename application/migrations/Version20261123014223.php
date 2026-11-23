<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123014223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Étape 3 : table signature_reference (signature chiffrée de chaque utilisateur)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE signature_reference (id INT AUTO_INCREMENT NOT NULL, image_chiffree LONGTEXT NOT NULL, traits_chiffres LONGTEXT NOT NULL, essai_retenu SMALLINT NOT NULL, cree_le DATETIME NOT NULL, utilisateur_id INT NOT NULL, UNIQUE INDEX UNIQ_1276A68FFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE signature_reference ADD CONSTRAINT FK_1276A68FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signature_reference DROP FOREIGN KEY FK_1276A68FFB88E14F');
        $this->addSql('DROP TABLE signature_reference');
    }
}
