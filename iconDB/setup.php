<?php

session_start();
include('ini.php');

$_SESSION['userlang'] = isset($_GET['userlang'])
    ? $_GET['userlang']
    : (isset($_SESSION['userlang']) ? $_SESSION['userlang'] : 'de');
$userlang = strtolower($_SESSION['userlang']);

$lang = [
    'de' => [
        'SETUP_HEAD'              => 'LMO-IconDataBase',
        'SETUP_SUBTITLE'          => 'Installationsroutine',
        'SETUP_DB_SECTION'        => 'Datenbankverbindung',
        'SETUP_ADMIN_SECTION'     => 'Admin-Zugang',
        'SETUP_DB_HOST'           => 'DB-Host',
        'SETUP_DB_HOST_HINT'      => 'meistens „localhost"',
        'SETUP_DB_USER'           => 'DB-Benutzername',
        'SETUP_DB_PASS'           => 'DB-Passwort',
        'SETUP_DB_NAME'           => 'DB-Name',
        'SETUP_ADMIN_USER'        => 'Admin-Benutzername',
        'SETUP_ADMIN_USER_HINT'   => 'für den Adminbereich',
        'SETUP_ADMIN_PASS'        => 'Admin-Passwort',
        'SETUP_ADMIN_PASS_CONFIRM'=> 'Passwort bestätigen',
        'SETUP_SUCCESS'           => 'Installation erfolgreich abgeschlossen!',
        'SETUP_SUCCESS_INFO'      => 'Die Konfiguration wurde gespeichert. Du kannst jetzt den Adminbereich öffnen.',
        'NEXT'                    => 'Installieren',
        'GO_ADMIN'                => 'Zum Adminbereich',
        'ERROR_PASS_MISMATCH'     => 'Die Passwörter stimmen nicht überein.',
        'ERROR_EMPTY_FIELDS'      => 'Bitte alle Felder ausfüllen.',
        'ERROR_WRITE'             => 'Fehler: Konfigurationsdatei konnte nicht geschrieben werden. Bitte CHMOD des Verzeichnisses prüfen (755).',
        'ERROR_HTACCESS'          => 'Fehler: .htaccess konnte nicht geschrieben werden. Bitte CHMOD des adminer-Verzeichnisses prüfen (755).',
    ],
    'en' => [
        'SETUP_HEAD'              => 'LMO-IconDataBase',
        'SETUP_SUBTITLE'          => 'Installation',
        'SETUP_DB_SECTION'        => 'Database Connection',
        'SETUP_ADMIN_SECTION'     => 'Admin Access',
        'SETUP_DB_HOST'           => 'DB Host',
        'SETUP_DB_HOST_HINT'      => 'usually "localhost"',
        'SETUP_DB_USER'           => 'DB Username',
        'SETUP_DB_PASS'           => 'DB Password',
        'SETUP_DB_NAME'           => 'DB Name',
        'SETUP_ADMIN_USER'        => 'Admin Username',
        'SETUP_ADMIN_USER_HINT'   => 'for the admin area',
        'SETUP_ADMIN_PASS'        => 'Admin Password',
        'SETUP_ADMIN_PASS_CONFIRM'=> 'Confirm Password',
        'SETUP_SUCCESS'           => 'Installation completed successfully!',
        'SETUP_SUCCESS_INFO'      => 'Configuration has been saved. You can now open the admin area.',
        'NEXT'                    => 'Install',
        'GO_ADMIN'                => 'Go to Admin Area',
        'ERROR_PASS_MISMATCH'     => 'Passwords do not match.',
        'ERROR_EMPTY_FIELDS'      => 'Please fill in all fields.',
        'ERROR_WRITE'             => 'Error: Could not write config file. Please check directory permissions (755).',
        'ERROR_HTACCESS'          => 'Error: Could not write .htaccess. Please check adminer directory permissions (755).',
    ],
];

// Abbruch, wenn Konfiguration bereits existiert
if (file_exists('cfg.php')) {
    exit('bereits installiert / already installed');
}

$step   = $_GET['step'] ?? '0';
$errors = [];

