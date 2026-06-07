<?php
session_start();
$_SESSION['ok'] = true;

$initial  = [];
$teams    = [];
$count    = 0;
$step     = '';
$error    = '';

require_once('../cfg.php');
require_once('../db_connect.php');
require_once('dUnzip2.inc.php');

$ini      = !empty($_GET['i'])       ? $_GET['i']              : '';
$edit     = !empty($_GET['edit'])    ? (int) $_GET['edit']     : 0;
$del      = !empty($_GET['del'])     ? (int) $_GET['del']      : 0;
$updteam  = !empty($_GET['updteam']) ? (int) $_GET['updteam']  : 0;
$step     = !empty($_GET['step'])    ? $_GET['step']           : '';

$term  = !empty($_POST['term']) ? $_POST['term'] : '';
$term2 = $term;

$term = str_replace('*', '%', $term);
$term = str_replace('?', '_', $term);

$query = dbquery('SELECT id FROM team');
$count = dbrows($query);
$query = dbquery('SELECT DISTINCT LEFT(city, 1) AS anfangsbuchstabe FROM team ORDER BY anfangsbuchstabe');
while ($i = dbarraynum($query)) {
    if ($ini === '' && $term === '') {
        $ini = $i[0];
    }
    $initial[] = $i[0];
}

// --- Team updaten ---
if ($updteam !== 0) {
    $xid      = (int) ($_POST['id']      ?? 0);
    $xname    = $_POST['xname']    ?? '';
    $xcity    = $_POST['xcity']    ?? '';
    $xcountry = $_POST['xcountry'] ?? '';
    $xregion  = $_POST['xregion']  ?? '';
    dbquery(
        'UPDATE team SET name = ?, city = ?, country = ?, region = ? WHERE id = ?',
        [$xname, $xcity, $xcountry, $xregion, $xid]
    );
    header('Location: ' . $_SERVER['PHP_SELF'] . '?i=' . urlencode($_POST['i'] ?? $ini));
    exit;
}

// --- Team löschen ---
if ($del !== 0) {
    $query = dbquery('SELECT icon FROM team WHERE id = ?', [$del]);
    $row   = dbarraynum($query);
    $icon  = $row[0] ?? '';
    if ($icon !== '' && file_exists(ICON_DIR . $icon)) {
        unlink(ICON_DIR . $icon);
    }
    dbquery('DELETE FROM team WHERE id = ?', [$del]);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?i=' . urlencode($_GET['i'] ?? $ini));
    exit;
}

// --- Upload (Schritt 2) ---
$userfile_name = '';
$name = $city = $country = $region = '';

if ($step === '2') {
    if (isset($_FILES['teamicon']) && $_FILES['teamicon']['error'] === 0) {
        $userfile      = $_FILES['teamicon']['tmp_name'];
        $userfile_name = $_FILES['teamicon']['name'];
        $userfile_type = $_FILES['teamicon']['type'];
        $parts_info    = pathinfo($userfile_name);
        $ext           = strtolower($parts_info['extension'] ?? '');
        $iconfile      = ICON_DIR . $userfile_name;

        if ($ext === 'zip') {
            $zip = new dUnzip2($userfile);
            $zip->unzipAll(ICON_DIR);
            $error = 'Unzip erfolgreich!';
            $step  = '1';
        } elseif ($userfile_name !== '' && str_starts_with($userfile_type, 'image') && move_uploaded_file($userfile, $iconfile)) {
            @chmod($iconfile, 0644);
            $error = 'Upload erfolgreich!';
            $name  = basename($userfile_name, '.' . $ext);

            $nameParts = explode(' ', $name);
            $anz       = count($nameParts);
            if ($anz === 1) {
                $city    = $nameParts[0];
                $country = $nameParts[0];
            } else {
                $city = (strlen($nameParts[$anz - 1]) < 5 && strlen($nameParts[$anz - 2]) >= 4)
                    ? $nameParts[$anz - 2]
                    : $nameParts[$anz - 1];
            }
        } else {
            $error = 'Upload-Fehler!';
            $step  = '1';
        }
    } else {
        $error = 'Upload-Fehler (kein File oder Fehler beim Transfer)!';
        $step  = '1';
    }
}

