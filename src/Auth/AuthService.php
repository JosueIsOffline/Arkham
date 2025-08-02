<?php

namespace JosueIsOffline\Framework\Auth;

use JosueIsOffline\Framework\Database\DB;

class AuthService
{
  private const SESSION_KEY = '_auth_user';

  public function __construct()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }

  public function loginById(int $userId): bool
  {
    $user = DB::table('users')
      ->where('id', $userId)
      ->where('is_active', 1)
      ->first();


    if ($user) {
      $this->login($user);
      return true;
    }

    return false;
  }

  public function attempt(string $email, string $password): bool
  {
    $user = DB::table('users')
      ->where('email', $email)
      ->where('is_active', 1)
      ->first();

    if ($user && password_verify($password, $user['password'])) {
      $this->login($user);
      return true;
    }
    return false;
  }

  public function login(array $user): void
  {
    $_SESSION[self::SESSION_KEY] = $user;
    session_regenerate_id(true);
  }

  public function logout(): void
  {
    unset($_SESSION[self::SESSION_KEY]);
    session_destroy();
  }

  public function user(): ?array
  {
    return $_SESSION[self::SESSION_KEY] ?? null;
  }

  public function check(): bool
  {
    return isset($_SESSION[self::SESSION_KEY]);
  }

  public function guest(): bool
  {
    return !$this->check();
  }

  public function hasRole(string $role): bool
  {
    $user = $this->user();
    if (!$user) return false;

    $userRole = DB::table('roles')->where('id', $user['role_id'])->first();
    return $userRole && $userRole['name'] === $role;
  }

  public function hasPermission(string $permission): bool
  {
    $user = $this->user();
    if (!$user) return false;

    $userRole = DB::table('roles')->where('id', $user['role_id'])->first();
    if (!$userRole) return false;

    $permissions = json_decode($userRole['permissions'], true) ?? [];
    return in_array($permission, $permissions);
  }

  public function id(): ?int
  {
    $user = $this->user();
    return $user ? (int)$user['id'] : null;
  }

  public function role(): ?array
  {
    $user = $this->user();
    if (!$user) return null;

    return DB::table('roles')->where('id', $user['role_id'])->first();
  }
}
