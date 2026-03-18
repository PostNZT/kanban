<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318024609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create kanban board schema: user, board, board_column, card tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE "user" (id SERIAL PRIMARY KEY, email VARCHAR(180) NOT NULL, roles JSON NOT NULL DEFAULT \'[]\', password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');

        $this->addSql('CREATE TABLE board (id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, owner_id INTEGER NOT NULL REFERENCES "user" (id) ON DELETE CASCADE)');
        $this->addSql('CREATE INDEX IDX_58562B477E3C61F9 ON board (owner_id)');
        $this->addSql('COMMENT ON COLUMN board.created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE board_column (id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, position INTEGER NOT NULL, board_id INTEGER NOT NULL REFERENCES board (id) ON DELETE CASCADE)');
        $this->addSql('CREATE INDEX IDX_D14DC3D9E7EC5785 ON board_column (board_id)');

        $this->addSql('CREATE TABLE card (id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, position INTEGER NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, board_column_id INTEGER NOT NULL REFERENCES board_column (id) ON DELETE CASCADE)');
        $this->addSql('CREATE INDEX IDX_161498D3CA372FE ON card (board_column_id)');
        $this->addSql('COMMENT ON COLUMN card.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE board_column');
        $this->addSql('DROP TABLE board');
        $this->addSql('DROP TABLE "user"');
    }
}
