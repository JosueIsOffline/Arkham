<?php

namespace JosueIsOffline\Framework\Database\Factories;

use JosueIsOffline\Framework\Database\Connection;
use PDO;

class ConnectionFactory
{

  public function create(array $config): Connection
  {
    $driver = $config['driver'];
    return match ($driver) {
      'mysql' => $this->createMySql($config),
      'sqlite' => $this->createSqlLite($config),
      default => throw new \Exception("$driver - This driver is not supported yet.")
    };
  }


  private function createMySql(array $config): Connection
  {
    $host = $config['host'] ?? 'localhost';
    $port = $config['port'] ?? 3306;
    $database = $config['database'] ?? '';
    $charset = $config['charset'] ?? 'utf8mb4';
    $options = $this->getDefaultOptions();

    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";
    $pdo = new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', $options);
    return new Connection($pdo);
  }

  private function createSqlLite(array $config): Connection
  {
    $database = $config['database'] ?? ":memory:";

    $dsn = "sqlite:$database";
    $pdo = new PDO($dsn);
    return new Connection($pdo);
  }

  private function getDefaultOptions(): array
  {
    return [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];
  }
}
