<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213004331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, full_name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');

        // Create payments table
        $this->addSql('CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, amount VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, payment_method VARCHAR(20) NOT NULL, transaction_reference VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX IDX_65D29B32A76ED395 ON payments (user_id)');

        // Create access_codes table
        $this->addSql('CREATE TABLE access_codes (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, code VARCHAR(12) NOT NULL, is_used BOOLEAN NOT NULL, used_at DATETIME DEFAULT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX code_idx ON access_codes (code)');
        $this->addSql('CREATE INDEX IDX_3C99048A76ED395 ON access_codes (user_id)');

        // Create live_events table
        $this->addSql('CREATE TABLE live_events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB NOT NULL, image_url VARCHAR(500) DEFAULT NULL, price VARCHAR(10) NOT NULL, stream_url CLOB NOT NULL, is_active BOOLEAN NOT NULL, live_date DATETIME NOT NULL, created_at DATETIME NOT NULL)');

        // Create admin_users table
        $this->addSql('CREATE TABLE admin_users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON admin_users (email)');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE access_codes ADD CONSTRAINT FK_3C99048A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE access_codes');
        $this->addSql('DROP TABLE admin_users');
        $this->addSql('DROP TABLE live_events');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE users');
    }
}