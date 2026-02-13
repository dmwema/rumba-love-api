<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213105121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B4A95E13E7927C74 ON admin_users (email)');
        $this->addSql('CREATE TABLE live_events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB NOT NULL, image_url VARCHAR(500) DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, stream_url CLOB NOT NULL, is_active BOOLEAN NOT NULL, live_date DATETIME NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__access_codes AS SELECT id, user_id, code, is_used, used_at, expires_at, created_at FROM access_codes');
        $this->addSql('DROP TABLE access_codes');
        $this->addSql('CREATE TABLE access_codes (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, code VARCHAR(15) NOT NULL, is_used BOOLEAN NOT NULL, used_at DATETIME DEFAULT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_C38BD1EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO access_codes (id, user_id, code, is_used, used_at, expires_at, created_at) SELECT id, user_id, code, is_used, used_at, expires_at, created_at FROM __temp__access_codes');
        $this->addSql('DROP TABLE __temp__access_codes');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C38BD1E77153098 ON access_codes (code)');
        $this->addSql('CREATE INDEX IDX_C38BD1EA76ED395 ON access_codes (user_id)');
        $this->addSql('CREATE INDEX code_idx ON access_codes (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__payments AS SELECT id, user_id, amount, status, payment_method, phone_number, transaction_reference, created_at FROM payments');
        $this->addSql('DROP TABLE payments');
        $this->addSql('CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, payment_method VARCHAR(20) NOT NULL, phone_number VARCHAR(20) NOT NULL, transaction_reference VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO payments (id, user_id, amount, status, payment_method, phone_number, transaction_reference, created_at) SELECT id, user_id, amount, status, payment_method, phone_number, transaction_reference, created_at FROM __temp__payments');
        $this->addSql('DROP TABLE __temp__payments');
        $this->addSql('CREATE INDEX IDX_65D29B32A76ED395 ON payments (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, full_name, phone, last_activity, is_online, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, full_name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, last_activity DATETIME DEFAULT NULL, is_online BOOLEAN NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO users (id, email, full_name, phone, last_activity, is_online, created_at) SELECT id, email, full_name, phone, last_activity, is_online, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE admin_users');
        $this->addSql('DROP TABLE live_events');
        $this->addSql('CREATE TEMPORARY TABLE __temp__access_codes AS SELECT id, code, is_used, used_at, expires_at, created_at, user_id FROM access_codes');
        $this->addSql('DROP TABLE access_codes');
        $this->addSql('CREATE TABLE access_codes (id INTEGER PRIMARY KEY AUTOINCREMENT DEFAULT NULL, code VARCHAR(20) NOT NULL, is_used BOOLEAN DEFAULT 0, used_at DATETIME DEFAULT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO access_codes (id, code, is_used, used_at, expires_at, created_at, user_id) SELECT id, code, is_used, used_at, expires_at, created_at, user_id FROM __temp__access_codes');
        $this->addSql('DROP TABLE __temp__access_codes');
        $this->addSql('CREATE TEMPORARY TABLE __temp__payments AS SELECT id, amount, status, payment_method, phone_number, transaction_reference, created_at, user_id FROM payments');
        $this->addSql('DROP TABLE payments');
        $this->addSql('CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT DEFAULT NULL, amount VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, payment_method VARCHAR(20) NOT NULL, phone_number VARCHAR(20) NOT NULL, transaction_reference VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO payments (id, amount, status, payment_method, phone_number, transaction_reference, created_at, user_id) SELECT id, amount, status, payment_method, phone_number, transaction_reference, created_at, user_id FROM __temp__payments');
        $this->addSql('DROP TABLE __temp__payments');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, full_name, phone, last_activity, is_online, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, full_name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, last_activity DATETIME DEFAULT NULL, is_online BOOLEAN DEFAULT 0, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO users (id, email, full_name, phone, last_activity, is_online, created_at) SELECT id, email, full_name, phone, last_activity, is_online, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
    }
}