// --- Team einfügen ---
if (isset($_POST['insertteam'])) {
    $name    = $_POST['name']    ?? '';
    $city    = $_POST['city']    ?? '';
    $country = $_POST['country'] ?? '';
    $region  = $_POST['region']  ?? '';
    $icon    = $_POST['icon']    ?? '';
    dbquery(
        'INSERT INTO team (name, city, country, region, icon) VALUES (?, ?, ?, ?, ?)',
        [$name, $city, $country, $region, $icon]
    );
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// --- Teamliste laden ---
if ($edit !== 0) {
    $query = dbquery('SELECT id, name, city, country, region, icon FROM team WHERE id = ?', [$edit]);
} elseif ($term === '') {
    $query = dbquery(
        'SELECT id, name, city, country, region, icon FROM team WHERE city LIKE ? ORDER BY name',
        [$ini . '%']
    );
} else {
    $query = dbquery(
        'SELECT id, name, city, country, region, icon FROM team WHERE name LIKE ? OR city LIKE ? ORDER BY name LIMIT ' . (int) MAXIMUM_SEARCH_RESULTS,
        ['%' . $term . '%', '%' . $term . '%']
    );
}
while ($i = dbarray($query)) {
    $teams[] = $i;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="de">
  <head>
    <title>IconDatabase <?php echo VERSION; ?> &mdash; <?php echo ADMIN; ?></title>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../style.css" />
  </head>
  <body>
    <div class="container-fluid">
    <h1>IconDatabase <?php echo VERSION; ?> &mdash; <?php echo ADMIN; ?></h1>
    <ul class='pagination'>
    <?php
    foreach ($initial as $i) {
        if ($ini !== $i) {
            echo "<li class='page-item'><a class='page-link' href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "?i=$i'>$i</a></li>";
        } else {
            echo "<li class='page-item active'><a class='page-link'>$i</a></li>";
        }
    }
    echo '</ul>';
    echo "<br /><small> [$count " . TEAM_IN_DB . ']</small>';
    echo '<hr>';
    ?>
    <p><?php echo htmlspecialchars($error) ?></p>
    <div id="add">
      <h2><?php echo NEW_TEAM; ?></h2>
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>?step=2" method="post" enctype="multipart/form-data" role="form">
      <?php if ($step === '2') { ?>
        <h3><?php echo STEP2; ?></h3>
        <p>
          <?php foreach (['#fff','#eee','#ddd','#ccc','#aaa','#888','#666','#444','#000'] as $bg) {
              echo "<img style='border:0.5em solid $bg;' src='" . ICON_URL . rawurlencode($userfile_name) . "' width='36' border='0' alt='teamicon'> ";
          } ?>
        </p>
        <p>
          <label><?php echo TEAM; ?> <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>"></label>
          <label><?php echo CITY; ?> <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>"></label>
          <label><?php echo COUNTRY; ?> <input type="text" name="country" value="<?php echo htmlspecialchars($country); ?>"></label>
          <input type="hidden" name="icon" value="<?php echo htmlspecialchars($userfile_name); ?>">
          <input class="btn btn-primary btn-xs" type="submit" name="insertteam" value="<?php echo NEXT; ?>">
        </p>
      <?php } else { ?>
        <h3><?php echo STEP1 ?></h3>
        <p><label><?php echo TEAMICON; ?>&nbsp;<input type="file" name="teamicon"></label> <input class="btn btn-primary btn-sm" type="submit" value="<?php echo NEXT; ?>"></p>
      <?php } ?>
      </form>
    </div>
    <hr>
    <div id="search">
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" role="form" class="form-inline">
        <div class="row g-3">
          <div class="col-auto"><h3><?php echo SEARCH ?></h3></div>
        </div>
        <div class="row g-3">
          <div class="col-auto">
            <input type="text" name="term" class="form-control" placeholder="<?php echo TEAM; ?>" />
          </div>
          <div class="col-auto"><?php echo WILDCARDS; ?></div>
        </div>
        <div class="row g-3">
          <div class="col-auto">
            <input class="btn btn-primary btn-sm" type="submit" value="<?php echo SEARCH; ?>">
          </div>
        </div>
      </form>
      <h3><?php
        $search_results = dbrows($query);
        echo $search_results . TEAMS_FOUND . ' "' . htmlspecialchars($term2 . $ini) . '" ' . FOUND;
        if ($term !== '' && $search_results >= MAXIMUM_SEARCH_RESULTS) {
            echo ' (' . SEARCH_LIMIT . MAXIMUM_SEARCH_RESULTS . RESULTS . ')';
        }
      ?></h3>
    </div>
    <hr>
    <?php if ($edit !== 0) { ?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?updteam=<?php echo $edit; ?>" role="form">
    <?php } ?>
    <div class="container-fluid">
      <div class="row">
        <div class="col-1"></div>
        <div class="col-5"><p class="font-weight-bold text-uppercase"><?php echo TEAM; ?> <small>(<?php echo CITY; ?>)</small></p></div>
        <div class="col-3"><p class="font-weight-bold text-uppercase"><?php echo COUNTRY; ?></p></div>
        <div class="col-3"></div>
      </div>
    <?php if (!empty($teams)) {
        foreach ($teams as $j) { ?>
    <div class="row p-2">
      <div class="col-1"><?php echo HTML_TeamIcon($j['icon'] ?? '', 'title="' . htmlspecialchars($j['name']) . '"', ' alt="' . htmlspecialchars($j['name']) . '"'); ?></div>
      <?php if ($edit === 0) { ?>
      <div class="col-5"><strong><?php echo htmlspecialchars($j['name']) ?></strong> <small>(<?php echo htmlspecialchars($j['city']); ?>)</small></div>
      <div class="col-3"><?php echo htmlspecialchars($j['country']); ?></div>
      <div class="col-auto">
        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?edit=<?php echo $j['id'] ?>&i=<?php echo urlencode($ini); ?>">
          <i class="bi bi-info-circle-fill" style="font-size: 1.3rem;" title="<?php echo EDIT; ?>"></i>
        </a>
      </div>
      <?php } else { ?>
      <div class="col-5">
        <strong><input type="text" name="xname" value="<?php echo htmlspecialchars($j['name']); ?>"></strong>
        (<input type="text" name="xcity" value="<?php echo htmlspecialchars($j['city']); ?>">)
      </div>
      <div class="col-3"><input type="text" name="xcountry" value="<?php echo htmlspecialchars($j['country']); ?>"></div>
      <div class="col-auto">
        <input class="btn btn-sm btn-success" type="submit" name="updateteam" value="<?php echo SAVE; ?>">
        <input type="hidden" name="id" value="<?php echo $j['id']; ?>">
        <input type="hidden" name="i" value="<?php echo htmlspecialchars($_GET['i'] ?? $ini); ?>">
      </div>
      <?php } ?>
      <?php if (!empty($j['icon'])) { ?>
      <div class="col-auto">
        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?del=<?php echo $j['id'] ?>&i=<?php echo urlencode($ini); ?>"
           onclick="return confirm('<?php echo DELETE_CONFIRM; ?>');">
          <i class="bi bi-trash text-danger" style="font-size: 1.3rem;"></i>
        </a>
      </div>
      <?php } ?>
    </div>
    <?php }
    } else { ?>
      <div class="row"><div class="col-10"><?php echo NOT_FOUND; ?></div></div>
    <?php } ?>
    </div>
    <?php if ($edit !== 0) { ?>
    </form>
    <?php } ?>
    <hr>
    <ul class="pagination">
    <?php
    foreach ($initial as $i) {
        if ($ini !== $i) {
            echo "<li class='page-item'><a class='page-link' href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "?i=$i'>$i</a></li>";
        } else {
            echo "<li class='page-item active'><a class='page-link'>$i</a></li>";
        }
    }
    ?>
    </ul>
    <small>[<?php echo $count . ' ' . TEAM_IN_DB; ?>]</small>
    <address>&copy; Ren&eacute; Marth</address>
    </div>
    <script src="//cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
