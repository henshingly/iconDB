<?php
session_start();
$_SESSION['ok']=true;

$initial = array();
$teams   = array();
$count   = 0;
$step    = 1;
$error   = '';

require_once("../cfg.php");
require_once("../db_connect.php");
require_once("dUnzip2.inc.php");

$ini      =!empty($_GET['i'])?$_GET['i']:'';
$edit     =!empty($_GET['edit'])?$_GET['edit']:'';
$del      =!empty($_GET['del'])?$_GET['del']:'';
$updteam  =!empty($_GET['updteam'])?$_GET['updteam']:'';
$save     =!empty($_GET['save'])?$_GET['save']:'';
$step     =!empty($_GET['step'])?$_GET['step']:'';

$term = !empty($_POST['term'])?$_POST['term']:'';
$term2 = $term;

// Suche nach String mit x Zeichen als Textersetzer für *
$term = str_replace('*','%',$term);
// Suche nach String mit genau einem Zeichen als Textersetzer für ?
$term = str_replace('?','_',$term);

$query=dbquery("SELECT id FROM team");
$count=dbrows($query);
$query=dbquery("SELECT DISTINCT LEFT (city, 1) AS anfangsbuchstabe FROM team ORDER BY anfangsbuchstabe");
while($i = dbarraynum($query)) {
  if ($ini=='' && $term=='') {
    $ini=$i[0];
  }
  $initial[]=$i[0];
}

if($updteam != '') {
  $xid = $_POST['id'];
  $xname = $_POST['xname'];
  $xcity = $_POST['xcity'];
  $xcountry = $_POST['xcountry'];
  $xregion = $_POST['xregion'];
  $result=dbquery("UPDATE team SET name='$xname', city='$xcity', country='$xcountry', region='$xregion' WHERE id=$xid");
  header("Location: ".$_SERVER['PHP_SELF']."?i=".$_POST['i']);
}

if ($edit != '') {
  $query=dbquery("SELECT id,name,city,country,region FROM team WHERE id='$edit'");
}elseif ($del != '') {
  $query=dbquery("SELECT name FROM team WHERE id=$del");
  $name=dbresult($query,0);
  if ($name != '') {
  	unlink("../".GET_TeamIcon($name));
  }
  $result=dbquery("DELETE FROM team WHERE id=$del");
  header("Location: ".$_SERVER['PHP_SELF']."?i=".$_GET['i']);
}elseif ($term=="") {
  $query=dbquery("SELECT id,name,city,country,region FROM team WHERE city LIKE '$ini%' ORDER BY name");
} else {
  $query=dbquery("SELECT id,name,city,country,region FROM team WHERE name LIKE '%$term%' OR city LIKE '%$term%' ORDER BY name LIMIT ".MAXIMUM_SEARCH_RESULTS);
}
while($i = dbarray($query)) {
  $teams[]=$i;
}

if ($step==2) {
  if($_FILES['teamicon']['error'] == 0) {
    $userfile =      !empty($_FILES['teamicon']['tmp_name']) ? $_FILES['teamicon']['tmp_name'] :'';
    $userfile_name = !empty($_FILES['teamicon']['name'])     ? $_FILES['teamicon']['name']     :'';
    $userfile_type = !empty($_FILES['teamicon']['type'])     ? $_FILES['teamicon']['type']     :'';
  }
  $parts = pathinfo($userfile_name);
  $ext = $parts['extension'];
  $iconfile = ICON_PATH.'/icons/'.$userfile_name;
  // ZIP-Upload?
  if($ext == 'zip') {
    $zip = new dUnzip2($userfile);
    $zip->unzipAll(ICON_PATH.'/icons/');
    echo "Unzip successful!";
    $step == 1;
  }
  // Image-Upload?
  elseif ($userfile!='' && $userfile_name!='' && substr($userfile_type,0,5) == 'image' && move_uploaded_file($userfile, $iconfile)) {
    @chmod($iconfile, 0644);
    $error="Upload Success!";

    $name=basename($userfile_name, ".".$ext);
    $city=$_POST['city'];
    $country=$_POST['country'];
    $region=$_POST['region'];

    $parts=explode(' ',$name);
    $anz=count($parts);
    switch ($anz) {
      case 1:
        $city=$parts[0];
        $country=$parts[0];
        break;
      default:
        if (strlen($parts[$anz-1])<5 && strlen($parts[$anz-2])>=4) {
          $city=$parts[$anz-2];
        } else {
          $city=$parts[$anz-1];
        }
        break;
    }
  } else {
    $error = "Upload-Error!";
    $step  = 1;
  }
}

