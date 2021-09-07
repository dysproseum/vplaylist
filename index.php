<?php

require_once 'include/bootstrap.php';
require_once 'include/header.php';

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

$vid_player = FALSE;
if (isset($_REQUEST['index']) && $machine_name != '') {
	$index = $_REQUEST['index'];
	$item = $collections[$machine_name]['items'][$index];
	$vid_file = base64_encode($item['filename']);
	$vid_title = basename($item['filename'], '.mp4');
	$vid_player = TRUE;

	$autoplay = isset($_REQUEST['autoplay']) ? true : false;
	$shuffle = isset($_REQUEST['shuffle']) ? true : false;

}
?>

<?php if ($vid_player): ?>
	<div class="player">
		<video autoplay controls width="640">
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
	</div>
	<div class="listing-box">
	<div class="listing">
	<?php foreach ($collections as $collection => $values): ?>
		<?php
			$total = sizeof($values['items']);
			$index = rand(0, $total - 1);
			$thumbnail = basename($collections[$collection]['items'][$index]['filename'], '.mp4') . '.jpg';
		?>

		<div class="thumbnail">
		<a href="index.php?collection=<?php print $collection; ?>">
		<img src="<?php print THUMBS_PATH . $collection . '/' . $thumbnail; ?>" width="320" />
		</a>
		<a class="label label-top" href="index.php?collection=<?php print $collection; ?>"><?php print $values['name']; ?></a>
		<span class="label label-bottom"><?php print $total; ?> videos</span>
		</div>
	<?php endforeach; ?>
	</div>
	</div>

<?php else: ?>

	<div class="subnav">
	<h2><a href="index.php">Home</a> | <?php print $collections[$machine_name]['name']; ?></h2>
	<?php if ($vid_player): ?>
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
	<?php else: ?>
		<h4>
			<a href="index.php?collection=<?php print $machine_name; ?>&index=0&autoplay=1">Play All</a>
		</h4>
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
			<?php print $basename; ?>
		</a>
		<span class="label label-bottom">
			<?php print human_filesize($item['size']); ?>
		</span>
		</div>

	<?php endforeach; ?>
	</div>
	</div>

<?php endif; ?>

<?php require_once 'include/footer.php'; ?>
