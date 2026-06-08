<?php
session_start();

if (!file_exists('cfg.php')) {
    header('Location: setup.php');
    exit;
}

$_SESSION['ok'] = true;
?>
<!DOCTYPE html>
<html lang="de-DE">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>IconDatabase</title>
  <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    html, body {
      height: 100%;
      margin: 0;
      overflow: hidden;          /* kein Scroll auf der Hülle */
      background: #f0f4f8;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }

    /* ── Desktop: zwei Spalten nebeneinander ── */
    #layout {
      display: flex;
      height: 100vh;
      gap: 0;
    }

    #frame-main {
      flex: 1 1 auto;
      border: none;
      height: 100%;
    }

    #frame-result {
      flex: 0 0 320px;
      border: none;
      border-left: 1px solid #d1dce8;
      height: 100%;
      background: #fff;
    }

    /* ── Mobile: Tab-Umschalter ── */
    #tab-bar {
      display: none;
      position: fixed;
      bottom: 0; left: 0; right: 0;
      height: 52px;
      background: #1a56db;
      z-index: 100;
    }
    #tab-bar button {
      flex: 1;
      border: none;
      background: transparent;
      color: rgba(255,255,255,.7);
      font-size: .85rem;
      font-weight: 600;
      letter-spacing: .04em;
      padding: 0;
      cursor: pointer;
      transition: color .15s;
    }
    #tab-bar button.active {
      color: #fff;
      border-bottom: 3px solid #fff;
    }
    #tab-bar .tab-badge {
      display: inline-block;
      background: #ef4444;
      color: #fff;
      font-size: .65rem;
      border-radius: 99px;
      padding: .05rem .38rem;
      margin-left: .3rem;
      vertical-align: middle;
      line-height: 1.4;
    }

    @media (max-width: 767px) {
      #tab-bar           { display: flex; }

      #layout {
        flex-direction: column;
        padding-bottom: 52px;    /* Platz für Tab-Bar */
      }
      #frame-main,
      #frame-result {
        flex: 1 1 100%;
        width: 100%;
        border-left: none;
        border-top: 1px solid #d1dce8;
      }
      #frame-result { display: none; }  /* initial versteckt */
    }
  </style>
</head>
<body>

<div id="layout">
  <iframe id="frame-main"   name="main"   src="main.php"   scrolling="auto"></iframe>
  <iframe id="frame-result" name="result" src="result.php" scrolling="auto"></iframe>
</div>

<!-- Mobile Tab-Bar -->
<div id="tab-bar">
  <button id="tab-search" class="active" onclick="showTab('main')">
    🔍 Suche
  </button>
  <button id="tab-basket" onclick="showTab('result')">
    📦 Auswahl
    <span class="tab-badge" id="basket-count" style="display:none">0</span>
  </button>
</div>

<script>
function showTab(which) {
    const main   = document.getElementById('frame-main');
    const result = document.getElementById('frame-result');
    const tS     = document.getElementById('tab-search');
    const tB     = document.getElementById('tab-basket');

    if (which === 'main') {
        main.style.display   = '';
        result.style.display = 'none';
        tS.classList.add('active');
        tB.classList.remove('active');
    } else {
        main.style.display   = 'none';
        result.style.display = '';
        tS.classList.remove('active');
        tB.classList.add('active');
        // iframe neu laden, damit Änderungen sichtbar sind
        result.src = result.src;
    }
}

// Badge-Aktualisierung: result.php setzt window.parent.setBasketCount(n)
function setBasketCount(n) {
    const badge = document.getElementById('basket-count');
    if (n > 0) {
        badge.textContent  = n;
        badge.style.display = '';
    } else {
        badge.style.display = 'none';
    }
}
</script>

</body>
</html>
