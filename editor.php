<?php

require_once 'include/bootstrap.php';

if (!isset($collections)) {
	header('Location: install.php');
	exit;
}

if (isset($_REQUEST['collection'])) {
	$machine_name = $_REQUEST['collection'];
}
else {
	$machine_name = '';
}

$indexes = ['source', 'target'];
$items = [];
foreach ($indexes as $index) {
  if (isset($_REQUEST[$index]) && $machine_name != '') {
	$id = $_REQUEST[$index];
	if (!isset($collections[$machine_name]['items'][$id])) {
		$items[$id] = false;
		header("HTTP/1.0 404 Not Found");
	}
	else {
	  $items[$index] = [
		'id' => $id,
		'item' => $collections[$machine_name]['items'][$id],
	  ];
	}
  }
}

?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="include/style.css">
<script type="text/javascript" src="include/editor.js"></script>
<title>vplaylist | video editor</title>
</head>

<body>

  <div class="header">
	<h1><a href="/vplaylist/index.php">vplaylist</a></h1>
	<form class="search" action="/vplaylist/search.php" method="get">
	    <input type="text" maxlength="64" name="q" placeholder="search" />
	</form>
  </div>
  <div class="subnav">
    Video Editor
  </div>

  <div class="video-editor">
    <?php foreach ($indexes as $index): ?>
	<div class="player <?php print $index; ?>">
	<h2><?php print $index; ?></h2>
	<video controls id="<?php print $index; ?>">
		<source src="serve.php?collection=<?php print $machine_name; ?>&index=<?php print $items[$index]['id']; ?>&file=.mp4" type="video/mp4" />
	</video>
	<span id="vid_title" class="label">
		<?php print basename($items[$index]['item']['filename'], '.mp4'); ?>
	</span>
	<button id="<?php print $index; ?>-mark-in">Mark In</button>
	<input type="text" id="<?php print $index; ?>-mark-in-value" />

	<button id="<?php print $index; ?>-mark-out">Mark Out</button>
	<input type="text" id="<?php print $index; ?>-mark-out-value" />
	</div>
    <?php endforeach; ?>
  </div>

  <div class="control-panel">
    <button id="preview">Preview</button>

    <button>Video Clip</button>
    <button>Audio Insert</button>
    <button>Video Insert</button>
    <button>Assemble</button>

  </div>

<?php require_once 'include/footer.php'; ?>
