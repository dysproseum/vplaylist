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
?>

<?php if (isset($_REQUEST['filename'])): ?>
	<?php $vid_file = $_REQUEST['filename']; ?>
	<div class="player">
		<video autoplay controls width="640">
			<source src="serve.php?filename=<?php print $vid_file; ?>" type="video/mp4" />
		</video>
	</div>

<?php endif; ?>

<?php if ($machine_name == ''): ?>
	<h2>Collections</h2>
	<ul>
	<?php foreach ($collections as $collection => $values): ?>
		<li>
		<a href="index.php?collection=<?php print $collection; ?>">
			<?php print $values['name']; ?>
		</a>
		<?php print sizeof($values['items']); ?>
		</li>
	<?php endforeach; ?>
	</ul>

<?php else: ?>

	<h2><?php print $collections[$machine_name]['name']; ?></h2>

	<?php foreach ($collections[$machine_name]['items'] as $item): ?>
		<?php
			$basename = basename($item['filename'], '.mp4');
			$vid_param = base64_encode($item['filename']);
			$thumbnail = THUMBS_PATH . $machine_name . '/' . $basename. '.jpg';
			$vid_link = 'index.php?collection=' . $machine_name . '&filename=' . $vid_param;
		?>

		<div class="thumbnail">
		<a href="<?php print $vid_link; ?>">
			<img src="<?php print $thumbnail; ?>" width="320" />
		</a>
		<a class="label label-top" href="<?php print $vid_link; ?>">
			<?php print $basename ?>
		</a>
		<span class="label label-bottom">
			<?php print human_filesize($item['size']); ?>
		</span>
		</div>

	<?php endforeach; ?>

<?php endif; ?>

<?php require_once 'include/footer.php'; ?>
