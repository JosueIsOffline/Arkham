<?php

declare(strict_types=1);

use JosueIsOffline\Framework\Database\Connection as DatabaseConnection;
use JosueIsOffline\Framework\Database\Factories\ConnectionFactory;
use PHPUnit\Framework\TestCase;


class ConnectionTest extends TestCase
{
  protected PDO $pdo;
  protected DatabaseConnection $connection;

  protected function setUp(): void
  {
    $this->pdo = new PDO('sqlite::memory:');
    $this->connection = new DatabaseConnection($this->pdo);

    $this->pdo->exec('CREATE TABLE IF NOT EXISTS books (id INTEGER PRIMARY KEY, title TEXT)');
    $this->pdo->exec("INSERT INTO books (title) VALUES ('PHP 8 Rocks')");


    $this->pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        username TEXT,
        password TEXT
    )');

    $this->pdo->exec("INSERT INTO users (username, password) VALUES ('Josue', 'admin123')");
    $this->pdo->exec("INSERT INTO users (username, password) VALUES ('Angela', 'admin123')");
  }

  public function testQueryReturnsPdoStatement()
  {
    $stmt = $this->connection->query('SELECT * FROM books WHERE id = ?', [1]);

    $this->assertInstanceOf(PDOStatement::class, $stmt);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $this->assertEquals('PHP 8 Rocks', $row['title']);
  }


  public function testfetchAll()
  {
    $rows = $this->connection->fetchAll('SELECT * FROM users;');

    $this->assertIsArray($rows, 'rows is not an array');
    $this->assertCount(2, $rows, "rows just have n > 2 or n < 2");
    $this->assertArrayHasKey('username', $rows[0]);

    $this->expectOutputString('Angela');

    print $rows[1]['username'];
  }

  public function testFetchOne()
  {
    $row = $this->connection->fetchOne("SELECT * FROM users");

    $this->assertIsArray($row);
    $this->assertArrayhasKey('username', $row, "row doesn't containt 'username' key");

    $this->expectOutputString('Josue');

    print $row['username'];
  }

  public function testFactoryCreation()
  {
    $connFactory = new ConnectionFactory();
    $sqlLite = $connFactory->create(["driver" => "sqlite", "database" => "main"]);
    $stmt = $sqlLite->query("CREATE TABLE IF NOT EXISTS test (title TEXT)");

    $this->assertInstanceOf(PDOStatement::class, $stmt);


    // $this->assertIsArray($rows);
    // $this->assertArrayHasKey('username', $rows[0]);

    $this->assertInstanceOf(DatabaseConnection::class, $sqlLite);
  }
}
