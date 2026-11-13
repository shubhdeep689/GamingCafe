<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Admin Logout Action
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

admin_logout();
set_flash('info', 'You have been safely signed out of the command center.');
redirect(BASE_URL . 'admin/login.php');

