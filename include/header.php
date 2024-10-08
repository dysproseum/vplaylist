<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="include/style.css">
<script src="include/util.js"></script>
<script src="include/player.js"></script>
<script type="text/javascript">
  const start_muted = <?php print $conf['start_muted']; ?>;
  const use_ajax = <?php print $conf['use_ajax']; ?>;
</script>
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
  <body class="behind-video">
<?php else: ?>
  <body>
<?php endif; ?>

  <div class="header">
	<h1><a href="/vplaylist/index.php">vplaylist</a></h1>

	<form class="search" action="/vplaylist/search.php" method="get">
		<input type="text" maxlength="64" name="q" placeholder="search" />
	</form>
</div>

