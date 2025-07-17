<?php

namespace App\Controllers;

use JosueIsOffline\Framework\Controllers\AbstractController;
use JosueIsOffline\Framework\Http\Response;
use JosueIsOffline\Framework\Database\DatabaseBootstrap;
use JosueIsOffline\Framework\Database\Factories\ConnectionFactory;

class DatabaseWizardController extends AbstractController
{
  private $step = 1;

  public function index(): Response
  {
    // Check if we already have a config file
    $bootstrap = new DatabaseBootstrap();
    if ($bootstrap->configFileExist()) {
      // Redirect to app if already configured
      return new Response('', 302, ['Location' => '/']);
    }

    $method = $this->request->getMethod();

    if ($method === 'GET') {
      return $this->render('database-wizard.html.twig', [
        'step' => $this->step,
        'config' => [],
        'error' => '',
        'success' => ''
      ]);
    }

    // Handle POST request
    $params = $this->request->getAllPost();
    $step = (int) ($params['step'] ?? 1);

    error_log("=== WIZARD DEBUG ===");
    error_log("POST params: " . print_r($params, true));
    error_log("Detected step: " . $step);
    error_log("==================");

    switch ($step) {
      case 2:
        error_log("Executing step 2 (connection test)");
        return $this->handleConnectionTest($params);
      case 3:
        error_log("Executing step 3 (database creation)");
        return $this->handleDatabaseCreation($params);
      case 4:
        error_log("Executing step 4 (table configuration)");
        return $this->handleTableConfiguration($params);
      case 5:
        error_log("Executing step 5 (create tables)");
        return $this->handleCreateTables($params);
      case 6:
        error_log("Executing step 6 (final save)");
        return $this->handleFinalSave($params);
      default:
        error_log("Executing default (step 1)");
        return $this->render('database-wizard.html.twig', [
          'step' => 1,
          'config' => [],
          'error' => '',
          'success' => ''
        ]);
    }
  }

  private function handleConnectionTest($params): Response
  {
    error_log("Starting connection test with params: " . print_r($params, true));

    $config = $this->buildConfig($params);
    error_log("Built config: " . print_r($config, true));

    // Validate required fields
    $validation = $this->validateConfig($config);
    if (!$validation['valid']) {
      error_log("Validation failed: " . $validation['error']);
      return $this->render('database-wizard.html.twig', [
        'step' => 1,
        'config' => $config,
        'error' => $validation['error'],
        'success' => ''
      ]);
    }

    // Test connection
    try {
      error_log("Testing connection for driver: " . $config['driver']);

      if ($config['driver'] === 'sqlite') {
        // For SQLite, just check if we can create/access the file
        $factory = new ConnectionFactory();
        $connection = $factory->create($config);
        $connection->query('SELECT 1');
        error_log("SQLite connection successful");
      } else {
        // For server databases, test connection WITHOUT specifying database
        $testConfig = $config;
        unset($testConfig['database']); // Remove database name for connection test

        error_log("Testing server connection without database: " . print_r($testConfig, true));

        $factory = new ConnectionFactory();
        $connection = $factory->create($testConfig);
        $connection->query('SELECT 1');
        error_log("Server connection successful");
      }

      error_log("Connection test passed, returning step 2");
      return $this->render('database-wizard.html.twig', [
        'step' => 2,
        'config' => $config,
        'error' => '',
        'success' => 'Conexión al servidor establecida correctamente'
      ]);
    } catch (\Exception $e) {
      error_log("Connection test failed: " . $e->getMessage());
      return $this->render('database-wizard.html.twig', [
        'step' => 1,
        'config' => $config,
        'error' => 'Error de conexión: ' . $e->getMessage(),
        'success' => ''
      ]);
    }
  }

