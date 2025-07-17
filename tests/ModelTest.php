<?php

use PHPUnit\Framework\TestCase;
use JosueIsOffline\Framework\Database\Connection as DatabaseConnection;
use JosueIsOffline\Framework\Database\QueryBuilder;

class ModelTest extends TestCase
{
  protected PDO $pdo;
  protected DatabaseConnection $connection;
  protected QueryBuilder $qb;

  protected function setUp(): void
  {
    $this->pdo = new PDO('sqlite::memory:');
    $this->connection = new DatabaseConnection($this->pdo);
    $this->qb = new QueryBuilder($this->connection);


    $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        username TEXT,
        password TEXT
    )');

    $this->pdo->exec("INSERT INTO users (username, password) VALUES ('Josue', 'admin123')");
    $this->pdo->exec("INSERT INTO users (username, password) VALUES ('Angela', 'admin123')");
  }
}
