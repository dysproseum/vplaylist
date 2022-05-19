<html>
<head>
<link rel="stylesheet" href="include/style.css?2">
<script src="include/player.js"></script>
<title>vplaylist</title>
<?php print analytics(); ?>
</head>

<?php if ($vid_player): ?>
<body class="behind-video">
<?php else: ?>
<body>
<?php endif; ?>
<div class="header">
	<h1><a href="index.php">vplaylist</a></h1>

	<form class="search" action="search.php" method="get">
		<input type="text" maxlength="64" name="q" placeholder="search" />
	</form>
</div>

