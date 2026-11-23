<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123014252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Étape 3 : le lien vers le N+1 est vidé si le N+1 est supprimé (ON DELETE SET NULL)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY `FK_1D1C63B331B7BDD7`');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B331B7BDD7 FOREIGN KEY (n1_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B331B7BDD7');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT `FK_1D1C63B331B7BDD7` FOREIGN KEY (n1_id) REFERENCES utilisateur (id)');
    }
}
