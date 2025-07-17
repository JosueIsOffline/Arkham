<?php

namespace JosueIsOffline\Framework\Database;

use JosueIsOffline\Framework\Database\DB;

class DatabaseBootstrap
{
  private function getConfigPath(): string
  {
    return defined('BASE_PATH') ? BASE_PATH . '/config/database.json' : __DIR__ . '/../../config/database.json';
  }

  private function getConfigDir(): string
  {
    return defined('BASE_PATH') ? BASE_PATH . '/config' : __DIR__ . '/../../config';
  }

  public function boot(): void
  {
    if ($this->configFileExist()) {
      $config = $this->loadConfig();
      DB::configure($config);
    }
  }

  private function loadConfig(): array
  {
    $configPath = $this->getConfigPath();
    $content = file_get_contents($configPath);
    return json_decode($content, true);
  }

  public function configFileExist(): bool
  {
    return file_exists($this->getConfigPath());
  }

  public function saveConfig(array $config): void
  {
    $configDir = $this->getConfigDir();
    $configFile = $this->getConfigPath();

    if (!is_dir($configDir)) {
      if (!mkdir($configDir, 0755, true)) {
        throw new \Exception("Could not create config directory: " . $configDir);
      }
    }

    if (!is_writable($configDir)) {
      throw new \Exception("Config directory is not writable: " . $configDir);
    }

    $jsonContent = json_encode($config, JSON_PRETTY_PRINT);

    if ($jsonContent === false) {
      throw new \Exception("Could not encode config to JSON: " . json_last_error_msg());
    }

    $result = file_put_contents($configFile, $jsonContent);

    if ($result === false) {
      throw new \Exception("Could not write config file: " . $configFile);
    }

    if (!file_exists($configFile)) {
      throw new \Exception("Config file was not created: " . $configFile);
    }
  }

  // private function testConnection(array $config = []): bool
  // {
  //   try {
  //     $factory = new ConnectionFactory();
  //     $connection = $factory->create($config);
  //     $connection->query('SELECT 1');
  //     return true;
  //   } catch (\Throwable $e) {
  //     return false;
  //   }
  // } 
}
