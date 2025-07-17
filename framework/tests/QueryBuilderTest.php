<?php

use PHPUnit\Framework\TestCase;
use JosueIsOffline\Framework\Database\Connection as DatabaseConnection;
use JosueIsOffline\Framework\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
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

  public function testSelectAndGet()
  {
    $rows = $this->qb->table('users')->select()->get();

    $this->assertArrayHasKey('username', $rows[0]);
    $this->assertInstanceOf(QueryBuilder::class, $this->qb);
    $this->expectOutputString('Josue');

    print $rows[0]['username'];
  }

  public function testFirts()
  {

    $row = $this->qb->table('users')->select()->first();

    $this->assertArrayHasKey('username', $row);
  }

  public function testWhere()
  {

    $row = $this->qb->table('users')->select()->where('id', '=', 2)->get();

    $sql = $this->qb->toSql();

    $this->assertArrayHasKey('username', $row[0]);

    $this->expectOutputString('SELECT * FROM users WHERE id = :where_0');

    print $sql;
  }

  public function testInsert()
  {
    $data = [
      'username' => "Nose",
      'password' => 'admin123'
    ];

    $result = $this->qb->table('users')->insert($data);

    $this->assertTrue($result);
  }


  public function testUpdate()
  {
    $data = [
      'username' => "Updated",
    ];

    $result = $this->qb->table('users')->where('id', 1)->update($data);

    $row = $this->qb->table("users")->where('id', 1)->first();

    $this->assertEquals('Updated', $row['username']);

    $this->assertTrue($result);
  }

  public function testDelete()
  {
    $result = $this->qb->table('users')->where('id', 2)->delete();

    $this->assertTrue($result);
  }
}
