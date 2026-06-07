<?php


session_start();
include('ini.php');

$_SESSION['userlang'] = isset($_GET['userlang'])
    ? $_GET['userlang']
    : (isset($_SESSION['userlang']) ? $_SESSION['userlang'] : 'de');
$userlang = strtolower($_SESSION['userlang']);

$lang = [
    'de' => [
        'SETUP_HEAD'    => 'Installationsroutine für LMO-IconDataBase',
        'SETUP_DB_HOST' => 'DB-Host (meistens "localhost")',
        'SETUP_DB_USER' => 'DB-User',
        'SETUP_DB_PASS' => 'DB-Passwort',
        'SETUP_DB_NAME' => 'DB-Name',
        'SETUP_SUCCESS' => 'Installation erfolgreich',
        'NEXT'          => 'weiter',
        'GO_ADMIN'      => 'Zum Adminbereich',
    ],
    'en' => [
        'SETUP_HEAD'    => 'Installation for LMO-IconDataBase',
        'SETUP_DB_HOST' => 'DB-Host (mostly "localhost")',
        'SETUP_DB_USER' => 'DB-User',
        'SETUP_DB_PASS' => 'DB-Password',
        'SETUP_DB_NAME' => 'DB-Name',
        'SETUP_SUCCESS' => 'Installation successful',
        'NEXT'          => 'next',
        'GO_ADMIN'      => 'Go to admin area',
    ],
];

// Exit, if config-file exists
if (file_exists('cfg.php')) {
    exit('bereits installiert / already installed');
}

$step = isset($_GET['step']) ? $_GET['step'] : '0';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?php echo htmlspecialchars($userlang) ?>">
  <head>
    <title><?php echo $lang[$userlang]['SETUP_HEAD'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
<?php

// initial startpage
if ($step === '0') {
?>
  <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>?step=1">
    <div class="container">
      <div class="jumbotron jumbotron-fluid">
        <h3><?php echo $lang[$userlang]['SETUP_HEAD'] ?></h3>
      </div>
      <div class="row p-1">
        <div class="col-3"><?php echo $lang[$userlang]['SETUP_DB_HOST'] ?></div><div class="col-2"><input class="form-control" type="text" name="dbhost" placeholder="localhost"></div>
      </div>
      <div class="row p-1">
        <div class="col-3"><?php echo $lang[$userlang]['SETUP_DB_USER'] ?></div><div class="col-2"><input class="form-control" type="text" name="dbuser"></div>
      </div>
      <div class="row p-1">
        <div class="col-3"><?php echo $lang[$userlang]['SETUP_DB_PASS'] ?></div><div class="col-2"><input class="form-control" type="password" name="dbpass"></div>
      </div>
      <div class="row p-1">
        <div class="col-3"><?php echo $lang[$userlang]['SETUP_DB_NAME'] ?></div><div class="col-2"><input class="form-control" type="text" name="dbname"></div>
      </div>
      <div class="row">
        <div class="col"><input class="btn btn-primary btn-sm" type="submit" value="<?php echo $lang[$userlang]['NEXT'] ?>"></div>
      </div>
    </div>
  </form>
  <?php
    foreach ($lang as $arr => $keys) {
        echo "<a href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "?userlang=$arr'>$arr</a> ";
    }
}

// save information in cfg.php
if ($step === '1') {
    $dbhost = $_POST['dbhost'] ?? 'localhost';
    $dbuser = $_POST['dbuser'] ?? '';
    $dbpass = $_POST['dbpass'] ?? '';
    $dbname = $_POST['dbname'] ?? '';

    $config = "<?php\n"
        . "//database settings\n"
        . "define('LMOID_DB_HOST', '" . addslashes($dbhost) . "');\n"
        . "define('LMOID_DB_USER', '" . addslashes($dbuser) . "');\n"
        . "define('LMOID_DB_PASS', '" . addslashes($dbpass) . "');\n"
        . "define('LMOID_DB', '"      . addslashes($dbname) . "');\n"
        . "\n"
        . "// URL settings\n"
        . "define('ICON_PATH', str_replace('\\\\', '/', dirname(__FILE__)) . '/');\n"
        . "define('ICON_DIR',  ICON_PATH . 'icons/');\n"
        . "define('ICON_URL', '" . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/icons/');\n"
        . "define('IMG_TYPES', '.svg,.png');\n"
        . "\n"
        . "// other stuff\n"
        . "define('MAX_RESULTS_PER_PAGE', 40);     // Max Icons/Zip-Datei\n"
        . "define('MAXIMUM_ICONS_PER_ZIP', 40);    // Max Icons/Zip-Datei\n"
        . "define('MAXIMUM_SEARCH_RESULTS', 500);  // Max Suchergebnisse\n"
        . "\n"
        . "// needed files\n"
        . "require_once('functions/html_output.php');\n"
        . "require_once('ini.php');\n"
        . "require_once('lang/" . $userlang . ".php');\n"
        . "?>\n";

    $temp = fopen('cfg.php', 'w');
    if (!fwrite($temp, $config)) {
        echo 'ERROR!! CHMOD current directory to 666';
        fclose($temp);
        exit;
    }
    fclose($temp);

    require_once('cfg.php');
    require_once('db_connect.php');

    $delDB = dbquery('DROP TABLE IF EXISTS team');
    if (!$delDB) {
        global $pdo;
        echo htmlspecialchars($pdo->errorInfo()[2]);
        exit;
    }

    $insDB = dbquery('CREATE TABLE team (
        id        INT(6) UNSIGNED NOT NULL AUTO_INCREMENT,
        name      VARCHAR(255) NOT NULL DEFAULT \'\',
        country   VARCHAR(255) DEFAULT NULL,
        region    VARCHAR(255) DEFAULT NULL,
        city      VARCHAR(255) DEFAULT NULL,
        icon      VARCHAR(255) DEFAULT NULL,
        timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY name_idx (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    if (!$insDB) {
        global $pdo;
        echo htmlspecialchars($pdo->errorInfo()[2]);
        exit;
    }
?>
  <h3><?php echo $lang[$userlang]['SETUP_SUCCESS'] ?></h3>
  <a class="btn btn-primary" href="adminer/index.php"><?php echo $lang[$userlang]['GO_ADMIN'] ?></a>
<?php
}
?>
  <script src="//cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
