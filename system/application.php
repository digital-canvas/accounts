<?php
// Define Paths
if (!defined('SYSTEM')) {
    define("SYSTEM", __DIR__);
}
if (!defined('APP_ROOT')) {
    define("APP_ROOT", dirname(SYSTEM));
}
if (!defined('LIBRARY')) {
    define("LIBRARY", SYSTEM . DIRECTORY_SEPARATOR . 'library');
}
if (!defined('VENDOR')) {
    define("VENDOR", SYSTEM . DIRECTORY_SEPARATOR . 'vendor');
}
if (!defined('WEBROOT')) {
    define('WEBROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'public');
}
if (!defined('TESTDIR')) {
    define('TESTDIR', APP_ROOT . DIRECTORY_SEPARATOR . 'tests');
}

// Load Config
$config = require(SYSTEM . '/config.php');

if (!defined('SERVER_MODE')) {
    $mode = 'production';
    if (isset($config['mode']) && $config['mode']) {
        $mode = mb_strtolower($config['mode']);
    }
    if (isset($_SERVER['HTTP_X_SERVER_MODE'])) {
        $mode = mb_strtolower($_SERVER['HTTP_X_SERVER_MODE']);
    }
    if (getenv('SLIM_MODE')) {
        $mode = mb_strtolower(getenv('SLIM_MODE'));
    }
    if (!in_array($mode, array('development', 'production', 'test'))) {
        $mode = 'production';
    }
    define('SERVER_MODE', $mode);
}


require(VENDOR . '/autoload.php');

if (SERVER_MODE == 'test') {
    $config['base_url'] = $config['test']['base_url'];
    $config['api_url'] = $config['test']['api_url'];
    // Init DB
    $dbh = Test\Database::initTestDatabase(TESTDIR . '/_data/schema.sql');
    Model\BaseModel::setCredentials(
        array(
             'phptype' => 'sqlite',
             'database' => ':memory:'
        )
    );
    Model\BaseModel::setConnection($dbh);
} else {
    // Init DB
    Model\BaseModel::setCredentials(
        array(
             'phptype' => 'sqlite',
             'database' => SYSTEM . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'passwords.db3'
        )
    );
    if (!file_exists(
        SYSTEM . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'passwords.db3'
    )
    ) {
        $dbh = Model\BaseModel::getConnection();
        Test\Database::initTestDatabase(SYSTEM . '/data/schema.sql', $dbh);
    }
}
