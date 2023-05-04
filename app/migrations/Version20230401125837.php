<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230401125837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_roulette_game (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, roulette_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_win TINYINT(1) DEFAULT NULL, INDEX IDX_E3E64CF1A76ED395 (user_id), INDEX IDX_E3E64CF1C247C4 (roulette_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_roulette_game ADD CONSTRAINT FK_E3E64CF1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_roulette_game ADD CONSTRAINT FK_E3E64CF1C247C4 FOREIGN KEY (roulette_id) REFERENCES roulette (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_roulette_game DROP FOREIGN KEY FK_E3E64CF1A76ED395');
        $this->addSql('ALTER TABLE user_roulette_game DROP FOREIGN KEY FK_E3E64CF1C247C4');
        $this->addSql('DROP TABLE user_roulette_game');
    }
}
