<html>
<head>
<link rel="stylesheet" href="include/style.css">
<script src="include/player.js"></script>
<title>vplaylist</title>
<?php print analytics(); ?>
</head>

<?php if ($controls == ''): ?>
  <body class="nocontrols">
<?php elseif ($vid_player): ?>
  <body class="behind-video">
<?php else: ?>
  <body>
<?php endif; ?>

  <div class="header">
	<h1><a href="/vplaylist/index.php">vplaylist</a></h1>

	<form class="search" action="search.php" method="get">
		<input type="text" maxlength="64" name="q" placeholder="search" />
	</form>
</div>

