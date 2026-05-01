<?php
/**
 * Nexus Notes - Header Include
 * Common header for all pages
 */

defined('APP_INIT') or define('APP_INIT', true);
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$dateDisplay = getDualCalendarDisplay();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <title><?php echo e($pageTitle ?? APP_NAME); ?> - Premium Note Taking</title>
    <meta name="description" content="Organize your thoughts with Nexus Notes - featuring Ethiopian calendar, dual timezone display, and modern editing experience.">
    <meta name="keywords" content="notes, ethiopian calendar, productivity, note-taking, organization">
    <meta name="author" content="Nexus Notes">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Nexus Notes">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236366f1'><path d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/></svg>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        dark: {
                            bg: '#0f172a',
                            surface: '#1e293b',
                            border: '#334155'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'pulse-slow': 'pulse 3s infinite'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Preload Critical Assets -->
    <link rel="preload" href="assets/js/app.js" as="script">
    
    <?php if (FEATURE_PWA): ?>
    <link rel="manifest" href="manifest.json">
    <?php endif; ?>
</head>
<body class="bg-gray-50 dark:bg-dark-bg text-gray-900 dark:text-gray-100 min-h-screen transition-colors duration-300">
    <!-- Top Bar with Date/Time Display -->
    <header class="sticky top-0 z-50 bg-white/80 dark:bg-dark-surface/80 backdrop-blur-lg border-b border-gray-200 dark:border-dark-border shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Navigation -->
                <div class="flex items-center gap-4">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg group-hover:shadow-primary-500/25 transition-shadow">
                            <i data-lucide="notebook-tabs" class="w-6 h-6 text-white"></i>
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent hidden sm:block">
                            Nexus Notes
                        </span>
                    </a>
                    
                    <nav class="hidden md:flex items-center gap-1 ml-4">
                        <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-dark-border transition-colors <?php echo $currentPage === 'index' ? 'text-primary-600 bg-primary-50 dark:bg-primary-900/20' : ''; ?>">
                            <i data-lucide="layout-grid" class="w-4 h-4 inline mr-1"></i>
                            Notes
                        </a>
                        <a href="folders.php" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-dark-border transition-colors <?php echo $currentPage === 'folders' ? 'text-primary-600 bg-primary-50 dark:bg-primary-900/20' : ''; ?>">
                            <i data-lucide="folder-tree" class="w-4 h-4 inline mr-1"></i>
                            Folders
                        </a>
                        <a href="tags.php" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-dark-border transition-colors <?php echo $currentPage === 'tags' ? 'text-primary-600 bg-primary-50 dark:bg-primary-900/20' : ''; ?>">
                            <i data-lucide="tags" class="w-4 h-4 inline mr-1"></i>
                            Tags
                        </a>
                    </nav>
                </div>
                
                <!-- Date/Time Display -->
                <div class="flex items-center gap-4 text-xs sm:text-sm">
                    <div class="hidden lg:flex flex-col items-end text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300"><?php echo e($dateDisplay['ethiopian']); ?></span>
                        <span class="text-xs"><?php echo e($dateDisplay['gregorian']); ?></span>
                    </div>
                    <div class="hidden sm:flex flex-col items-end text-gray-500 dark:text-gray-400 border-l border-gray-200 dark:border-dark-border pl-4">
                        <span class="font-mono">UTC+9: <span class="text-gray-700 dark:text-gray-300"><?php echo e($dateDisplay['utc9']); ?></span></span>
                        <span class="font-mono text-xs">UTC+3: <?php echo e($dateDisplay['utc3']); ?></span>
                    </div>
                    
                    <!-- Theme Toggle -->
                    <button id="themeToggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-dark-border transition-colors" aria-label="Toggle theme">
                        <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
                        <i data-lucide="moon" class="w-5 h-5 block dark:hidden"></i>
                    </button>
                    
                    <!-- Mobile Menu Button -->
                    <button id="mobileMenuBtn" class="md:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-dark-border transition-colors">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-gray-200 dark:border-dark-border bg-white dark:bg-dark-surface">
            <div class="px-4 py-3 space-y-2">
                <a href="index.php" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-dark-border">Notes</a>
                <a href="folders.php" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-dark-border">Folders</a>
                <a href="tags.php" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-dark-border">Tags</a>
                <div class="pt-2 border-t border-gray-200 dark:border-dark-border">
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                        <p>Ethiopian: <?php echo e($dateDisplay['ethiopian']); ?></p>
                        <p>Gregorian: <?php echo e($dateDisplay['gregorian']); ?></p>
                        <p>UTC+9: <?php echo e($dateDisplay['utc9']); ?> | UTC+3: <?php echo e($dateDisplay['utc3']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
