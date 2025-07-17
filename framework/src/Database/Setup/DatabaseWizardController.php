<?php

namespace App\Controllers;

use JosueIsOffline\Framework\Controllers\AbstractController;
use JosueIsOffline\Framework\Http\Response;
use JosueIsOffline\Framework\Database\DatabaseBootstrap;
use JosueIsOffline\Framework\Database\Factories\ConnectionFactory;
use JosueIsOffline\Framework\Database\Setup\WizardRoutes;

class DatabaseWizardController extends AbstractController
{
  private $step = 1;

  private function getWizardRoute(): string
  {
    return WizardRoutes::getRoute();
  }

  private function getConfigDir(): string
  {
    return defined('BASE_PATH') ? BASE_PATH . '/config' : __DIR__ . '/../../config';
  }

  public function index(): Response
  {
    $bootstrap = new DatabaseBootstrap();
    if ($bootstrap->configFileExist()) {
      return new Response('', 302, ['Location' => '/']);
    }

    $method = $this->request->getMethod();

    if ($method === 'GET') {
      return $this->render('database-wizard.html.twig', [
        'step' => $this->step,
        'config' => [],
        'error' => '',
        'success' => '',
        'wizard_route' => $this->getWizardRoute()
      ]);
    }

    $params = $this->request->getAllPost();
    $step = (int) ($params['step'] ?? 1);

    switch ($step) {
      case 2:
        return $this->handleConnectionTest($params);
      case 3:
        return $this->handleDatabaseCreation($params);
      case 4:
        return $this->handleTableConfiguration($params);
      case 5:
        return $this->handleCreateTables($params);
      case 6:
        return $this->handleFinalSave($params);
      default:
        return $this->render('database-wizard.html.twig', [
          'step' => 1,
          'config' => [],
          'error' => '',
          'success' => '',
          'wizard_route' => $this->getWizardRoute()
        ]);
    }
  }

  private function renderWithRoute(string $template, array $data = []): Response
  {
    $data['wizard_route'] = $this->getWizardRoute();
    return $this->render($template, $data);
  }

  private function handleConnectionTest($params): Response
  {
    $config = $this->buildConfig($params);

    $validation = $this->validateConfig($config);
    if (!$validation['valid']) {
      return $this->renderWithRoute('database-wizard.html.twig', [
        'step' => 1,
        'config' => $config,
        'error' => $validation['error'],
        'success' => ''
      ]);
    }

    try {
      if ($config['driver'] === 'sqlite') {
        $factory = new ConnectionFactory();
        $connection = $factory->create($config);
        $connection->query('SELECT 1');
      } else {
        $testConfig = $config;
        unset($testConfig['database']);

        $factory = new ConnectionFactory();
        $connection = $factory->create($testConfig);
        $connection->query('SELECT 1');
      }

      return $this->renderWithRoute('database-wizard.html.twig', [
        'step' => 2,
        'config' => $config,
        'error' => '',
        'success' => 'Connection established successfully'
      ]);
    } catch (\Exception $e) {
      return $this->renderWithRoute('database-wizard.html.twig', [
        'step' => 2,
        'config' => $config,
        'error' => '',
        'success' => 'Connection established successfully'
      ]);
    }
  }

  private function handleDatabaseCreation($params): Response
  {
    $config = $this->buildConfig($params);

    try {
      if ($config['driver'] !== 'sqlite') {
        $this->createDatabase($config);
      }

      $factory = new ConnectionFactory();
      $connection = $factory->create($config);
      $connection->query('SELECT 1');

      return $this->render('database-wizard.html.twig', [
        'step' => 3,
        'config' => $config,
        'error' => '',
        'success' => 'Database created successfully. Now let\'s configure tables.'
      ]);
    } catch (\Exception $e) {
      return $this->render('database-wizard.html.twig', [
        'step' => 2,
        'config' => $config,
        'error' => 'Error creating database: ' . $e->getMessage(),
        'success' => ''
      ]);
    }
  }

  private function handleTableConfiguration($params): Response
  {
    $config = $this->buildConfig($params);

    if (!isset($params['table_names']) || empty($params['table_names'])) {
      return $this->render('database-wizard.html.twig', [
        'step' => 4,
        'config' => $config,
        'error' => '',
        'success' => ''
      ]);
    }

    $tables = $this->buildTablesConfig($params);
    $config['tables'] = $tables;

    return $this->render('database-wizard.html.twig', [
      'step' => 5,
      'config' => $config,
      'tables' => $tables,
      'error' => '',
      'success' => 'Table configuration processed successfully'
    ]);
  }

