<?php
/**
 * TokenFlow Pro — Entry Point
 */
require_once __DIR__ . '/includes/helpers.php';
tfInit();

// Redirect based on auth state
if (Auth::isLoggedIn()) {
    header('Location: ' . Auth::getDashboardUrl());
} else {
    header('Location: ' . PAGES_URL . 'public/login.php');
}
exit;
