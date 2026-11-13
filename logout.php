<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Gamer Logout Script
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

customer_logout();
set_flash('info', 'You have been signed out. See you in the next match, GG!');
redirect(BASE_URL . 'login.php');

