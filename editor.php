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

$editing_jobs = [];
if (isset($_REQUEST['edit'])) {
  // send a message to addJob($_REQUEST);

  $editing_jobs[] = $_REQUEST['edit'];
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

<body class="editor">

  <div class="header">
	<h1><a href="/vplaylist/index.php">vplaylist</a></h1>
	<form class="search" action="/vplaylist/search.php" method="get">
	    <input type="text" maxlength="64" name="q" placeholder="search" />
	</form>
  </div>
  <div class="subnav">
    Video Editor
  </div>

  <form action="/vplaylist/editor.php?<?php print $_SERVER['QUERY_STRING']; ?>" method="post">
  <div class="video-editor">
    <?php foreach ($indexes as $index): ?>
      <div class="player <?php print $index; ?>">
        <h2><?php print $index; ?></h2>
        <div class="player-<?php print $index; ?>">
	  <video controls id="<?php print $index; ?>">
            <source src="serve.php?collection=<?php print $machine_name; ?>&index=<?php print $items[$index]['id']; ?>&file=.mp4" type="video/mp4" />
	  </video>
        </div>

	<span id="vid_title" class="label">
		<?php print basename($items[$index]['item']['filename'], '.mp4'); ?>
	</span>
	<button type="button" id="<?php print $index; ?>-mark-in">Mark In</button>
	<input type="text" id="<?php print $index; ?>-mark-in-value" name="<?php print $index; ?>-mark-in-value" />

	<button type="button" id="<?php print $index; ?>-mark-out">Mark Out</button>
	<input type="text" id="<?php print $index; ?>-mark-out-value" name="<?php print $index; ?>-mark-out-value" />
      </div>
    <?php endforeach; ?>
  </div>

  <div class="control-panel">
    <button type="button" id="preview">Preview</button>
    <button type="button" id="stop">Stop</button>
    <input type="submit" id="record" value="Record" />

    <input type="checkbox" id="audio-insert" value="audio_insert" />
    <label for="audio-insert">Audio Insert</label>

    <input type="checkbox" id="video-insert" value="video_insert" />
    <label for="video-insert">Video Insert</label>

    <input type="radio" name="edit" id="edit-insert" value="insert" />
    <label for="edit-insert">Insert</label>
    <input type="radio" name="edit" id="edit-assemble" value="assemble" />
    <label for="edit-assemble">Assemble</label>
    <input type="radio" name="edit" id="edit-clip" value="clip" />
    <label for="edit-clip">Video Clip</label>

  </div>
  </form>

  <?php if ($editing_jobs): ?>
    <div class="jobs">
      <?php print_r($_REQUEST); ?>
    </div>
  <?php endif; ?>

<?php require_once 'include/footer.php'; ?>
