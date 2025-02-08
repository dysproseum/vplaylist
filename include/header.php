<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="include/style.css">
<link rel="stylesheet" href="include/cast.css">
<script src="include/util.js"></script>
<script src="include/player.js"></script>
<script src="include/fullscreen.js"></script>
<script type="text/javascript">
  const start_muted = <?php print $conf['start_muted']; ?>;
  const use_ajax = <?php print $conf['use_ajax']; ?>;
</script>
<script src="include/cast.js" type="module"></script>
<script type="text/javascript" src="//www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
<?php
  $title = "vplaylist";
  if (isset($vid_title) && $vid_title != '') {
    $title = "$vid_title | vplaylist";
  }
?>
<title><?php print $title; ?></title>
<meta property="og:title" content="<?php print $vid_title; ?>" />
<meta property="og:description" content="vplaylist is an HTML5 video player for desktop and mobile for personal use" />
<meta property="og:image" content="/vplaylist/serve.php?collection=<?php print $machine_name; ?>&index=<?php print $index; ?>&file=.jpg" />
</head>

<?php if ($controls == '0'): ?>
  <body class="nocontrols">
<?php elseif ($vid_player): ?>
  <body id="body" class="behind-video">
<?php else: ?>
  <body>
<?php endif; ?>

  <div class="header">
	<h1><a href="/vplaylist/index.php">vplaylist</a></h1>

	<form class="search" action="/vplaylist/search.php" method="get">
		<input type="text" maxlength="64" name="q" placeholder="search" />
	</form>
</div>
