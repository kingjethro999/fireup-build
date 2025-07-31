<?php

/**
 * FireUp PHP Build - Application Creator
 * 
 * Usage: php create-app.php my-app-name
 */

if ($argc < 2) {
    echo "❌ Error: Please provide an application name\n";
    echo "Usage: php create-app.php <app-name>\n";
    echo "Example: php create-app.php my-blog\n";
    exit(1);
}

$appName = $argv[1];
$appDir = getcwd() . '/' . $appName;

if (is_dir($appDir)) {
    echo "❌ Error: Directory '$appName' already exists\n";
    exit(1);
}

echo "🚀 Creating new FireUp PHP Build application: $appName\n";
echo "=====================================\n";

// Create application directory
mkdir($appDir);
echo "✅ Created directory: $appName\n";

// Copy template files
$templateFiles = [
    'template/composer.json' => 'composer.json',
    'template/public/index.php' => 'public/index.php',
    'template/src/App.php' => 'src/App.php',
    'template/artisan' => 'artisan',
    'template/README.md' => 'README.md',
    '.env.example' => '.env.example',
    '.gitignore' => '.gitignore'
];

foreach ($templateFiles as $source => $dest) {
    if (file_exists($source)) {
        $destPath = $appDir . '/' . $dest;
        $destDir = dirname($destPath);
        
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        copy($source, $destPath);
        echo "✅ Created: $dest\n";
    }
}

// Update composer.json with app name
$composerPath = $appDir . '/composer.json';
if (file_exists($composerPath)) {
    $composerContent = file_get_contents($composerPath);
    $composerContent = str_replace('fireup/php-app', $appName . '/' . $appName, $composerContent);
    $composerContent = str_replace('A clean PHP application built with FireUp PHP Build', 'A ' . $appName . ' application built with FireUp PHP Build', $composerContent);
    file_put_contents($composerPath, $composerContent);
}

// Create .env file
$envContent = "# Application Configuration\n";
$envContent .= "APP_ENV=development\n";
$envContent .= "APP_DEBUG=true\n";
$envContent .= "APP_URL=http://localhost:8000\n\n";
$envContent .= "# Database Configuration (if needed)\n";
$envContent .= "DB_HOST=localhost\n";
$envContent .= "DB_NAME=" . $appName . "\n";
$envContent .= "DB_USER=root\n";
$envContent .= "DB_PASS=\n\n";
$envContent .= "# Security\n";
$envContent .= "APP_KEY=" . bin2hex(random_bytes(32)) . "\n";

file_put_contents($appDir . '/.env', $envContent);
echo "✅ Created: .env\n";

// Create additional directories
$directories = ['config', 'templates', 'logs', 'src/Controllers', 'src/Models', 'src/Views'];
foreach ($directories as $dir) {
    $fullPath = $appDir . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
        echo "✅ Created directory: $dir\n";
    }
}

echo "\n🎉 Application '$appName' created successfully!\n";
echo "=====================================\n";
echo "📁 Location: $appDir\n";
echo "🚀 Next steps:\n";
echo "   cd $appName\n";
echo "   composer install\n";
echo "   php artisan serve\n";
echo "   php artisan chat\n";
echo "\n💡 The chat interface will help you build your application!\n"; 