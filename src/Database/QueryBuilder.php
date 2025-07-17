<?php

namespace JosueIsOffline\Framework\Database;


class QueryBuilder
{
  private Connection $connection;
  private string $table = '';
  private array $columns = ['*'];
  private array $where = [];
  private array $joins = [];
  private array $orderBy = [];
  private array $groupBy = [];
  private ?string $having = null;
  private ?int $limit = null;
  private ?int $offset = null;
  private array $bindings = [];

  public function __construct(Connection $connection)
  {
    $this->connection = $connection;
  }

  public function table(string $table): self
  {
    $this->table = $table;
    return $this;
  }

  public function get(): array
  {
    $sql = $this->buildSelectQuery();
    return $this->connection->fetchAll($sql, $this->bindings);
  }

  public function first(): array
  {
    $sql = $this->buildSelectQuery();
    return $this->connection->fetchOne($sql, $this->bindings);
  }


  public function select(...$columns): self
  {
    $this->columns = empty($columns) ? ['*'] : $columns;

    return $this;
  }

  public function where(string $column, $operator, $value = null): self
  {
    if ($value === null) {
      $value = $operator;
      $operator = '=';
    }

    $placeholder = ":where_" . count($this->where);
    $this->where[] = "$column $operator $placeholder";
    $this->bindings[$placeholder] = $value;

    return $this;
  }

  public function insert(array $data): bool
  {
    $columns = array_keys($data);
    $placeholder = array_map(fn($col) => ":{$col}", $columns);

    $sql = "INSERT INTO $this->table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholder) . ")";

    $bindings = [];
    foreach ($data as $key => $value) {
      $bindings[":$key"] = $value;
    }

    return $this->connection->execute($sql, $bindings);
  }

  public function update(array $data): bool
  {
    $sets = [];
    $bindings = [];

    foreach ($data as $column => $value) {
      $placeholder = ":update_$column";
      $sets[] = "$column = $placeholder";
      $bindings[$placeholder] = $value;
    }

    $sql = "UPDATE $this->table SET " . implode(', ', $sets);

    if (!empty($this->where)) {
      $sql .= " WHERE " . implode(' AND ', $this->where);
    }
    return $this->connection->execute($sql, array_merge($bindings, $this->bindings));
  }

  public function delete(): bool
  {
    $sql = "DELETE FROM $this->table";

    if (!empty($this->where)) {
      $sql .= " WHERE " . implode(' AND ', $this->where);
    }

    return $this->connection->execute($sql, $this->bindings);
  }


  private function buildSelectQuery(): string
  {
    $sql = "SELECT " . implode(', ', $this->columns) . " FROM $this->table";

    if (!empty($this->where)) {
      $sql .= " WHERE " . implode(' AND ', $this->where);
    }

    return $sql;
  }

  public function toSql(): string
  {
    return $this->buildSelectQuery();
  }

  public function getBindings(): array
  {
    return $this->bindings;
  }
}
