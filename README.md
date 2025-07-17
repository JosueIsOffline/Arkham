# Arkham PHP Framework

![Arkham Framework](https://img.shields.io/badge/Arkham-Framework-blue)
![PHP Version](https://img.shields.io/badge/PHP-8.1+-green)
![License](https://img.shields.io/badge/License-MIT-yellow)
![Build Status](https://img.shields.io/badge/Build-Passing-brightgreen)

**Arkham** is a lightweight, modern PHP framework built from scratch following MVC architecture principles. It provides developers with essential tools for rapid web application development while maintaining simplicity and performance.

## 🚀 Features

- **MVC Architecture**: Clean separation of concerns with Models, Views, and Controllers
- **Database Wizard**: Interactive setup wizard for database configuration and table creation
- **Query Builder**: Intuitive fluent interface for database operations
- **Twig Integration**: Powerful templating engine for dynamic views
- **Fast Routing**: High-performance routing system powered by FastRoute
- **Multiple Database Support**: MySQL, PostgreSQL, SQLite, and SQL Server
- **Auto-Setup**: Automatic framework initialization and configuration
- **PHPUnit Testing**: Built-in testing framework support
- **Composer Ready**: PSR-4 autoloading and Composer integration

## 📋 Requirements

- PHP 8.1 or higher
- Composer
- Web server (Apache, Nginx, or built-in PHP server)
- Database server (optional - SQLite works out of the box)

## 🔧 Installation

### Via Composer (Recommended)

```bash
composer create-project arkham-dev/framework my-app
cd my-app
php -S localhost:8000 -t public
```

### Manual Installation

```bash
git clone https://github.com/JosueIsOffline/Arkham.git my-app
cd my-app
composer install
php -S localhost:8000 -t public
```

## 🏁 Quick Start

### 1. Initial Setup

When you first visit your application, Arkham will automatically redirect you to the Database Setup Wizard:

```
http://localhost:8000
```

The wizard will guide you through:
- Database connection configuration
- Database creation
- Table and field setup
- Configuration file generation

### 2. Create Your First Controller

```php
<?php

namespace App\Controllers;

use JosueIsOffline\Framework\Controllers\AbstractController;
use JosueIsOffline\Framework\Http\Response;

class WelcomeController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('welcome.html.twig', [
            'message' => 'Hello from Arkham!'
        ]);
    }
}
```

### 3. Define Routes

Edit `routes/web.php`:

```php
<?php

use App\Controllers\WelcomeController;

return [
    ['GET', '/', [WelcomeController::class, 'index']],
    ['GET', '/welcome/{name}', [WelcomeController::class, 'show']],
    ['POST', '/api/users', [UserController::class, 'store']],
];
```

### 4. Create Views

Create `views/welcome.html.twig`:

```twig
{% extends "base.html.twig" %}

{% block title %}Welcome to Arkham{% endblock %}

{% block content %}
<div class="container">
    <h1>{{ message }}</h1>
    <p>Welcome to the Arkham PHP Framework!</p>
</div>
{% endblock %}
```

## 🗄️ Database Operations

### Using the Query Builder

```php
use JosueIsOffline\Framework\Database\DB;

// Select all users
$users = DB::table('users')->select()->get();

// Find a specific user
$user = DB::table('users')->where('id', 1)->first();

// Insert a new user
DB::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update user
DB::table('users')
    ->where('id', 1)
    ->update(['name' => 'Jane Doe']);

// Delete user
DB::table('users')->where('id', 1)->delete();
```

### Using Models

```php
<?php

namespace App\Models;

use JosueIsOffline\Framework\Model\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
}

// Usage
$users = User::all();
$user = User::find(1);
$activeUsers = User::where('status', 'active');
```

## 📁 Directory Structure

```
my-app/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   └── BookController.php
│   └── Models/
├── config/
│   └── database.json (auto-generated)
├── public/
│   └── index.php
├── routes/
│   └── web.php
├── src/
│   ├── Controllers/
│   ├── Database/
│   ├── Http/
│   ├── Model/
│   ├── Routing/
│   └── View/
├── tests/
├── views/
│   ├── base.html.twig
│   ├── home.html.twig
│   └── book.html.twig
├── composer.json
└── README.md
```

## 🎯 Advanced Features

### Custom Middleware

```php
<?php

namespace App\Middleware;

use JosueIsOffline\Framework\Http\Request;
use JosueIsOffline\Framework\Http\Response;

class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Authentication logic here
        if (!$this->isAuthenticated($request)) {
            return new Response('Unauthorized', 401);
        }
        
        return $next($request);
    }
}
```

### Database Configuration

The framework supports multiple database drivers:

```json
{
    "driver": "mysql",
    "host": "localhost",
    "port": 3306,
    "database": "my_app",
    "username": "root",
    "password": "secret",
    "charset": "utf8mb4",
    "collation": "utf8mb4_unicode_ci"
}
```

### View Helpers

```php
// In your controller
public function show(): Response
{
    return $this->render('user/profile.html.twig', [
        'user' => $user,
        'posts' => $posts
    ]);
}
```

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Example test:

```php
<?php

use PHPUnit\Framework\TestCase;
use JosueIsOffline\Framework\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    public function testSelectQuery()
    {
        $qb = new QueryBuilder($this->connection);
        $sql = $qb->table('users')->select('name', 'email')->toSql();
        
        $this->assertEquals('SELECT name, email FROM users', $sql);
    }
}
```

## 📚 Documentation

### Core Components

- **Kernel**: The heart of the framework, handles HTTP requests
- **Router**: Fast and flexible routing system
- **Controllers**: Handle business logic and return responses
- **Models**: Interact with database using Query Builder
- **Views**: Twig-powered templating system
- **Database**: Multi-driver database abstraction layer

### Configuration

The framework automatically generates configuration files during setup. Manual configuration is also supported:

```php
// Bootstrap database connection
use JosueIsOffline\Framework\Database\DB;

DB::configure([
    'driver' => 'sqlite',
    'database' => 'database/app.sqlite'
]);
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
git clone https://github.com/arkham/framework.git
cd framework
composer install
composer test
```

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **FastRoute** by Nikita Popov for routing
- **Twig** by Fabien Potencier for templating
- **PHPUnit** for testing framework
- The PHP community for inspiration and best practices

## 📞 Support

- **Documentation**: [https://arkham-framework.dev](https://arkham-framework.dev)
- **Issues**: [GitHub Issues](https://github.com/arkham/framework/issues)
- **Discussions**: [GitHub Discussions](https://github.com/arkham/framework/discussions)
- **Email**: support@arkham-framework.dev

## 🗺️ Roadmap

- [ ] Session management
- [ ] Authentication system
- [ ] Cache layer
- [ ] CLI commands
- [ ] Migration system
- [ ] Event system
- [ ] API documentation generator
- [ ] Performance optimization tools

---

**Arkham Framework** - *Building the future of PHP web development, one commit at a time.*

Made with ❤️ by the Arkham Team