// ── Schritt 1: Formular verarbeiten ──────────────────────────────────────────
if ($step === '1') {
    $dbhost            = trim($_POST['dbhost']       ?? 'localhost');
    $dbuser            = trim($_POST['dbuser']       ?? '');
    $dbpass            = $_POST['dbpass']            ?? '';
    $dbname            = trim($_POST['dbname']       ?? '');
    $adminuser         = trim($_POST['adminuser']    ?? '');
    $adminpass         = $_POST['adminpass']         ?? '';
    $adminpass_confirm = $_POST['adminpass_confirm'] ?? '';

    if ($dbhost === '' || $dbuser === '' || $dbname === '' || $adminuser === '' || $adminpass === '') {
        $errors[] = $lang[$userlang]['ERROR_EMPTY_FIELDS'];
    }
    if ($adminpass !== $adminpass_confirm) {
        $errors[] = $lang[$userlang]['ERROR_PASS_MISMATCH'];
    }

    if (empty($errors)) {
        // cfg.php schreiben
        $proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $config = "<?php\n"
            . "// Datenbankverbindung\n"
            . "define('LMOID_DB_HOST', '" . addslashes($dbhost) . "');\n"
            . "define('LMOID_DB_USER', '" . addslashes($dbuser) . "');\n"
            . "define('LMOID_DB_PASS', '" . addslashes($dbpass) . "');\n"
            . "define('LMOID_DB',      '" . addslashes($dbname) . "');\n"
            . "\n"
            . "// Pfade & URLs\n"
            . "define('ICON_PATH', str_replace('\\\\', '/', dirname(__FILE__)) . '/');\n"
            . "define('ICON_DIR',  ICON_PATH . 'icons/');\n"
            . "define('ICON_URL',  '" . $proto . "://" . $_SERVER['HTTP_HOST']
                . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . "/icons/');\n"
            . "define('IMG_TYPES', '.svg,.png');\n"
            . "\n"
            . "// Limits\n"
            . "define('MAX_RESULTS_PER_PAGE',  40);\n"
            . "define('MAXIMUM_ICONS_PER_ZIP', 40);\n"
            . "define('MAXIMUM_SEARCH_RESULTS', 500);\n"
            . "\n"
            . "// Pflichtdateien\n"
            . "require_once('functions/html_output.php');\n"
            . "require_once('ini.php');\n"
            . "require_once('lang/" . $userlang . ".php');\n"
            . "?>\n";

        $fh = fopen('cfg.php', 'w');
        if (!$fh || !fwrite($fh, $config)) {
            $errors[] = $lang[$userlang]['ERROR_WRITE'];
            if ($fh) {
                fclose($fh);
            }
        } else {
            fclose($fh);
        }
    }

    if (empty($errors)) {
        // .htaccess & .htpasswd für adminer schreiben
        $htaccess = "# Icon-DB Admin Area Protection\n"
            . "AuthType Basic\n"
            . "AuthName \"Icon-DB\"\n"
            . "AuthUserFile " . __DIR__ . "/adminer/.htpasswd\n"
            . "require valid-user\n";

        $fh = fopen('adminer/.htaccess', 'w');
        if (!$fh || !fwrite($fh, $htaccess)) {
            $errors[] = $lang[$userlang]['ERROR_HTACCESS'];
            if ($fh) {
                fclose($fh);
            }
        } else {
            fclose($fh);
            chmod('adminer/.htaccess', 0644);
        }
    }

    if (empty($errors)) {
        // Passwort-Hash: Apache-kompatibles bcrypt-Format via apr1/md5
        // Fallback auf {SHA} wenn crypt() kein bcrypt unterstützt
        if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH === 1) {
            $salt = '$2y$10$' . substr(strtr(base64_encode(random_bytes(16)), '+', '.'), 0, 22);
            $hash = crypt($adminpass, $salt);
        } else {
            // Fallback: Apache-kompatibler SHA1
            $hash = '{SHA}' . base64_encode(sha1($adminpass, true));
        }
        $htpasswd = $adminuser . ':' . $hash . "\n";

        $fh = fopen('adminer/.htpasswd', 'w');
        if (!$fh || !fwrite($fh, $htpasswd)) {
            $errors[] = 'Fehler: .htpasswd konnte nicht geschrieben werden.';
            if ($fh) {
                fclose($fh);
            }
        } else {
            fclose($fh);
            chmod('adminer/.htpasswd', 0640);
        }
    }

    if (empty($errors)) {
        // Datenbank anlegen
        require_once('cfg.php');
        require_once('db_connect.php');

        dbquery('DROP TABLE IF EXISTS team');
        dbquery('CREATE TABLE team (
            id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name      VARCHAR(255) NOT NULL DEFAULT \'\',
            country   VARCHAR(255) DEFAULT NULL,
            region    VARCHAR(255) DEFAULT NULL,
            city      VARCHAR(255) DEFAULT NULL,
            icon      VARCHAR(255) DEFAULT NULL,
            timestamp TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY name_idx (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }
}

// ── Hilfsfunktion: Lang-Links ─────────────────────────────────────────────────
function langLinks(array $lang, string $self, string $current): string
{
    $out = '';
    foreach (array_keys($lang) as $code) {
        $active = ($code === $current) ? ' active fw-bold' : '';
        $out   .= '<a href="' . htmlspecialchars($self) . '?userlang=' . $code
               . '" class="lang-link' . $active . '">' . strtoupper($code) . '</a>';
    }
    return $out;
}

$self = htmlspecialchars($_SERVER['PHP_SELF']);
$t    = $lang[$userlang];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($userlang); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?php echo htmlspecialchars($t['SETUP_HEAD']); ?> – <?php echo htmlspecialchars($t['SETUP_SUBTITLE']); ?></title>
  <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    :root {
      --lmo-primary:   #1a56db;
      --lmo-bg:        #f0f4f8;
      --lmo-card-bg:   #ffffff;
      --lmo-border:    #d1dce8;
      --lmo-muted:     #6b7a90;
      --lmo-success:   #0e9f6e;
    }

    body {
      background: var(--lmo-bg);
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 2rem 1rem 3rem;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }

    .setup-wrapper {
      width: 100%;
      max-width: 540px;
    }

    /* ── Header ── */
    .setup-header {
      text-align: center;
      margin-bottom: 1.75rem;
    }
    .setup-header .badge-subtitle {
      display: inline-block;
      font-size: .7rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--lmo-primary);
      background: #dbeafe;
      border-radius: 20px;
      padding: .25rem .85rem;
      margin-bottom: .6rem;
    }
    .setup-header h1 {
      font-size: 1.6rem;
      font-weight: 700;
      color: #1e293b;
      margin: 0;
    }

    /* ── Card ── */
    .setup-card {
      background: var(--lmo-card-bg);
      border: 1px solid var(--lmo-border);
      border-radius: 14px;
      padding: 1.75rem 1.5rem;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
    }

    /* ── Section title ── */
    .section-title {
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--lmo-muted);
      margin-bottom: .85rem;
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .section-title::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--lmo-border);
    }

    /* ── Form controls ── */
    .form-label {
      font-size: .85rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: .3rem;
    }
    .form-text {
      font-size: .75rem;
      color: var(--lmo-muted);
    }
    .form-control {
      border-radius: 8px;
      border-color: var(--lmo-border);
      font-size: .92rem;
      padding: .55rem .8rem;
      transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus {
      border-color: var(--lmo-primary);
      box-shadow: 0 0 0 3px rgba(26,86,219,.12);
    }

    /* ── Submit button ── */
    .btn-install {
      background: var(--lmo-primary);
      border: none;
      border-radius: 9px;
      color: #fff;
      font-size: .95rem;
      font-weight: 600;
      padding: .65rem 1.5rem;
      width: 100%;
      margin-top: .5rem;
      letter-spacing: .02em;
      transition: background .15s, transform .1s;
    }
    .btn-install:hover  { background: #1648c0; }
    .btn-install:active { transform: scale(.98); }

    /* ── Language switcher ── */
    .lang-switcher {
      text-align: center;
      margin-top: 1.1rem;
      font-size: .8rem;
      color: var(--lmo-muted);
    }
    .lang-link {
      color: var(--lmo-muted);
      text-decoration: none;
      margin: 0 .35rem;
      padding: .15rem .4rem;
      border-radius: 4px;
      transition: background .12s, color .12s;
    }
    .lang-link:hover,
    .lang-link.active {
      background: #dbeafe;
      color: var(--lmo-primary);
    }

    /* ── Success state ── */
    .success-icon {
      width: 60px; height: 60px;
      background: #d1fae5;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.8rem;
      color: var(--lmo-success);
    }
    .btn-admin {
      background: var(--lmo-success);
      border: none;
      border-radius: 9px;
      color: #fff;
      font-size: .95rem;
      font-weight: 600;
      padding: .65rem 1.5rem;
      width: 100%;
      text-decoration: none;
      display: block;
      text-align: center;
      transition: background .15s;
    }
    .btn-admin:hover { background: #057a55; color: #fff; }

    /* ── Divider ── */
    .my-section { margin: 1.4rem 0 1rem; }
  </style>
</head>
<body>
<div class="setup-wrapper">

  <div class="setup-header">
    <div class="badge-subtitle"><?php echo htmlspecialchars($t['SETUP_SUBTITLE']); ?></div>
    <h1><?php echo htmlspecialchars($t['SETUP_HEAD']); ?></h1>
  </div>

  <div class="setup-card">

<?php
if ($step === '1' && empty($errors)) { ?>

    <!-- ── Erfolg ─────────────────────────────────────────────── -->
    <div class="text-center py-2">
      <div class="success-icon"><i class="bi bi-check-lg"></i></div>
      <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($t['SETUP_SUCCESS']); ?></h5>
      <p class="text-muted mb-4" style="font-size:.88rem">
        <?php echo htmlspecialchars($t['SETUP_SUCCESS_INFO']); ?>
      </p>
      <a class="btn-admin" href="adminer/index.php">
        <i class="bi bi-shield-lock me-2"></i><?php echo htmlspecialchars($t['GO_ADMIN']); ?>
      </a>
    </div>

<?php
} else { ?>

    <!-- ── Fehlermeldungen ────────────────────────────────────── -->
<?php
    if (!empty($errors)) { ?>
      <div class="alert alert-danger py-2 mb-3" style="font-size:.875rem; border-radius:9px">
        <?php foreach ($errors as $err): ?>
          <div><i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($err); ?></div>
        <?php endforeach; ?>
      </div>
<?php
    } ?>

    <!-- ── Formular ───────────────────────────────────────────── -->
    <form method="post" action="<?php echo $self; ?>?step=1" novalidate>

      <!-- Datenbankverbindung -->
      <div class="section-title">
        <i class="bi bi-database"></i>
        <?php echo htmlspecialchars($t['SETUP_DB_SECTION']); ?>
      </div>

      <div class="mb-3">
        <label class="form-label" for="dbhost"><?php echo htmlspecialchars($t['SETUP_DB_HOST']); ?></label>
        <input class="form-control" type="text" id="dbhost" name="dbhost"
               value="<?php echo htmlspecialchars($_POST['dbhost'] ?? 'localhost'); ?>"
               placeholder="localhost" autocomplete="off">
        <div class="form-text"><?php echo htmlspecialchars($t['SETUP_DB_HOST_HINT']); ?></div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="dbuser"><?php echo htmlspecialchars($t['SETUP_DB_USER']); ?></label>
        <input class="form-control" type="text" id="dbuser" name="dbuser"
               value="<?php echo htmlspecialchars($_POST['dbuser'] ?? ''); ?>"
               autocomplete="off">
      </div>

      <div class="mb-3">
        <label class="form-label" for="dbpass"><?php echo htmlspecialchars($t['SETUP_DB_PASS']); ?></label>
        <input class="form-control" type="password" id="dbpass" name="dbpass" autocomplete="new-password">
      </div>

      <div class="mb-3">
        <label class="form-label" for="dbname"><?php echo htmlspecialchars($t['SETUP_DB_NAME']); ?></label>
        <input class="form-control" type="text" id="dbname" name="dbname"
               value="<?php echo htmlspecialchars($_POST['dbname'] ?? ''); ?>"
               autocomplete="off">
      </div>

      <!-- Admin-Zugang -->
      <div class="section-title my-section">
        <i class="bi bi-person-lock"></i>
        <?php echo htmlspecialchars($t['SETUP_ADMIN_SECTION']); ?>
      </div>

      <div class="mb-3">
        <label class="form-label" for="adminuser"><?php echo htmlspecialchars($t['SETUP_ADMIN_USER']); ?></label>
        <input class="form-control" type="text" id="adminuser" name="adminuser"
               value="<?php echo htmlspecialchars($_POST['adminuser'] ?? ''); ?>"
               required autocomplete="off">
        <div class="form-text"><?php echo htmlspecialchars($t['SETUP_ADMIN_USER_HINT']); ?></div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="adminpass"><?php echo htmlspecialchars($t['SETUP_ADMIN_PASS']); ?></label>
        <input class="form-control" type="password" id="adminpass" name="adminpass"
               required autocomplete="new-password">
      </div>

      <div class="mb-4">
        <label class="form-label" for="adminpass_confirm"><?php echo htmlspecialchars($t['SETUP_ADMIN_PASS_CONFIRM']); ?></label>
        <input class="form-control" type="password" id="adminpass_confirm" name="adminpass_confirm"
               required autocomplete="new-password">
      </div>

      <button type="submit" class="btn-install">
        <i class="bi bi-rocket-takeoff me-2"></i><?php echo htmlspecialchars($t['NEXT']); ?>
      </button>

    </form>

<?php
} ?>

  </div><!-- .setup-card -->

  <div class="lang-switcher">
    <?php echo langLinks($lang, $_SERVER['PHP_SELF'], $userlang); ?>
  </div>

</div><!-- .setup-wrapper -->
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
