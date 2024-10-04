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
$loop = '';
$shuffle = false;
$repeat = '';
if ($conf['start_muted']) {
	$muted = 'muted';
}
else {
	$muted = '';
}

if (isset($_REQUEST['index']) && $machine_name != '') {
	$index = $_REQUEST['index'];
	if (!isset($collections[$machine_name]['items'][$index])) {
		$item = false;
		header("HTTP/1.0 404 Not Found");
	}
	else {
		$item = $collections[$machine_name]['items'][$index];
		$vid_title = basename($item['filename'], '.mp4');
		$duration = seconds_to_clock_time($item['duration']);
		$framerate = isset($item['framerate']) ? $item['framerate'] : 0;
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

// Sort filters.
if (isset($_REQUEST['collection']) && $_REQUEST['collection'] != '') {
  if (isset($_REQUEST['sort']) && $_REQUEST['sort'] == 'name') {
    $items = $collections[$machine_name]['items'];
    if (isset($_REQUEST['order']) && $_REQUEST['order'] == 'desc') {
      uasort($items, 'name_compare_desc');
    }
    else {
      uasort($items, 'name_compare');
    }
    $collections[$machine_name]['items'] = $items;
  }
  else if (isset($_REQUEST['sort']) && $_REQUEST['sort'] == 'date') {
    $items = $collections[$machine_name]['items'];
    if (isset($_REQUEST['order']) && $_REQUEST['order'] == 'desc') {
      $items = array_reverse($items, true);
      $collections[$machine_name]['items'] = $items;
    }
  }
}

// Add'l body classes are set depending on vid_player.
require_once 'include/header.php';

?>

<?php if ($vid_player): ?>
	<div class="player">
	<video autoplay <?php print $controls; ?> <?php print $muted; ?> <?php print $loop; ?> width="640" id="<?php print $vid_player_id; ?>">
		<?php if (is_mobile()): ?>
			<source src="serve.php?collection=<?php print $machine_name; ?>&index=<?php print $index; ?>&file=.mp4" type="video/mp4" />
		<?php else: ?>
			<source src="serve.php?collection=<?php print $machine_name; ?>&index=<?php print $index; ?>&file=.mp4" type="video/mp4" />
		<?php endif; ?>
	</video>
	<span id="vid_title" class="label">
		<?php print $vid_title; ?>
	</span>
	</div>

<?php endif; ?>

<?php if (empty($collections)): ?>
	<div class="subnav">
		<h2>No collections found.</h2>
		<h4><a href="video-editor/index.php">Add more videos</a></h4>
	</div>
<?php elseif ($machine_name == ''): ?>
	<div class="subnav">
		<h2>Collections</h2>
                <div class="subnav-right-side">
			<h4><a href="video-editor/index.php">Add more videos</a></h4>
                </div>
	</div>
	<div class="listing-box">
	<div class="listing">
	<?php foreach ($collections as $collection => $values): ?>
		<?php
			$total = sizeof($values['items']);
			$index = rand(0, $total - 1);
			$thumbnail = 'serve.php?collection=' . $collection . '&index=' . $index . '&file=.jpg';
		?>

		<div class="thumbnail">
		<a href="index.php?collection=<?php print $collection; ?>">
		<img src="<?php print $thumbnail; ?>" width="320" />
		</a>
		<span class="label label-top"><?php print $total; ?> videos</span>
		<a class="label label-bottom" href="index.php?collection=<?php print $collection; ?>"><?php print $values['name']; ?></a>
		</div>
	<?php endforeach; ?>
	</div>
	</div>

<?php elseif (!(isset($collections[$machine_name]))): ?>
	<div class="subnav">
		<h2>Collection not found</h2>
		<?php header("HTTP/1.0 404 Not Found"); ?>
	</div>
<?php elseif (sizeof($collections[$machine_name]['items']) == 0): ?>
	<div class="subnav">
		<h2>Collection is empty.</h2>
	</div>
<?php else: ?>
	<div class="subnav">
	<h4><a href="index.php">Home</a>|<a href="index.php?collection=<?php print $machine_name; ?>"><?php print $collections[$machine_name]['name']; ?></a></h4>
	<?php if ($vid_player): ?>
          <div class="subnav-right-side">
		<input type="hidden" name="vid_count" value="<?php print sizeof($collections[$machine_name]['items']); ?>" />

		<h4>
			<label for="vid_autoplay">Autoplay</label>
			<input type="checkbox" name="vid_autoplay" id="vid_autoplay"
			<?php if ($autoplay): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_shuffle">Shuffle</label>
			<input type="checkbox" name="vid_shuffle" id="vid_shuffle"
			<?php if ($shuffle): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_repeat">Repeat All</label>
			<input type="checkbox" name="vid_repeat" id="vid_repeat"
			<?php if ($repeat): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_loop">Loop</label>
			<input type="checkbox" name="vid_loop" id="vid_loop"
			<?php if ($loop): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_muted">Muted</label>
			<input type="checkbox" name="vid_muted" id="vid_muted"
			<?php if ($muted !== ''): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
          </div>
	<?php else: ?>
          <div class="subnav-right-side">
		<h4><a href="video-editor/index.php">Add more videos</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&index=0&autoplay=1">Play All</a></h4>
		<h4><a href="getnextvideo.php?collection=<?php print $machine_name; ?>">Shuffle All</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&sort=name">Sort a-z</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&sort=name&order=desc">Sort z-a</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&sort=date&order=desc">Oldest first</a></h4>
          </div>
	<?php endif; ?>
	</div>

	<div class="listing-box">
	<div class="listing">
	<?php foreach ($collections[$machine_name]['items'] as $index => $item): ?>
		<?php
			$basename = basename($item['filename'], '.mp4');
			$thumbnail = 'serve.php?collection=' . $machine_name . '&index=' . $index . '&file=.jpg';
			$vid_link = 'index.php?collection=' . $machine_name . '&index=' . $index;
		?>

		<div class="thumbnail">
		<a class="vid-link" data-id="<?php print $index; ?>" href="<?php print $vid_link; ?>">
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