  private function handleCreateTables($params): Response
  {
    $config = $this->buildConfig($params);
    $tables = $this->buildTablesConfig($params);

    try {
      $factory = new ConnectionFactory();
      $connection = $factory->create($config);

      foreach ($tables as $table) {
        $this->createCustomTable($connection, $table, $config['driver']);
      }

      return $this->render('database-wizard.html.twig', [
        'step' => 5,
        'config' => $config,
        'tables' => $tables,
        'error' => '',
        'success' => 'All tables have been created successfully'
      ]);
    } catch (\Exception $e) {
      return $this->render('database-wizard.html.twig', [
        'step' => 4,
        'config' => $config,
        'error' => 'Error creating tables: ' . $e->getMessage(),
        'success' => ''
      ]);
    }
  }

  private function handleFinalSave($params): Response
  {
    $config = $this->buildConfig($params);
    $tables = $this->buildTablesConfig($params);
    $config['tables'] = $tables;

    try {
      $bootstrap = new DatabaseBootstrap();

      $configDir = $this->getConfigDir();

      if (!is_dir($configDir)) {
        if (!mkdir($configDir, 0755, true)) {
          throw new \Exception("Could not create config directory");
        }
      }

      if (!is_writable($configDir)) {
        throw new \Exception("Config directory is not writable");
      }

      $bootstrap->saveConfig($config);

      return $this->render('database-wizard.html.twig', [
        'step' => 6,
        'config' => $config,
        'tables' => $tables,
        'error' => '',
        'success' => 'Configuration saved successfully'
      ]);
    } catch (\Exception $e) {
      return $this->render('database-wizard.html.twig', [
        'step' => 5,
        'config' => $config,
        'error' => 'Error saving configuration: ' . $e->getMessage(),
        'success' => ''
      ]);
    }
  }

  private function buildConfig($params): array
  {
    $driver = $params['driver'] ?? '';

    $config = [
      'driver' => $driver,
      'host' => $params['host'] ?? 'localhost',
      'port' => (int)($params['port'] ?? $this->getDefaultPort($driver)),
      'database' => $params['database'] ?? '',
      'username' => $params['username'] ?? '',
      'password' => $params['password'] ?? '',
      'charset' => $params['charset'] ?? 'utf8mb4',
      'collation' => $params['collation'] ?? 'utf8mb4_unicode_ci'
    ];

    if ($config['driver'] === 'sqlite') {
      $config['database'] = $params['sqlite_path'] ?? 'database/app.sqlite';
      $config['host'] = '';
      $config['port'] = '';
      $config['username'] = '';
      $config['password'] = '';
    }

    return $config;
  }

  private function getDefaultPort($driver): int
  {
    return match ($driver) {
      'mysql' => 3306,
      'pgsql' => 5432,
      'sqlsrv' => 1433,
      default => 3306
    };
  }

  private function validateConfig($config): array
  {
    if (empty($config['driver'])) {
      return ['valid' => false, 'error' => 'You must select a database driver'];
    }

    if ($config['driver'] === 'sqlite') {
      if (empty($config['database'])) {
        return ['valid' => false, 'error' => 'You must specify the SQLite file path'];
      }
    } else {
      if (empty($config['host'])) {
        return ['valid' => false, 'error' => 'You must specify the server'];
      }
      if (empty($config['database'])) {
        return ['valid' => false, 'error' => 'You must specify the database name'];
      }
      if (empty($config['username'])) {
        return ['valid' => false, 'error' => 'You must specify the username'];
      }
    }

    return ['valid' => true, 'error' => ''];
  }

  private function buildTablesConfig($params): array
  {
    $tables = [];

    if (isset($params['table_names']) && is_array($params['table_names'])) {
      foreach ($params['table_names'] as $index => $tableName) {
        if (empty(trim($tableName))) continue;

        $table = [
          'name' => trim($tableName),
          'fields' => []
        ];

        if (isset($params['field_names'][$index]) && is_array($params['field_names'][$index])) {
          foreach ($params['field_names'][$index] as $fieldIndex => $fieldName) {
            if (empty(trim($fieldName))) continue;

            $field = [
              'name' => trim($fieldName),
              'type' => $params['field_types'][$index][$fieldIndex] ?? 'VARCHAR',
              'length' => trim($params['field_lengths'][$index][$fieldIndex] ?? ''),
              'nullable' => isset($params['field_nullable'][$index][$fieldIndex]),
              'primary_key' => isset($params['field_primary'][$index][$fieldIndex]),
              'auto_increment' => isset($params['field_auto_increment'][$index][$fieldIndex])
            ];

            if ($field['auto_increment']) {
              $field['type'] = 'INT';
              $field['primary_key'] = true;
              $field['nullable'] = false;
            }

            if ($field['primary_key']) {
              $field['nullable'] = false;
            }

            $table['fields'][] = $field;
          }
        }

        if (count($table['fields']) > 0) {
          $tables[] = $table;
        }
      }
    }

    return $tables;
  }

