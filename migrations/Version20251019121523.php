<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251019121523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE security_log ADD user_agent VARCHAR(255) DEFAULT NULL, ADD email_attempt VARCHAR(255) DEFAULT NULL, ADD reason VARCHAR(255) DEFAULT NULL, DROP type, DROP message, DROP ip_address, CHANGE success success TINYINT(1) DEFAULT NULL, CHANGE email ip VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE security_log ADD type VARCHAR(255) NOT NULL, ADD message LONGTEXT DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD ip_address VARCHAR(45) DEFAULT NULL, DROP ip, DROP user_agent, DROP email_attempt, DROP reason, CHANGE success success TINYINT(1) NOT NULL');
    }
}
