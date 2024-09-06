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
<title>vplaylist</title>
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

