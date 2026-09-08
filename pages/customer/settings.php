<?php
/**
 * TokenFlow Pro — Customer Settings (Redirects to Profile)
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
header('Location: ' . PAGES_URL . 'customer/profile.php');
exit;
