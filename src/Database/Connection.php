<?php

namespace JosueIsOffline\Framework\Database;

use PDO;
use PDOException;

class Connection
{
  private PDO $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }


  public function query(string $sql, array $params = []): \PDOStatement
  {
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($params);

      return $stmt;
    } catch (PDOException $e) {
      throw new DatabaseException(
        "Error in query [{$e->getCode()}]: {$e->getMessage()} SQL: '$sql'"
      );
    }
  }

  public function fetchAll(string $sql, array $params = []): array
  {
    return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
  }

  public function fetchOne(string $sql, array $params =  []): ?array
  {
    $stmt = $this->query($sql, $params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row !== false ? $row : null;
  }

  public function execute(string $sql, array $params = []): bool
  {
    $this->query($sql, $params);

    return true;
  }

  public function lastInsertId(): string
  {
    return $this->pdo->lastInsertId();
  }
}