if(isset($_POST['insertteam'])) {
    $name = $_POST['name'];
    $city = $_POST['city'];
    $country = $_POST['country'];
    $region = $_POST['region'];
    dbquery("INSERT INTO team (id, name, city, country, region) VALUES (NULL, '$name', '$city', '$country', '$region')");
    header("Location: ".$_SERVER['PHP_SELF']);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="de">
  <head>
    <title>IconDatabase <?php echo VERSION;?> &mdash; <?php echo ADMIN;?></title>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../style.css" />
  </head>
  <body>
    <div class="container-fluid">
    <h1>IconDatabase <?php echo VERSION;?> &mdash; <?php echo ADMIN;?></h1>
    <ul class='pagination'>
    <?php
foreach($initial as $i) {
  if ($ini!=$i) {
    echo "<li class='page-item'><a class='page-link' href='".$_SERVER['PHP_SELF']."?i=$i'>$i</a></li>";
  } else {
    $current_initial=$i;
    echo "<li class='page-item active'><a class='page-link'>$i</a></li>";
  }
}
echo "</ul>";
echo "<br /><small> [$count ".TEAM_IN_DB."]</small>";
echo "<hr>";?>
    <p><?php echo $error?></p>
    <div id="add">
      <h2><?php echo NEW_TEAM;?></h2>
      <form action="<?php echo $_SERVER['PHP_SELF']?>?step=2" method="post" enctype="multipart/form-data" role="form"><?php
      if ($step == 2) {?>
        <h3><?php echo STEP2;?></h3>
        <p>
          <img style="border:0.5em solid #fff;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
          <img style="border:0.5em solid #eee;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
          <img style="border:0.5em solid #ddd;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
          <img style="border:0.5em solid #ccc;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
          <img style="border:0.5em solid #aaa;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
          <img style="border:0.5em solid #888;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
          <img style="border:0.5em solid #666;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
          <img style="border:0.5em solid #444;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
          <img style="border:0.5em solid #000;" src="<?php echo ICON_URL.rawurlencode(utf8_encode($userfile_name))?>" width="36" border="0" alt="teamicon">
        </p>
        <p>
          <label><?php echo TEAM;?><input type="text" name="name" value="<?php echo $name;?>"></label>
          <label><?php echo CITY;?><input type="text" name="city" value="<?php echo $city?>"></label>
          <label><?php echo COUNTRY;?><input type="text" name="country" value="<?php echo $country?>"></label>
        <input class="btn btn-primary btn-xs" type="submit" name="insertteam" value="<?php echo NEXT;?>"></p><?php
      } else {?>
        <h3><?php echo STEP1?></h3>
        <p><label><?php echo TEAMICON;?>&nbsp;<input type="file" name="teamicon"></label> <input class="btn btn-primary btn-sm" type="submit" value="<?php echo NEXT;?>"></p><?php
      }?>
      </form>
    </div>
    <hr>
    <div id="search">
      <form action="<?php echo $_SERVER['PHP_SELF']?>" method="post" role="form" class="form-inline">
        <div class="row g-3">
          <div class="col-auto">
            <h3><?php echo SEARCH?></h3>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-auto">
            <input type="text" name="term" class="form-control" placeholder="<?php echo TEAM;?>" />
          </div>
          <div class="col-auto">
            <?php echo WILDCARDS; ?>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-auto">
            <input class="btn btn-primary btn-sm" type="submit" value="<?php echo SEARCH;?>">
          </div>
        </div>

      </form>
      <h3><?php
       $search_results=dbrows($query);
       echo $search_results.TEAMS_FOUND." \"".$term2.$ini."\" ".FOUND;
       if ($term!="" && $search_results>=MAXIMUM_SEARCH_RESULTS) {
         echo " (".SEARCH_LIMIT.MAXIMUM_SEARCH_RESULTS.RESULTS.")";
       }
      ?></h3>
    </div><hr><?php
    if ($edit != '') {?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?updteam=<?php echo $edit;?>" role="form"><?php
    }?>
    <div class="container-fluid">
      <div class="row">
        <div class="col-1"></div>
        <div class="col-5"><p class="font-weight-bold text-uppercase"><?php echo TEAM;?> <small>(<?php echo CITY;?>)</small></p></div>
        <div class="col-3"><p class="font-weight-bold text-uppercase"><?php echo COUNTRY;?></p></div>
        <div class="col-3"></div>
      </div>
<?php
  if (!empty($teams)) {
    $anz=count($teams);
    for($i=0;$i<$anz;$i++) {
      $j=$teams[$i];?>
    <div class="row p-2">
      <div class="col-1"><?php echo HTML_TeamIcon($j['name'],'title="' . $j['name'] . '"', ' alt="' . $j['name'] . '"');?></div><?php
      if ($edit == '') {?>
      <div class="col-5"><strong><?php echo $j['name']?></strong> <small>(<?php echo $j['city'];?>)</small></div>
      <div class="col-3"><?php echo $j['country'];?></div>
      <div class="col-auto"><a href="<?php echo $_SERVER['PHP_SELF'];?>?edit=<?php echo $j['id']?>&i=<?php echo $ini;?>"><i class="bi bi-info-circle-fill" style="font-size: 1.3rem;" alt="<?php echo EDIT;?>" title="<?php echo EDIT;?>"></i></a></div><?php
      } else {?>
      <div class="col-5"><strong><input type="text" name="xname" value="<?php echo $j['name'];?>"></strong> (<input type="text" name="xcity" value="<?php echo $j['city'];?>">)</div>
      <div class="col-3"><input type="text" name="xcountry" value="<?php echo $j['country'];?>"></div>
      <div class="col-auto"><input class="btn btn-sm btn-success" type="submit" name="updateteam" value="<?php echo SAVE;?>"><input type="hidden" name="id" value="<?php echo $j['id'];?>"><input type="hidden" name="i" value="<?php echo $_GET['i'];?>"></div><?php
      }

      if (GET_TeamIcon($j['name'])) {?>
      <div class="col-auto"><a href="<?php echo $_SERVER['PHP_SELF'];?>?del=<?php echo $j['id']?>&i=<?php echo $ini;?>" onclick="return confirm('<?php echo DELETE_CONFIRM;?>');"><i class="bi bi-trash text-danger" style="font-size: 1.3rem;"></i></a></div><?php
      }?>
    </div><?php
    }
  } else {?>
      <div class="row">
        <div class="col-10"><?php echo NOT_FOUND;?></div>
      </div><?php
}?>
    </div><?php
    if ($edit != '') {?>
    </form><?php
    }?>
    <hr>
    <ul class="pagination">
    <?php
foreach($initial as $i) {
  if ($ini!=$i) {
    echo "<li class='page-item'><a class='page-link' href='".$_SERVER['PHP_SELF']."?i=$i'>$i</a></li>";
  } else {
    $current_initial=$i;
    echo "<li class='page-item active'><a class='page-link'>$i</a></li>";
  }
}
?>
  </ul>
  <?php
  echo "<small> [$count ".TEAM_IN_DB."]</small>";
  ?>
  <address>&copy; Ren&eacute; Marth</address>
  </div>
  <script src="//cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
