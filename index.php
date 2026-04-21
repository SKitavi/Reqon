<?php
/**
 * Main entry point and router(redirects to login or dashboard)
 */

//Load configuration
require_once __DIR__ . '/config/config.php';

//Get requested route
$route = isset($_GET['route'])?$_GET['route']:'home';
$route = filter_var($route, FILTER_SANITIZE_STRING);

//Parse route
$parts = explode('/', $route);
$module = isset($parts[0]) && !empty($parts[0]) ? $parts[0] : 'home';
$action = isset($parts[1]) && !empty($parts[1]) ? $parts[1] : 'index';

//Define allowed routes
$allowedRoutes = [
    'home' => 'modules/auth/login.php',
    'login'=> 'modules/auth/login.php',
    'logout'=> 'modules/auth/logout.php',
    'dashboard'=> 'modules/dashboard/dashboard.php',
    'requisitions'=> 'modules/requisitions/index.php',
    'approvals'=> 'modules/approvals/index.php',
    'reports'=> 'modules/reports/index.php',
    'admin'=> 'modules/admin/index.php'
];

//Route to appropriate controller
if ($module === 'home' || $module === '' || $module === 'login') {
    //Show login page
    if (file_exists('modules/auth/login.php')) {
        require_once 'modules/auth/login.php';
    } else {
        showWelcomePage();
    }
} elseif ($module === 'test') {
    //Test page to verify setup
    showTestPage();
} else {
    //For now, show welcome page
    showWelcomePage();
}

//Welcome page(delete in production)
function showWelcomePage() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo APP_NAME; ?> - Setup Complete</title>
        <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 40px;
                max-width: 600px;
                width: 100%;
            }
            h1 {
                color: #1976D2;
                margin-bottom: 10px;
                font-size: 32px;
            }
            .subtitle {
                color: #666;
                margin-bottom: 30px;
                font-size: 16px;
            }
            .success-box {
                background: #e8f5e9;
                border-left: 4px solid #4caf50;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .success-box h3 {
                color: #2e7d32;
                margin-bottom: 10px;
            }
            .info-box {
                background: #e3f2fd;
                border-left: 4px solid #2196f3;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .info-box h3 {
                color: #1976d2;
                margin-bottom: 10px;
            }
            .checklist {
                list-style: none;
                margin: 15px 0;
            }
            .checklist li {
                padding: 8px 0;
                padding-left: 30px;
                position: relative;
            }
            .checklist li:before {
                content: "✓";
                position: absolute;
                left: 0;
                color: #4caf50;
                font-weight: bold;
                font-size: 18px;
            }
            .btn {
                display: inline-block;
                background: #1976D2;
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                margin-top: 20px;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #0D47A1;
            }
            .footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                text-align: center;
                color: #999;
                font-size: 14px;
            }
            code {
                background: #f5f5f5;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                color: #d32f2f;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎉 <?php echo APP_NAME; ?></h1>
            <p class="subtitle">Integrated Requisition Management System</p>
            
            <div class="success-box">
                <h3>✓ Setup Complete!</h3>
                <p>Your project structure is ready and the server is running successfully.</p>
            </div>
            
            <div class="info-box">
                <h3>📋 Project Information</h3>
                <ul class="checklist">
                    <li>Application Name: <?php echo APP_NAME; ?></li>
                    <li>Environment: <?php echo APP_ENV; ?></li>
                    <li>Base URL: <?php echo APP_URL; ?></li>
                    <li>PHP Version: <?php echo phpversion(); ?></li>
                    <li>Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
                </ul>
            </div>
            
            <div class="info-box">
                <h3>🔧 Next Steps</h3>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li>Create database: <code>reqon_db</code></li>
                    <li>Import schema: <code>database/schema.sql</code></li>
                    <li>Copy <code>.env.example</code> to <code>.env</code></li>
                    <li>Update database credentials in <code>.env</code></li>
                    <li>Test database connection: <a href="test">Click here</a></li>
                    <li>Start building features!</li>
                </ol>
            </div>
            
            <a href="test" class="btn">Test Database Connection →</a>
            
            <div class="footer">
                <p>Reqon v1.0 | Final Year Project 2026</p>
                <p>Isuzu East Africa Limited</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

//Database test page(delete in production)
function showTestPage() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo APP_NAME; ?> - Database Test</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                background: #f5f5f5;
                padding: 40px 20px;
            }
            .container {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                padding: 40px;
                max-width: 800px;
                margin: 0 auto;
            }
            h1 {
                color: #1976D2;
                margin-bottom: 30px;
            }
            .test-result {
                padding: 15px;
                margin: 15px 0;
                border-radius: 6px;
                border-left: 4px solid;
            }
            .test-result.success {
                background: #e8f5e9;
                border-color: #4caf50;
                color: #2e7d32;
            }
            .test-result.error {
                background: #ffebee;
                border-color: #f44336;
                color: #c62828;
            }
            .back-btn {
                display: inline-block;
                margin-top: 20px;
                color: #1976D2;
                text-decoration: none;
            }
            pre {
                background: #f5f5f5;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 Database Connection Test</h1>
            
            <?php
            try {
                // Test database connection
                $db = getDB();
                echo '<div class="test-result success">';
                echo '<h3>✓ Database Connection Successful!</h3>';
                echo '<p>Successfully connected to database: <strong>' . DB_NAME . '</strong></p>';
                echo '</div>';
                
                // Test query
                try {
                    $stmt = $db->query("SELECT DATABASE() as db_name, VERSION() as version");
                    $result = $stmt->fetch();
                    
                    echo '<div class="test-result success">';
                    echo '<h3>✓ Database Query Test Passed</h3>';
                    echo '<pre>';
                    echo "Database: " . $result['db_name'] . "\n";
                    echo "MySQL Version: " . $result['version'];
                    echo '</pre>';
                    echo '</div>';
                } catch (PDOException $e) {
                    echo '<div class="test-result error">';
                    echo '<h3>✗ Query Test Failed</h3>';
                    echo '<p>' . $e->getMessage() . '</p>';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="test-result error">';
                echo '<h3>✗ Database Connection Failed</h3>';
                echo '<p>' . $e->getMessage() . '</p>';
                echo '<p><strong>Troubleshooting:</strong></p>';
                echo '<ul>';
                echo '<li>Make sure XAMPP MySQL is running</li>';
                echo '<li>Verify database name in .env file</li>';
                echo '<li>Check username and password</li>';
                echo '<li>Create the database if it doesn\'t exist</li>';
                echo '</ul>';
                echo '</div>';
            }
            ?>
            
            <a href="<?php echo APP_URL; ?>" class="back-btn">← Back to Home</a>
        </div>
    </body>
    </html>
    <?php
}
?>