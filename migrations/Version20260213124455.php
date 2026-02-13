<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213124455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__payments AS SELECT id, user_id, amount, status, payment_method, phone_number, transaction_reference, created_at FROM payments');
        $this->addSql('DROP TABLE payments');
        $this->addSql('CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, payment_method VARCHAR(20) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, transaction_reference VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO payments (id, user_id, amount, status, payment_method, phone_number, transaction_reference, created_at) SELECT id, user_id, amount, status, payment_method, phone_number, transaction_reference, created_at FROM __temp__payments');
        $this->addSql('DROP TABLE __temp__payments');
        $this->addSql('CREATE INDEX IDX_65D29B32A76ED395 ON payments (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__payments AS SELECT id, amount, status, payment_method, phone_number, transaction_reference, created_at, user_id FROM payments');
        $this->addSql('DROP TABLE payments');
        $this->addSql('CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, payment_method VARCHAR(20) NOT NULL, phone_number VARCHAR(20) NOT NULL, transaction_reference VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO payments (id, amount, status, payment_method, phone_number, transaction_reference, created_at, user_id) SELECT id, amount, status, payment_method, phone_number, transaction_reference, created_at, user_id FROM __temp__payments');
        $this->addSql('DROP TABLE __temp__payments');
        $this->addSql('CREATE INDEX IDX_65D29B32A76ED395 ON payments (user_id)');
    }
}
