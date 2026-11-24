<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Integration;

use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
final class SchemaTest extends TestCase
{
    private function database(): PDO
    {
        $database = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $database->exec((string) file_get_contents(dirname(__DIR__, 2) . '/db/schema.sql'));

        return $database;
    }

    public function testCreatesExpectedTables(): void
    {
        $statement = $this->database()->query("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name");

        self::assertNotFalse($statement);
        self::assertSame(
            ['guess_attempts', 'hash_recovery_runs', 'players', 'rps_rounds'],
            $statement->fetchAll(PDO::FETCH_COLUMN),
        );
    }

    public function testSchemaIsIdempotent(): void
    {
        $database = $this->database();

        self::assertNotFalse($database->exec((string) file_get_contents(dirname(__DIR__, 2) . '/db/schema.sql')));
    }

    public function testRejectsInvalidMove(): void
    {
        $database = $this->database();
        $database->exec("INSERT INTO players (display_name) VALUES ('Ada')");

        $this->expectException(PDOException::class);
        $database->exec("INSERT INTO rps_rounds (player_id, human_move, computer_move, outcome) VALUES (1, 'lizard', 'rock', 'win')");
    }

    public function testEnforcesForeignKey(): void
    {
        $this->expectException(PDOException::class);
        $this->database()->exec("INSERT INTO rps_rounds (player_id, human_move, computer_move, outcome) VALUES (99, 'rock', 'rock', 'tie')");
    }

    public function testDeletingPlayerCascadesRounds(): void
    {
        $database = $this->database();
        $database->exec("INSERT INTO players (display_name) VALUES ('Ada')");
        $database->exec("INSERT INTO rps_rounds (player_id, human_move, computer_move, outcome) VALUES (1, 'rock', 'rock', 'tie')");
        $database->exec('DELETE FROM players');
        $statement = $database->query('SELECT COUNT(*) FROM rps_rounds');

        self::assertNotFalse($statement);
        self::assertSame(0, (int) $statement->fetchColumn());
    }
}
