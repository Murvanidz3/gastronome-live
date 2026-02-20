<?php
/**
 * Shared Header — HTML head + navigation sidebar.
 * Include after auth/guard.php in every protected page.
 *
 * Set $pageTitle and $activePage before including.
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';

// Base URL for all absolute links
$baseUrl = '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> — Gastronome</title>
    <meta name="description" content="Gastronome Product Management System — Admin Dashboard">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header" style="padding: 20px 15px 10px 15px; text-align: center;">
            <a href="<?php echo $baseUrl; ?>/index.php" style="display: inline-block;">
                <img src="<?php echo $baseUrl; ?>/img/invlogo.png" alt="Gastronome Logo"
                    style="max-width: 100%; max-height: 80px; object-fit: contain;">
            </a>
        </div>
        <ul class="sidebar-nav">
            <li>
                <a href="<?php echo $baseUrl; ?>/index.php"
                    class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Products</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $baseUrl; ?>/companies.php"
                    class="<?php echo $activePage === 'companies' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-text">Companies</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $baseUrl; ?>/csv-import.php"
                    class="<?php echo $activePage === 'csv-import' ? 'active' : ''; ?>">
                    <span class="nav-icon">📥</span>
                    <span class="nav-text">CSV Import</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $baseUrl; ?>/invoice.php"
                    class="<?php echo $activePage === 'invoice' ? 'active' : ''; ?>">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-text">Invoice</span>
                </a>
            </li>
            <?php if (isset($_SESSION['username']) && strtolower($_SESSION['username']) === 'masho'): ?>
                <li>
                    <a href="<?php echo $baseUrl; ?>/profile.php"
                        class="<?php echo $activePage === 'profile' ? 'active' : ''; ?>">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">Manage Profile</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer">
            <?php if (isset($_SESSION['user_name'])): ?>
                <div class="sidebar-user">
                    <span class="user-label">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            <?php endif; ?>
            <a href="<?php echo $baseUrl; ?>/auth/logout.php" class="logout-link">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </nav>

    <!-- Mobile menu toggle -->
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle menu">☰</button>

    <!-- Main Content Wrapper -->
    <main class="main-content">