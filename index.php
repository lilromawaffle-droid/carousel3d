<?php
// index.php
define('BASEPATH', __DIR__);
define('APPPATH', BASEPATH . '/application');

// 1. Load Database Config
require_once APPPATH . '/config/database.php';

// 2. Setup Database Connection (PDO)
try {
    $dsn = "mysql:host={$db_config['hostname']};dbname={$db_config['database']};charset=utf8";
    $db = new PDO($dsn, $db_config['username'], $db_config['password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage() . "<br>Pastikan database '{$db_config['database']}' sudah dibuat di XAMPP phpMyAdmin.");
}

// 3. Load Core Classes
require_once APPPATH . '/core/Model.php';
require_once APPPATH . '/core/Controller.php';

// 4. Simple Routing (CI3 style ?c=Controller&m=method)
$controller_name = isset($_GET['c']) ? ucfirst($_GET['c']) : 'Home';
$method_name = isset($_GET['m']) ? $_GET['m'] : 'index';

$controller_file = APPPATH . '/controllers/' . $controller_name . '.php';

if (file_exists($controller_file)) {
    require_once $controller_file;
    if (class_exists($controller_name)) {
        // Pass DB to controller so it can pass it to models
        $controller = new $controller_name($db);
        if (method_exists($controller, $method_name)) {
            $controller->$method_name();
        } else {
            die("Method $method_name not found in Controller $controller_name");
        }
    } else {
        die("Class $controller_name not found");
    }
} else {
    die("Controller $controller_name not found");
}
