<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109174400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store which statements the bank allows per account (from UPD).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account ADD COLUMN supports_statements_of_account BOOLEAN DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE account ADD COLUMN supports_statements_of_holding BOOLEAN DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE account ADD COLUMN account_type INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__account AS SELECT id, name, bic, bank_code, url, tan_media_name, tan_mechanism, username, password, iban, account_number FROM account');
        $this->addSql('DROP TABLE account');
        $this->addSql('CREATE TABLE account (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, bic VARCHAR(255) NOT NULL, bank_code VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, tan_media_name VARCHAR(255) DEFAULT NULL, tan_mechanism VARCHAR(255) DEFAULT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, iban VARCHAR(255) DEFAULT NULL, account_number VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO account (id, name, bic, bank_code, url, tan_media_name, tan_mechanism, username, password, iban, account_number) SELECT id, name, bic, bank_code, url, tan_media_name, tan_mechanism, username, password, iban, account_number FROM __temp__account');
        $this->addSql('DROP TABLE __temp__account');
    }
}
