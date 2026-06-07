<?php
session_start();

if (empty($_SESSION['ok'])) {
    header('Location: ' . $_SERVER['HTTP_HOST'] . '/index.php');
    exit;
}

if (empty($_SESSION['files'])) {
    unset($_SESSION['files']);
}

$rejected   = [];
$sendfiles  = isset($_POST['file'])    ? $_POST['file'] : [];
$apache2    = isset($_POST['apache2']) ? 1 : 0;

require_once('cfg.php');
require_once('db_connect.php');

if (isset($_POST['send']) && !empty($sendfiles)) {
    require_once('zip.php');

    $zipfile = new zipfile();
    foreach ($sendfiles as $file) {
        $file = (int) $file;
        $query = dbquery('SELECT name, icon FROM team WHERE id = ?', [$file]);
        while ($j = dbarray($query)) {
            $team = $j['name'];
            $icon = $j['icon'] ?? '';
        }
        if (!empty($icon) && GET_TeamIcon($icon) !== '') {
            $iconPath = ICON_DIR . $icon;
            $ext      = '.' . pathinfo($icon, PATHINFO_EXTENSION);
            if ($apache2 === 1) {
                // Vereinsname: nur Buchstaben und Ziffern, alles andere raus
                $zipName = preg_replace('/[^a-zA-Z0-9]/', '', $team) . $ext;
            } else {
                // Vereinsname so wie er in der DB steht + Dateiendung
                $zipName = $team . $ext;
            }
            $zipfile->add_file(file_get_contents($iconPath), $zipName);
        }
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="icons.zip"');
    echo $zipfile->file();
} else {

if (!isset($_SESSION['files'])) {
    $_SESSION['files'] = [];
}

if (!empty($_GET['add'])) {
    $add_files = explode(',', $_GET['add']);
    foreach ($add_files as $add) {
        $add = (int) $add;
        if (count($_SESSION['files']) < MAXIMUM_ICONS_PER_ZIP) {
            $x = array_search($add, $_SESSION['files']);
            if ($x === false) {
                $_SESSION['files'][] = $add;
            } else {
                $rejected[] = $add;
            }
        } else {
            $rejected[] = $add;
        }
    }
}

if (isset($_POST['deleteall'])) {
    unset($_SESSION['files']);
}

if (isset($_GET['del'])) {
    $del = (int) $_GET['del'];
    $x = array_search($del, $_SESSION['files']);
    if ($x !== false) {
        unset($_SESSION['files'][$x]);
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="de-DE">
  <head>
    <title><?php echo RESULT ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <div class="container">
    <h1><?php echo SELECTION; ?></h1>
<?php
$i = 0;
if (isset($_SESSION['files']) && count($_SESSION['files']) > 0) {
    if (count($rejected) > 0) { ?>
      <dl>
        <dt>
          <div class="alert alert-danger" role="alert">
          <?php echo NO_ICON_ZIP; ?><small>(<?php echo NO_ZIP_ARG1; ?> [<?php echo MAXIMUM_ICONS_PER_ZIP; ?>] <?php echo NO_ZIP_ARG2; ?>)</small>
          </div>
        </dt>
        <div class="alert alert-warning" role="alert">
    <?php
        foreach ($rejected as $file) {
            $file  = (int) $file;
            $query = dbquery('SELECT name, icon FROM team WHERE id = ?', [$file]);
            $j     = dbarraynum($query);
            $team  = $j[0]; ?>
          <dd><?php echo htmlspecialchars($team) ?></dd>
        <?php
        } ?>
      </div>
    </dl><?php
    } ?>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
      <div class="container-fluid">
        <div class="row">
          <div class="col-auto"><?php echo SEL_ICONS; ?></div>
        </div>
<?php
    foreach ($_SESSION['files'] as $file) {
        $i++;
        $file  = (int) $file;
        $query = dbquery('SELECT name, icon FROM team WHERE id = ?', [$file]);
        $j     = dbarraynum($query);
        $team  = $j[0]; ?>
        <div class="row">
          <div class="col-1"><?php echo $i ?>.</div>
          <div class="col-1 d-flex align-items-center"><?php echo HTML_TeamIcon($team, '', ''); ?></div>
          <div class="col-5"><?php echo htmlspecialchars($team) ?><input type="hidden" name="file[]" value="<?php echo $file ?>"></div>
          <div class="col-1"><a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?del=<?php echo $file ?>" title="<?php echo DEL_FROM_ZIP; ?>"><i class="bi bi-trash text-danger" style="font-size: 1.3rem"></i></a></div>
        </div>
<?php
    } ?>
        <div class="row">
          <div class="col-auto"><input type="checkbox" name="apache2"></div>
          <div class="col-auto"><acronym title="<?php echo APACHE_HINT; ?>"><?php echo APACHE_COMP; ?></acronym></div>
        </div>
        <div class="row">
          <div class="col-auto"><input class="btn btn-danger btn-sm" type="submit" name="deleteall" value="<?php echo DEL_LIST ?>"> <input class="btn btn-success btn-sm" type="submit" name="send" value="<?php echo SAVE_LIST; ?>"></div>
        </div>
      </div>
    </form>
<?php
}
if ($i === 0) { ?>
      <p><?php echo SEL_TEAM ?></p><?php
} ?>
    </div>
    <script src="//cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html><?php
}
<?php
session_start();

if (empty($_SESSION['ok'])) {
  header("Location: ".$_SERVER['HTTP_HOST']."/index.php");
  exit;
}

if (empty($_SESSION['files'])) unset($_SESSION['files']);

$rejected=array();
$sendfiles=isset($_POST['file'])?$_POST['file']:array();
$apache2=isset($_POST['apache2'])?1:0;

require_once("cfg.php");
require_once("db_connect.php");

if (isset($_POST['send']) && !empty($sendfiles)) {
  require_once('zip.php');

  $zipfile = new zipfile();
  foreach($sendfiles as $file) {
    $query=dbquery("SELECT name FROM team WHERE id='$file'");
    while($j = dbarray($query)) {
     $team=$j['name'];
     $team2=$team;
     if ($apache2==1) $team2=preg_replace("/[^a-zA-Z0-9]/",'',$team);
    }
    if (GET_TeamIcon($team) != '') {
      $zipfile->add_file(implode('',file(substr(GET_TeamIcon($team),1))), substr(GET_TeamIcon($team2),7));
    }
  }
  header("Content-Type: application/zip");
  header("Content-Disposition: attachment; filename=\"icons.zip\"");
  echo $zipfile->file();
} else {

if (!isset($_SESSION['files'])) $_SESSION['files']=array();

if (!empty($_GET['add'])) {

  $add_files=explode(',',$_GET['add']);
  foreach ($add_files as $add) {
    if (count($_SESSION['files'])<MAXIMUM_ICONS_PER_ZIP) {
      $x=array_search($add,$_SESSION['files']);
      if ($x===FALSE) {
        $_SESSION['files'][]=$add;
      } else {
        $rejected[]=$add;
      }
    } else{
      $rejected[]=$add;
    }
  }
}

if(isset($_POST['deleteall'])) { 
   unset($_SESSION['files']);
}

if(isset($_GET['del'])) {
	$del = $_GET['del'];
	$x = array_search($del, $_SESSION['files']);
	if ($x!==FALSE) {
     unset($_SESSION['files'][$x]);
  }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="de-DE">
  <head>
    <title><?php echo RESULT?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <div class="container">
    <h1><?php echo SELECTION;?></h1>
<?php
$i=0;
if (isset($_SESSION['files']) && count($_SESSION['files'])>0) {
  if (count($rejected)>0) {?>
      <dl>
        <dt>
          <div class="alert alert-danger" role="alert">
          <?php echo NO_ICON_ZIP;?><small>(<?php echo NO_ZIP_ARG1;?> [<?php echo MAXIMUM_ICONS_PER_ZIP;?>] <?php echo NO_ZIP_ARG2;?>)</small>
          </div>
        </dt>
        <div class="alert alert-warning" role="alert">
    <?php
    foreach ($rejected as $file) {
      $query=dbquery("SELECT name FROM team WHERE id=$file");
      $j = dbarraynum($query);
      $team=$j[0];?>
        <dd><?php echo $team?></dd>
      <?php
    }?>
      </div>
    </dl><?php
  }?>
    <form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
      <div class="container-fluid">
        <div class="row">
          <div class="col-auto"><?php echo SEL_ICONS;?></div>
        </div>
<?php
    foreach ($_SESSION['files'] as $file) {
      $i++;
      $query=dbquery("SELECT name FROM team WHERE id=$file");
      $j = dbarraynum($query);
      $team=$j[0];?>
        <div class="row">
          <div class="col-1"><?php echo $i?>.</div>
          <div class="col-1 d-flex align-items-center"><?php echo HTML_TeamIcon($team,"","");?></div>
          <div class="col-5"><?php echo $team?><input type="hidden" name="file[]" value="<?php echo $file?>"></div>
          <div class="col-1"><a href="<?php echo $_SERVER['PHP_SELF'];?>?del=<?php echo $file?>" title="<?php echo DEL_FROM_ZIP;?>"><i class="bi bi-trash text-danger" style="font-size: 1.3rem"></i></a></div>
        </div>
<?php
    }?>
        <div class="row">
          <div class="col-auto"><input type="checkbox" name="apache2"></div>
          <div class="col-auto"><acronym title="<?php echo APACHE_HINT;?>"><?php echo APACHE_COMP;?></acronym></div>
        </div>
        <div class="row">
          <div class="col-auto"><input class="btn btn-danger btn-sm" type="submit" name="deleteall" value="<?php echo DEL_LIST?>"> <input class="btn btn-success btn-sm" type="submit" name="send" value="<?php echo SAVE_LIST;?>"></div>
        </div>
      </div>
    </form>
<?php
  }
  if ($i==0) {?>
      <p><?php echo SEL_TEAM?></p><?php
  }
  ?>
    </div>
    <script src="//cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html><?php
}?>
