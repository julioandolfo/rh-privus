<?php
/**
 * Página Inicial - Redireciona para Dashboard
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

header('Location: pages/dashboard.php');
exit;

