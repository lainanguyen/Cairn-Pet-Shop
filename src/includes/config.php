<?php

// Database configuration
define('DB_SERVER', '54.165.204.136');
define('DB_USERNAME', 'group4');
define('DB_PASSWORD', '615fre907ncX8]');
define('DB_NAME', 'group4');

// Application configuration
//define('SITE_NAME', 'Blue Collar Pets');
define('BASE_URL', '/'); // Update this based on your server configuration

// Error reporting - set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
//date_default_timezone_set('America/New York');

// Directory paths
define('ROOT_DIR', dirname(__DIR__));
define('INCLUDES_DIR', ROOT_DIR . '/includes');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