  private function handleDatabaseCreation($params): Response
  {
    error_log("Starting database creation with params: " . print_r($params, true));

    $config = $this->buildConfig($params);
    error_log("Config for database creation: " . print_r($config, true));

    try {
      // Create database if it doesn't exist (for server-based databases)
      if ($config['driver'] !== 'sqlite') {
        error_log("Creating database for driver: " . $config['driver']);
        $this->createDatabase($config);
        error_log("Database created successfully");
      } else {
        error_log("SQLite detected, skipping database creation");
      }

      // Test connection with the database
      error_log("Testing connection with database");
      $factory = new ConnectionFactory();
      $connection = $factory->create($config);
      $connection->query('SELECT 1');
      error_log("Connection with database successful");

      return $this->render('database-wizard.html.twig', [
        'step' => 3,
        'config' => $config,
        'error' => '',
        'success' => 'Base de datos creada exitosamente. Ahora configuremos las tablas.'
      ]);
    } catch (\Exception $e) {
      error_log("Database creation failed: " . $e->getMessage());
      error_log("Stack trace: " . $e->getTraceAsString());
      return $this->render('database-wizard.html.twig', [
        'step' => 2,
        'config' => $config,
        'error' => 'Error al crear la base de datos: ' . $e->getMessage(),
        'success' => ''
      ]);
    }
  }

  private function handleTableConfiguration($params): Response
  {
    error_log("Starting table configuration");

    $config = $this->buildConfig($params);

    // If we're coming from step 3, show the table configuration form
    if (!isset($params['table_names']) || empty($params['table_names'])) {
      return $this->render('database-wizard.html.twig', [
        'step' => 4,
        'config' => $config,
        'error' => '',
        'success' => ''
      ]);
    }

    // Process table configuration and show step 5
    $tables = $this->buildTablesConfig($params);
    $config['tables'] = $tables;

    return $this->render('database-wizard.html.twig', [
      'step' => 5,
      'config' => $config,
      'tables' => $tables,
      'error' => '',
      'success' => 'Configuración de tablas procesada correctamente'
    ]);
  }

  private function handleCreateTables($params): Response
  {
    error_log("Starting table creation");

    $config = $this->buildConfig($params);
    $tables = $this->buildTablesConfig($params);

    try {
      $factory = new ConnectionFactory();
      $connection = $factory->create($config);

      foreach ($tables as $table) {
        $this->createCustomTable($connection, $table, $config['driver']);
      }

      error_log("All tables created successfully");

      return $this->render('database-wizard.html.twig', [
        'step' => 5,
        'config' => $config,
        'tables' => $tables,
        'error' => '',
        'success' => 'Todas las tablas han sido creadas exitosamente'
      ]);
    } catch (\Exception $e) {
      error_log("Table creation failed: " . $e->getMessage());
      return $this->render('database-wizard.html.twig', [
        'step' => 4,
        'config' => $config,
        'error' => 'Error al crear las tablas: ' . $e->getMessage(),
        'success' => ''
      ]);
    }
  }

