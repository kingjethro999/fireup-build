# 🚀 FireUp PHP Build

**Interactive PHP Development Tool for Creating Clean, Well-Structured Applications**

FireUp PHP Build is a comprehensive PHP development package that helps developers create clean, maintainable PHP applications through an interactive chat interface and powerful CLI tools.

## ✨ Features

- **🤖 Interactive Chat Interface** - Natural language development with AI assistance
- **🏗️ Project Generation** - Create complete MVC applications with one command
- **📝 Code Generation** - Generate classes, controllers, models, and functions
- **🐛 Smart Debugging** - Automatic error detection and fixing
- **📁 File Management** - Create, edit, and manage project files
- **⚡ Development Server** - Built-in server for rapid development
- **🔧 Build System** - Optimize and compile applications for production

## 🚀 Quick Start

### Installation

```bash
composer create-project fireup/php-build my-php-app
cd my-php-app
```

### Interactive Development

```bash
# Start interactive chat (CLI)
php artisan chat

# Start web chat interface (opens .chat/interface.html)
php artisan chat --web

# Using composer scripts
composer chat
```

### Basic Commands

```bash
# Create a new project
php artisan create project my-awesome-app

# Start development server (serves user's index.php)
php artisan serve

# Build for production
php artisan build --production

# Debug your code
php artisan debug

# Using composer scripts
composer serve
composer build
```

## 💬 Interactive Chat Examples

The chat interface understands natural language requests:

```
You: Create a new PHP project called "blog"
AI: ✅ Project 'blog' created successfully with complete MVC structure!

You: Create a User model with properties name, email, password
AI: ✅ Generated User model with properties: name, email, password

You: Create a UserController with CRUD methods
AI: ✅ Generated UserController with index, show, create, store methods

You: Debug this file for syntax errors
AI: 🐛 Found and fixed 2 syntax errors in UserController.php
```

## 📁 Project Structure

When you create a new project, you get a clean, well-organized structure:

```
my-php-app/
├── public/              # Web root
│   └── index.php       # Entry point
├── src/                # Application source
│   ├── App.php         # Main application class
│   ├── Controllers/    # Controllers
│   └── Models/         # Models
├── config/             # Configuration files
├── templates/          # View templates
├── vendor/             # Dependencies
├── bin/                # CLI tools
│   └── php-build       # Main CLI executable
├── composer.json       # Project configuration
└── README.md          # Project documentation
```

## 🛠️ Available Commands

### Chat Commands
- `php artisan chat` - Interactive CLI chat
- `php artisan chat --web` - Web chat interface (opens .chat/interface.html)
- `php artisan chat "Create a User model"` - Direct request

### Project Commands
- `php artisan create project <name>` - Create new project
- `php artisan create controller <name>` - Create controller
  - `php artisan create model <name>` - Create model
  - `php artisan create class <name>` - Create class

### Development Commands
- `php bin/php-build serve` - Start development server
- `php bin/php-build serve --port 8080` - Custom port
- `php bin/php-build build` - Build application
- `php bin/php-build build --optimize` - Optimized build
- `php bin/php-build build --production` - Production build

### Debug Commands
- `php artisan debug` - Debug entire project
- `php artisan debug <file>` - Debug specific file
- `php artisan debug --fix` - Auto-fix issues
- `php artisan debug --verbose` - Detailed output

## 🎯 Use Cases

### For Beginners
- Learn PHP development through interactive guidance
- Generate boilerplate code automatically
- Get instant feedback on code quality
- Build complete applications step by step

### For Experienced Developers
- Rapidly prototype new features
- Maintain consistent code structure
- Automate repetitive development tasks
- Debug complex applications efficiently

### For Teams
- Standardize project structure
- Enforce coding conventions
- Share development knowledge
- Accelerate onboarding process

## 🔧 Configuration

### Environment Variables

Create a `.env` file in your project root:

```env
# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (if needed)
DB_HOST=localhost
DB_NAME=myapp
DB_USER=root
DB_PASS=

# Security
APP_KEY=your-secret-key-here
```

### Custom Templates

You can customize the generated code by modifying the templates in the `src/Services/CodeGenerator.php` file.

## 🚀 Advanced Features

### Web Interface

Start the web interface for a rich development experience:

```bash
php artisan chat --web --port 8000
```

Features:
- Real-time chat interface
- Project file explorer
- Syntax validation
- Quick action buttons
- Chat history export

### Build Optimization

Optimize your application for production:

```bash
php artisan build --production --optimize
```

This will:
- Minify CSS and JavaScript
- Remove development files
- Optimize autoloader
- Set production environment
- Disable error reporting

### File Watching

Watch for changes and auto-rebuild:

```bash
php artisan build --watch
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- 📖 [Documentation](https://fireup-php-build.com/docs)
- 💬 [Discord Community](https://discord.gg/fireup-php-build)
- 🐛 [Issue Tracker](https://github.com/fireup/php-build/issues)
- 📧 [Email Support](mailto:support@fireup-php-build.com)

## 🙏 Acknowledgments

- Built with [Symfony Console](https://symfony.com/doc/current/components/console.html)
- Styled with [Tailwind CSS](https://tailwindcss.com/)
- Powered by modern PHP 8.0+ features

---

**Made with ❤️ by the FireUp Team** 