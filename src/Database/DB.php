<?php

namespace JosueIsOffline\Framework\Database;

use JosueIsOffline\Framework\Database\Factories\ConnectionFactory;

class DB
{
  private static ?Connection $connection = null;

  public static function configure(array $configure): void
  {
    $factory = new ConnectionFactory();
    static::$connection = $factory->create($configure);
  }

  public static function connection(): Connection
  {
    if (self::$connection === null) {
      throw new \Exception("No database connection configured.");
    }

    return self::$connection;
  }

  public static function table(string $name): QueryBuilder
  {
    return (new QueryBuilder(static::connection()))->table($name);
  }

  public static function raw(string $sql, array $params = []): mixed
  {
    return self::$connection->query($sql, $params);
  }
}