  private function handleFinalSave($params): Response
  {
    error_log("Starting final save with params: " . print_r($params, true));

    $config = $this->buildConfig($params);
    $tables = $this->buildTablesConfig($params);
    $config['tables'] = $tables;

    error_log("Config to save: " . print_r($config, true));

    try {
      $bootstrap = new DatabaseBootstrap();

      // Check if config directory exists and is writable
      $configDir = __DIR__ . '/../../config';
      error_log("Config directory: " . $configDir);

      if (!is_dir($configDir)) {
        error_log("Creating config directory");
        if (!mkdir($configDir, 0755, true)) {
          throw new \Exception("No se pudo crear el directorio config");
        }
      }

      if (!is_writable($configDir)) {
        throw new \Exception("El directorio config no tiene permisos de escritura");
      }

      $bootstrap->saveConfig($config);
      error_log("Configuration saved successfully");

      return $this->render('database-wizard.html.twig', [
        'step' => 6,
        'config' => $config,
        'tables' => $tables,
        'error' => '',
        'success' => 'Configuración guardada exitosamente'
      ]);
    } catch (\Exception $e) {
      error_log("Error saving config: " . $e->getMessage());
      return $this->render('database-wizard.html.twig', [
        'step' => 5,
        'config' => $config,
        'error' => 'Error al guardar la configuración: ' . $e->getMessage(),
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

    // Handle SQLite
    if ($config['driver'] === 'sqlite') {
      $config['database'] = $params['sqlite_path'] ?? 'database/app.sqlite';
      // Clear server-specific fields for SQLite
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
      return ['valid' => false, 'error' => 'Debe seleccionar un driver de base de datos'];
    }

    if ($config['driver'] === 'sqlite') {
      if (empty($config['database'])) {
        return ['valid' => false, 'error' => 'Debe especificar la ruta del archivo SQLite'];
      }
    } else {
      if (empty($config['host'])) {
        return ['valid' => false, 'error' => 'Debe especificar el servidor'];
      }
      if (empty($config['database'])) {
        return ['valid' => false, 'error' => 'Debe especificar el nombre de la base de datos'];
      }
      if (empty($config['username'])) {
        return ['valid' => false, 'error' => 'Debe especificar el usuario'];
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

            // Validation: Auto increment fields should be INT and PRIMARY KEY
            if ($field['auto_increment']) {
              $field['type'] = 'INT';
              $field['primary_key'] = true;
              $field['nullable'] = false;
            }

            // Validation: Primary keys should not be nullable
            if ($field['primary_key']) {
              $field['nullable'] = false;
            }

            $table['fields'][] = $field;
          }
        }

        // Ensure table has at least one field
        if (count($table['fields']) > 0) {
          $tables[] = $table;
        }
      }
    }

    return $tables;
  }

  private function createDatabase($config): void
  {
    error_log("Starting database creation for: " . $config['database']);

    // Connect without database to create it
    $tempConfig = $config;
    unset($tempConfig['database']);

    error_log("Connecting to server without database: " . print_r($tempConfig, true));

    $factory = new ConnectionFactory();
    $connection = $factory->create($tempConfig);

    $dbName = $config['database'];
    $charset = $config['charset'] ?? 'utf8mb4';

    error_log("Creating database: $dbName with charset: $charset");

    switch ($config['driver']) {
      case 'mysql':
        $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset}";
        error_log("Executing SQL: " . $sql);
        break;
      case 'pgsql':
        // PostgreSQL doesn't support IF NOT EXISTS for databases
        try {
          $sql = "CREATE DATABASE \"{$dbName}\"";
          error_log("Executing SQL: " . $sql);
          $connection->execute($sql);
          error_log("PostgreSQL database created successfully");
          return;
        } catch (\Exception $e) {
          error_log("PostgreSQL error: " . $e->getMessage());
          // Database might already exist
          if (strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
          }
          error_log("Database already exists, continuing");
          return;
        }
      case 'sqlsrv':
        $sql = "IF NOT EXISTS(SELECT * FROM sys.databases WHERE name = '{$dbName}') CREATE DATABASE [{$dbName}]";
        error_log("Executing SQL: " . $sql);
        break;
      default:
        throw new \Exception("Database creation not supported for driver: {$config['driver']}");
    }

    try {
      $connection->execute($sql);
      error_log("Database creation SQL executed successfully");
    } catch (\Exception $e) {
      error_log("Error executing database creation SQL: " . $e->getMessage());
      throw $e;
    }
  }

  private function createCustomTable($connection, $table, $driver): void
  {
    $tableName = $table['name'];
    $fields = $table['fields'];

    error_log("Creating table: $tableName");

    if (empty($fields)) {
      throw new \Exception("La tabla '$tableName' debe tener al menos un campo");
    }

    // Different quote styles for different drivers
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

    // Add engine and charset for MySQL
    if ($driver === 'mysql') {
      $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    error_log("Executing SQL: " . $sql);
    $connection->execute($sql);
    error_log("Table '$tableName' created successfully");
  }

  private function buildFieldDefinition($field, $driver): string
  {
    $name = $field['name'];
    $type = $field['type'];
    $length = $field['length'];
    $nullable = $field['nullable'];
    $primaryKey = $field['primary_key'];
    $autoIncrement = $field['auto_increment'];

    // Different quote styles for different drivers
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

    // Build type with length
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

    // Nullable
    if (!$nullable) {
      $definition .= " NOT NULL";
    }

    // Auto increment (driver specific)
    if ($autoIncrement) {
      switch ($driver) {
        case 'mysql':
          $definition .= " AUTO_INCREMENT";
          break;
        case 'pgsql':
          // PostgreSQL uses SERIAL instead
          $definition = "{$quote}{$name}{$closeQuote} SERIAL";
          break;
        case 'sqlite':
          // SQLite AUTOINCREMENT only for INTEGER PRIMARY KEY
          if ($primaryKey) {
            $definition .= " AUTOINCREMENT";
          }
          break;
        case 'sqlsrv':
          $definition .= " IDENTITY(1,1)";
          break;
        default:
          // No auto increment for unknown drivers
          break;
      }
    }

    // Primary key
    if ($primaryKey) {
      $definition .= " PRIMARY KEY";
    }

    return $definition;
  }
}
