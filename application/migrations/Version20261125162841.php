<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125162841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Le département est rattaché directement à l'utilisateur ; suppression du matricule et de la table service";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD departement_id INT DEFAULT NULL');
        $this->addSql('UPDATE utilisateur u INNER JOIN service s ON s.id = u.service_id SET u.departement_id = s.departement_id');
        $this->addSql('ALTER TABLE utilisateur MODIFY departement_id INT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('CREATE INDEX IDX_1D1C63B3CCF9E01E ON utilisateur (departement_id)');

        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3ED5CA9E6');
        $this->addSql('DROP INDEX IDX_1D1C63B3ED5CA9E6 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP service_id');

        $this->addSql('DROP INDEX UNIQ_1D1C63B312B2DC9C ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP matricule');

        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2CCF9E01E');
        $this->addSql('DROP TABLE service');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Les services et les matricules supprimés ne peuvent pas être recréés.');
    }
}
