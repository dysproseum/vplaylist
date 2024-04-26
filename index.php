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

global $vid_player;
$vid_player = FALSE;
global $controls;
$controls = 'controls';
$vid_player_id = '';
$muted = 'muted';
$loop = '';

if (isset($_REQUEST['index']) && $machine_name != '') {
	$index = $_REQUEST['index'];
	if (!isset($collections[$machine_name]['items'][$index])) {
		$item = false;
		header("HTTP/1.0 404 Not Found");
	}
	else {
		$item = $collections[$machine_name]['items'][$index];
		$vid_file = base64_encode(addslashes($item['filename']));
		$vid_title = basename($item['filename'], '.mp4');
		$vid_player = TRUE;
	}

	// No controls: kiosk mode, autoplay.
	if (isset($_REQUEST['controls'])) {
		$controls = '';
	        $vid_player_id = 'background-video';
		$autoplay = true;
	}
	else {
		$autoplay = isset($_REQUEST['autoplay']) ? true : false;
	}

	if (isset($_REQUEST['muted']))
	       $muted = ($_REQUEST['muted'] == 1) ? 'muted' : '';
	if (isset($_REQUEST['loop']))
	       $loop = ($_REQUEST['loop'] == 1) ? 'loop' : '';
	if (isset($_REQUEST['shuffle']))
	       $shuffle = ($_REQUEST['shuffle'] == 1) ? true : false;
	if (isset($_REQUEST['repeat']))
	       $repeat = ($_REQUEST['repeat'] == 1) ? 'repeat' : '';
}

// Add'l body classes are set depending on vid_player.
require_once 'include/header.php';

?>

<?php if ($vid_player): ?>
	<div class="player">
	<video <?php print $controls; ?> <?php print $muted; ?> <?php print $loop; ?> autoplay width="640" id="<?php print $vid_player_id; ?>">
		<?php if (is_mobile()): ?>
			<source src="serve.php?filename=<?php print $vid_file; ?>" type="video/mp4" />
		<?php else: ?>
			<source src="serve.php?filename=<?php print $vid_file; ?>&file=.mp4" type="video/mp4" />
		<?php endif; ?>
		</video>
		<span class="label">
			<?php print $vid_title; ?>
		</span>
	</div>

<?php endif; ?>

<?php if ($machine_name == ''): ?>
	<div class="subnav">
		<h2>Collections</h2>
		<h4><a href="video-editor/index.php">Add more videos</a></h4>
	</div>
	<div class="listing-box">
	<div class="listing">
	<?php foreach ($collections as $collection => $values): ?>
		<?php
			$total = sizeof($values['items']);
			$index = rand(0, $total - 1);
			$thumbnail = basename($collections[$collection]['items'][$index]['filename'], '.mp4') . '.jpg';
			$thumbnail = rawurlencode($thumbnail);
			$url = THUMBS_PATH . "$collection/$thumbnail";
		?>

		<div class="thumbnail">
		<a href="index.php?collection=<?php print $collection; ?>">
		<img src="<?php print $url; ?>" width="320" />
		</a>
		<a class="label label-top" href="index.php?collection=<?php print $collection; ?>"><?php print $values['name']; ?></a>
		<span class="label label-bottom"><?php print $total; ?> videos</span>
		</div>
	<?php endforeach; ?>
	</div>
	</div>

<?php elseif (!(isset($collections[$machine_name]))): ?>

	<div class="subnav">
		<h2>Collection not found</h2>
		<?php header("HTTP/1.0 404 Not Found"); ?>
	</div>

<?php else: ?>

	<div class="subnav">
	<h2><a href="index.php">Home</a> | <?php print $collections[$machine_name]['name']; ?></h2>
	<?php if ($vid_player): ?>
		<input type="hidden" name="vid_count" value="<?php print sizeof($collections[$machine_name]['items']); ?>" />

		<h4>
			<label for="vid_autoplay">Autoplay</label>
			<input type="checkbox" name="vid_autoplay"
			<?php if ($autoplay): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_shuffle">Shuffle</label>
			<input type="checkbox" name="vid_shuffle"
			<?php if ($shuffle): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_repeat">Repeat All</label>
			<input type="checkbox" name="vid_repeat"
			<?php if ($repeat): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_loop">Loop</label>
			<input type="checkbox" name="vid_loop"
			<?php if ($loop): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_muted">Muted</label>
			<input type="checkbox" name="vid_muted"
			<?php if ($muted !== ''): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
	<?php else: ?>
		<h4><a href="video-editor/index.php">Add more videos</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&index=0&autoplay=1">Play All</a></h4>
	<?php endif; ?>
	</div>

	<div class="listing-box">
	<div class="listing">
	<?php foreach ($collections[$machine_name]['items'] as $index => $item): ?>
		<?php
			$basename = basename($item['filename'], '.mp4');
			$vid_param = base64_encode($item['filename']);
			$thumbnail = THUMBS_PATH . $machine_name . '/' . $basename . '.jpg';
			$vid_link = 'index.php?collection=' . $machine_name . '&index=' . $index;
		?>

		<div class="thumbnail">
		<a href="<?php print $vid_link; ?>">
			<img src="<?php print $thumbnail; ?>" width="320" />
		</a>
		<a class="label label-top" href="<?php print $vid_link; ?>">
			<?php print human_filesize($item['size']); ?>
		</a>
		<span class="label label-bottom">
			<?php print $basename; ?>
		</span>
		</div>

	<?php endforeach; ?>
	</div>
	</div>

<?php endif; ?>

<?php require_once 'include/footer.php'; ?>
