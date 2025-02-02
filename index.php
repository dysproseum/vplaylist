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
$vid_player_id = 'video_element';
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

// Prepare mediaInfo sources.
$vid_src = "serve.php?collection=$machine_name&index=$index&file=.mp4";
$vid_img = "serve.php?collection=$machine_name&index=$index&file=.jpg";

$mediaInfo = [];
foreach ($collections[$machine_name]['items'] as $i => $item) {
  $basename = basename($item['filename'], '.mp4');
  $thumbnail = "serve.php?collection=$machine_name&index=$i&file=.jpg";
  $vid_link = "serve.php?collection=$machine_name&index=$i&file=.mp4";
  $duration = 596;
  $size = human_filesize($item['size']);
  $mediaInfo[] = [
    'title' => $basename,
    'contentUrl' => $vid_link,
    'contentType' => 'video/mp4',
    'thumb' => $thumbnail,
    'duration' => $duration,
    'subtitle' => 'vplaylist',
  ];
}

$mediaInfoJSON = json_encode($mediaInfo, JSON_PRETTY_PRINT);

?>

<?php if ($vid_player): ?>

  <script type="text/javascript">
    let mediaJSON = {
      'media': <?php print $mediaInfoJSON; ?>
    };
    let currentMediaIndex = <?php print $index; ?>;
  </script>

	<div class="player">

          <div class="imageSub" style="display: none">
            <!-- Put Your Image Width -->
            <div class="blackbg" id="playerstatebg">IDLE</div>
            <div class=label id="playerstate">IDLE</div>
            <img src="imagefiles/bunny.jpg" id="video_image">
            <div id="video_image_overlay"></div>
          </div>
          <div id="skip" style="display: none">Skip Ad</div>

	<video autoplay <?php print $controls; ?> <?php print $muted; ?> <?php print $loop; ?> width="640" id="<?php print $vid_player_id; ?>" src="<?php print $vid_src; ?>">
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

	<?php if ($vid_player): ?>
          <div class="castbar">
            <div id="main_video">
              <div id="media_control">
                <div id="play"></div>
                <div id="pause"></div>
                <div id="audio_bg"></div>
                <div id="audio_bg_track"></div>
                <div id="audio_indicator"></div>
                <div id="audio_bg_level"></div>
                <div id="audio_on"></div>
                <div id="audio_off"></div>
                <div id="progress_bar_container">
                  <div id="progress_bg"></div>
                  <div id="seekable_window"></div>
                  <div id="progress"></div>
                  <div id="unseekable_overlay"></div>
                  <div id="progress_indicator"></div>
                </div>
                <input type="range" value="0" min="0" step="1" id="seek_range" />
                <div id="fullscreen_expand"></div>
                <div id="fullscreen_collapse"></div>
                <google-cast-launcher id="castbutton"></google-cast-launcher>
                <div id="currentTime">00:00:00</div>
                <div id="duration">00:00:00</div>
                <img id="live_indicator">
              </div>
            </div>
            <div id="media_info">
              <div id="media_title"></div>
              <div id="feature_toggle_container">
                <input type="radio" id="none" name="feature" value="none" checked>None<br>
                <input type="radio" id="ads" name="feature" value="ads">Ads<br>
                <input type="radio" id="live" name="feature" value="live">Live
              </div>
              <div id="media_subtitle"></div>
            </div>
          </div>
	<?php endif; ?>

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

	<?php if ($vid_player): ?>
  <div id="carousel"></div>
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
