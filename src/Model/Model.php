<?php

namespace JosueIsOffline\Framework\Model;

use JosueIsOffline\Framework\Database\DB;
use JosueIsOffline\Framework\Database\QueryBuilder;

abstract class Model
{
  protected string $table = '';
  protected string $primaryKey = 'id';
  protected array $fillabel = [];
  protected array $attributes = [];


  public function __construct(array $attributes = [])
  {
    $this->attributes = $attributes;
    if (empty($this->table)) {
      $this->table = $this->getTable();
    }
  }

  private function getTable(): string
  {
    $className = (new \ReflectionClass($this))->getShortName();

    return strtolower($className) . 's';
  }

  public static function query(): QueryBuilder
  {
    $instance = new static();
    return DB::table($instance->table);
  }

  public static function all(): array
  {
    return static::query()->select()->get();
  }

  public static function find(mixed $id): ?self
  {
    $row = static::query()->select()->where('id', $id)->first();

    return $row ? new static($row) : null;
  }

  public static function where(string $column, $operator, mixed $value = null): array
  {
    if ($value === null) {
      $value = $operator;
      $operator = '=';
    }

    $rows = static::query()->where($column, $operator, $value)->get();
    return array_map(fn($row) => new static($row), $rows);
  }

  public function create(array $data): bool
  {
    $instance = new static();

    $filtered = array_filter(
      $data,
      fn($key) => in_array($key, $instance->fillabel),
      ARRAY_FILTER_USE_KEY
    );
    return static::query()->insert($data);
  }

  public function update(array $data): bool
  {
    return static::query()->where('id', $data['id'])->update($data);
  }

  public function delete(mixed $id): bool
  {
    return static::query()->where('id', $id)->delete();
  }


  public function __get(string $key)
  {
    return $this->attributes[$key] ?? null;
  }

  public function __set(string $key, mixed $value): void
  {
    $this->attributes[$key] = $value;
  }

  public function __isset(string $key): bool
  {
    return isset($this->attributes[$key]);
  }
}
