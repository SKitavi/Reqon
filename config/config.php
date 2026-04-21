<?php
//Reqon Central Configuration

//Start session
session_start();

//Error reporting (Disable this in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

//Timezone
date_default_timezone_set('Africa/Nairobi');

//Load env variables from .env file
if (file_exists(__DIR__.'/../.env')) {
    $env = parse_ini_file (__DIR__.'/../.env');
    foreach($env as $key => $value) {
        define($key, $value);
    }
} else {
    //defaults if .env doesn't exist
    define('DB_HOST','localhost');
    define('DB_NAME','reqon_db');
    define('DB_USER','root');
    define('APP_NAME','Reqon');
    define('APP_ENV','development');
    define('APP_DEBUG', true );
    define('APP_URL','http://localhost/Reqon/');
}

//Define paths
define('ROOT_PATH',dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

//Define URLs
define('BASE_URL', APP_URL);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

//Database configuration
define('DB_CHARSET','utf8mb4');

//Session configuration
define('SESSION_LIFETIME',1800); //in seconds so 30 min
define('SESSION_NAME','reqon_session');

//Application settings
define('ITEMS_PER_PAGE', 20);
define('DATE_FORMAT','Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT','d/m/Y');

//File upload settings
define('UPLOAD_MAX_SIZE', 5242880); //in bytes so 5MB 
define('ALLOWED_FILE_TYPES', ['jpg','jpeg','png', 'pdf','doc', 'docx']);

//Security settings
define('HASH_COST', 12);

//Requisition types
define('REQUISITION_TYPES', [
    'personnel' => 'Personnel',
    'procurement' => 'Procurement',
    'it_asset' => 'IT Asset',
    'merchandise' => 'Merchandise'
]);

//Requisition statuses
define('REQUISITION_STATUSES', [
    'pending' => 'Pending',
    'in_review' => 'In Review',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'cancelled' => 'Cancelled'
]);

//User roles
define('USER_ROLES', [
    1 => 'System Admin',
    2 => 'HR Admin',
    3 => 'Approver',
    4 => 'Requester'
]);

//Include database connection
require_once CONFIG_PATH . '/database.php';

//Include helper functions
require_once INCLUDES_PATH . '/functions.php';

?>