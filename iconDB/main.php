<?php
session_start();
if (empty($_SESSION['ok'])) {
    header('Location: ' . $_SERVER['HTTP_HOST'] . '/index.php');
    exit;
}
$initial = [];
$teams = [];
$count = 0;
require_once('cfg.php');
require_once('db_connect.php');

$ini  = !empty($_GET['i'])     ? $_GET['i']     : '';
$term = !empty($_POST['term']) ? $_POST['term'] : '';
$term2 = $term;

// Suche nach String mit x Zeichen als Textersetzer für *
$term = str_replace('*', '%', $term);

// Suche nach String mit genau einem Zeichen als Textersetzer für ?
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

if ($term === '') {
    $query = dbquery(
        "SELECT id, name, city, country, region, icon FROM team WHERE city LIKE ? ORDER BY name",
        [$ini . '%']
    );
} else {
    $query = dbquery(
        "SELECT id, name, city, country, region, icon FROM team WHERE name LIKE ? OR city LIKE ? ORDER BY name LIMIT " . (int) MAXIMUM_SEARCH_RESULTS,
        [$term, $term]
    );
}

while ($i = dbarray($query)) {
    $teams[] = $i;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="de-DE">
  <head>
    <title>IconDatabase <?php echo VERSION ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <div class="container-fluid">
    <h1>IconDatabase <?php echo VERSION ?></h1>
    <ul class='pagination'>
    <?php
        foreach ($initial as $i) {
            if ($ini !== $i) {
                echo "      <li class='page-item'><a class='page-link' href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "?i=$i'>$i</a></li>\n";
            } else {
                $current_initial = $i;
                echo "  <li class='page-item active'><a class='page-link'>$i</a></li>\n";
            }
        }
    ?>
    </ul>
    <p><small>[<?php echo "$count " . TEAM_IN_DB ?>]</small></p>
    <hr>
    <div id="search">
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="form-inline" role="form">
        <div class="row g-3">
          <div class="col-auto">
            <h3><?php echo SEARCH ?></h3>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-auto">
            <input type="text" name="term" class="form-control" placeholder="<?php echo TEAM; ?>" />
          </div>
          <div class="col-auto">
            <?php echo WILDCARDS; ?>
          </div>
          <div class="col-auto">
            <input class="btn btn-primary btn-sm" type="submit" value="<?php echo SEARCH; ?>">
          </div>
        </div>
      </form>
      <p><hr></p>
      <h3><?php
$search_results = dbrows($query);
echo $search_results . TEAMS_FOUND . ' "' . htmlspecialchars($term2 . $ini) . '" ' . FOUND;
if ($term !== '' && $search_results >= MAXIMUM_SEARCH_RESULTS) {
    echo ' <small>(' . SEARCH_LIMIT . MAXIMUM_SEARCH_RESULTS . RESULTS . ')';
}
?></h3>
    </div>
    <div class="container-fluid">
      <div class="row">
        <div class="col-1"></div>
        <div class="col-4"><p class="font-weight-bold text-uppercase"><?php echo TEAM; ?> <small>(<?php echo CITY; ?>)</small></p></div>
        <div class="col-3"><p class="font-weight-bold text-uppercase"><?php echo COUNTRY; ?></p></div>
        <div class="col-2"><p class="font-weight-bold text-uppercase"><?php echo ZIP_IT; ?></p></div>
        <div class="col-1"></div>
      </div>
      <?php
if (!empty($teams)) {
    $anz = count($teams);
    $cluster = [];
    $zipcluster = [];
    for ($i = 0; $i < $anz; $i++) {
        $j = $teams[$i];
        $cluster[] = $teams[$i]['id'];
        if (fmod($i + 1, MAXIMUM_ICONS_PER_ZIP) == 0) {
            $zipcluster[] = $cluster;
            $cluster = [];
        }
?>
      <div class="row p-2">
        <div class="col-1"><?php echo HTML_TeamIcon($j['icon'] ?? '', " title='" . htmlspecialchars($j['name']) . "'", " alt='" . htmlspecialchars($j['name']) . "'"); ?></div>
        <div class="col-4"><strong><?php echo htmlspecialchars($j['name']) ?></strong> <small>(<?php echo htmlspecialchars($j['city']) ?>)</small></div>
        <div class="col-3"><?php echo htmlspecialchars($j['country']) ?></div>
        <div class="col-2"><a target="result" href="result.php?add=<?php echo (int) $j['id'] ?>" title="<?php echo SELECT; ?>"><i class="bi bi-download" style="font-size: 1.3rem"></i></a></div>
      </div>
      <?php
    }
    $zipcluster[] = $cluster;
} else {
    ?>
    <div class="container-fluid">
      <div class="col-6"><?php echo NOT_FOUND; ?></div>
    </div><?php
}
?>
    <div class="container-fluid">
      <div class="col-12 text-end"><?php
if (!empty($teams)) {
    if (count($zipcluster) == 1) {
?>
<a target="result" href="result.php?add=<?php echo implode(',', array_map('intval', $zipcluster[0])); ?>" title="<?php echo SELECTALL; ?>"><i class="bi bi-download text-success" style="font-size: 1.3rem"></i></a>
<?php
    } else {
        foreach ($zipcluster as $cluster) {
?>
<a target="result" href="result.php?add=<?php echo implode(',', array_map('intval', $cluster)); ?>"><i class="bi bi-download text-success"  style="font-size: 1.3rem" title="<?php echo SELECTALL; ?>"></i></a>
<?php
        }
    }
}
?></div>
      </div>
    </div>
    <hr>
    <ul class="pagination">
    <?php
        foreach ($initial as $i) {
            if ($ini !== $i) {
                echo "      <li class='page-item'><a class='page-link' href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "?i=$i'>$i</a></li>\n";
            } else {
                $current_initial = $i;
                echo "  <li class='page-item active'><a class='page-link'>$i</a></li>\n";
            }
        }
    ?>
    </ul>
    </div>
    <script src="//cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
