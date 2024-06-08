<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240517204032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE discussion (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C0B9F90F9D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE discussion_messages (id INT AUTO_INCREMENT NOT NULL, user_id_id INT DEFAULT NULL, discussion_id_id INT NOT NULL, message LONGTEXT NOT NULL, sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6E5A8F819D86650F (user_id_id), INDEX IDX_6E5A8F81B0DB6562 (discussion_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90F9D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE discussion_messages ADD CONSTRAINT FK_6E5A8F819D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE discussion_messages ADD CONSTRAINT FK_6E5A8F81B0DB6562 FOREIGN KEY (discussion_id_id) REFERENCES discussion (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90F9D86650F');
        $this->addSql('ALTER TABLE discussion_messages DROP FOREIGN KEY FK_6E5A8F819D86650F');
        $this->addSql('ALTER TABLE discussion_messages DROP FOREIGN KEY FK_6E5A8F81B0DB6562');
        $this->addSql('DROP TABLE discussion');
        $this->addSql('DROP TABLE discussion_messages');
    }
}