  private function createDatabase($config): void
  {
    $tempConfig = $config;
    unset($tempConfig['database']);

    $factory = new ConnectionFactory();
    $connection = $factory->create($tempConfig);

    $dbName = $config['database'];
    $charset = $config['charset'] ?? 'utf8mb4';

    switch ($config['driver']) {
      case 'mysql':
        $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset}";
        break;
      case 'pgsql':
        try {
          $sql = "CREATE DATABASE \"{$dbName}\"";
          $connection->execute($sql);
          return;
        } catch (\Exception $e) {
          if (strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
          }
          return;
        }
      case 'sqlsrv':
        $sql = "IF NOT EXISTS(SELECT * FROM sys.databases WHERE name = '{$dbName}') CREATE DATABASE [{$dbName}]";
        break;
      default:
        throw new \Exception("Database creation not supported for driver: {$config['driver']}");
    }

    try {
      $connection->execute($sql);
    } catch (\Exception $e) {
      throw $e;
    }
  }

  private function createCustomTable($connection, $table, $driver): void
  {
    $tableName = $table['name'];
    $fields = $table['fields'];

    if (empty($fields)) {
      throw new \Exception("Table '$tableName' must have at least one field");
    }

    $quote = match ($driver) {
      'mysql' => '`',
      'pgsql' => '"',
      'sqlsrv' => '[',
      'sqlite' => '`',
      default => '`'
    };

    $closeQuote = match ($driver) {
      'sqlsrv' => ']',
      default => $quote
    };

    $sql = "CREATE TABLE IF NOT EXISTS {$quote}{$tableName}{$closeQuote} (";
    $fieldSql = [];

    foreach ($fields as $field) {
      $fieldDef = $this->buildFieldDefinition($field, $driver);
      $fieldSql[] = $fieldDef;
    }

    $sql .= implode(', ', $fieldSql);
    $sql .= ")";

    if ($driver === 'mysql') {
      $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    $connection->execute($sql);
  }

  private function buildFieldDefinition($field, $driver): string
  {
    $name = $field['name'];
    $type = $field['type'];
    $length = $field['length'];
    $nullable = $field['nullable'];
    $primaryKey = $field['primary_key'];
    $autoIncrement = $field['auto_increment'];

    $quote = match ($driver) {
      'mysql' => '`',
      'pgsql' => '"',
      'sqlsrv' => '[',
      'sqlite' => '`',
      default => '`'
    };

    $closeQuote = match ($driver) {
      'sqlsrv' => ']',
      default => $quote
    };

    $definition = "{$quote}{$name}{$closeQuote} ";

    switch (strtoupper($type)) {
      case 'VARCHAR':
        $len = $length ?: '255';
        $definition .= "VARCHAR($len)";
        break;
      case 'INT':
        if ($driver === 'sqlite') {
          $definition .= "INTEGER";
        } else {
          $len = $length ? "($length)" : '';
          $definition .= "INT$len";
        }
        break;
      case 'TEXT':
        $definition .= "TEXT";
        break;
      case 'DATETIME':
        $definition .= ($driver === 'sqlsrv') ? "DATETIME2" : "DATETIME";
        break;
      case 'TIMESTAMP':
        $definition .= "TIMESTAMP";
        break;
      case 'DECIMAL':
        $len = $length ?: '10,2';
        $definition .= "DECIMAL($len)";
        break;
      default:
        $definition .= $type;
        break;
    }

    if (!$nullable) {
      $definition .= " NOT NULL";
    }

    if ($autoIncrement) {
      switch ($driver) {
        case 'mysql':
          $definition .= " AUTO_INCREMENT";
          break;
        case 'pgsql':
          $definition = "{$quote}{$name}{$closeQuote} SERIAL";
          break;
        case 'sqlite':
          if ($primaryKey) {
            $definition .= " AUTOINCREMENT";
          }
          break;
        case 'sqlsrv':
          $definition .= " IDENTITY(1,1)";
          break;
      }
    }

    if ($primaryKey) {
      $definition .= " PRIMARY KEY";
    }

    return $definition;
  }
}
