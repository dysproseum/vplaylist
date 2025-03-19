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
if (isset($conf['controls']) && $conf['controls'] == true) {
  $controls = 'controls';
}
else {
  $controls = '';
}
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
		$vid_height = isset($item['height']) ? $item['height'] : 0;
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
	<video autoplay <?php print $controls; ?> <?php print $muted; ?> <?php print $loop; ?> height="360" id="<?php print $vid_player_id; ?>">
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
	<?php if ($vid_player && $controls == ''): ?>
        <div class="supernav">
          <div class="supernav-left-side">
            <div class="player-controls">
		<h4>
			<label for="vid_muted">
				<i class="fa-sharp fa-solid fa-volume-xmark" title="Mute"></i>
			</label>
			<input type="checkbox" name="vid_muted" id="vid_muted"
			<?php if ($muted !== ''): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
            </div>
            <?php include "include/javascript-equalizer.html"; ?>
            <div class="player-controls">
		<div id="volvalue">0</div>
		<input type="range" class="vertical" id="volslider" title="Volume" />
		<input type="range" class="horizontal" min="-1" max="1" step="0.1" id="panner" title="Pan Left/Right" />
            </div>
          </div>
          <div class="supernav-middle">
            <div class="player-controls">
		  <div class="button">
			<a id="player-rec"><i class="fa-sharp fa-solid fa-circle"></i></a>
		  </div>
		  <div class="button">
			<a id="player-play"><i class="fa-sharp fa-solid fa-play"></i></a>
		  </div>
		  <div class="button">
			<a id="player-stop"><i class="fa-sharp fa-solid fa-stop"></i></a>
		  </div>
		  <div class="button">
			<a id="player-prev"><i class="fa-sharp fa-solid fa-backward-fast"></i></a>
		  </div>
		  <div class="button">
			<a id="player-next"><i class="fa-sharp fa-solid fa-forward-fast"></i></a>
		  </div>
		  <div class="button">
			<a id="player-pause"><i class="fa-sharp fa-solid fa-pause"></i></a>
		  </div>
	    </div>
          </div>
          <div class="supernav-right-side">
            <div class="player-controls">
		<div id="speedvalue">1.0x</div>
		<input type="range" class="horizontal" value="0" min="0" step="0.1" id="seek_range" title="Seek Range" />
		<input type="range" class="vertical" min="0.1" max="2" step="0.1" value="1" id="vidspeed" title="Speed" />
            </div>
	    <div class="time-box">
              <div id="player-timeline">
                <span id="player-time">00:00:00</span>
                /
                <span id="player-duration"><?php print $duration; ?></span>
              </div>
              <div id="player-status" hidden>&nbsp;</div>
              <script type="text/javascript">
                framerate = <?php print $framerate; ?>;
		vidHeight = <?php print $vid_height; ?>;
	      </script>
            </div>

            <div class="player-controls">
		<h4>
			<a id="nextsong">
				<i class="fa-sharp fa-solid fa-forward-fast" title="Mute"></i>
			</a>
		</h4>
            </div>

          </div>
        </div>
	<div class="subnav">
	  <h4><a href="index.php">Home</a>|<a href="index.php?collection=<?php print $machine_name; ?>"><?php print $collections[$machine_name]['name']; ?></a></h4>
          <div class="subnav-right-side">
              <div class="player-controls">
		<input type="hidden" name="vid_count" value="<?php print sizeof($collections[$machine_name]['items']); ?>" />

		<h4>
			<label for="vid_autoplay">
				<i class="fa-sharp fa-solid fa-rotate" title="Autoplay"></i>
			</label>
			<input type="checkbox" name="vid_autoplay" id="vid_autoplay"
			<?php if ($autoplay): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_shuffle">
				<i class="fa-sharp fa-solid fa-shuffle" title="Shuffle"></i>
			</label>
			<input type="checkbox" name="vid_shuffle" id="vid_shuffle"
			<?php if ($shuffle): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_repeat">
				<i class="fa-sharp fa-solid fa-repeat" title="Repeat All"></i>
			</label>
			<input type="checkbox" name="vid_repeat" id="vid_repeat"
			<?php if ($repeat): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		<h4>
			<label for="vid_loop">
				<i class="fa-sharp fa-solid fa-1" title="Repeat 1"></i>
			</label>
			<input type="checkbox" name="vid_loop" id="vid_loop"
			<?php if ($loop): ?>
				checked="checked"
			<?php endif; ?>
			/>
		</h4>
		</div>
          </div>
	</div>
	<?php else: ?>
	<div class="subnav">
	  <h4><a href="index.php">Home</a>|<a href="index.php?collection=<?php print $machine_name; ?>"><?php print $collections[$machine_name]['name']; ?></a></h4>
          <div class="subnav-right-side">
		<h4><a href="video-editor/index.php">Add more videos</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&index=0&autoplay=1">Play All</a></h4>
		<h4><a href="getnextvideo.php?collection=<?php print $machine_name; ?>">Shuffle All</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&sort=name">Sort a-z</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&sort=name&order=desc">Sort z-a</a></h4>
		<h4><a href="index.php?collection=<?php print $machine_name; ?>&sort=date&order=desc">Oldest first</a></h4>
          </div>
	</div>
	<?php endif; ?>

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
