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
$controls = 'nocontrols';
$vid_player_id = 'video_element';
$loop = '';
$shuffle = false;
$repeat = '';
$autoplay = false;
$muted = '';
if (isset($conf['start_muted'])) {
	$muted = ($conf['start_muted'] == true) ? 'muted' : '';
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
		$controls = ($_REQUEST['controls'] == 1) ? 'controls' : 'nocontrols';
	}
        // if ($controls)
	//	$controls = '';
	//      $vid_player_id = 'background-video';
	//	$autoplay = true;
	//}
	if (isset($_REQUEST['autoplay'])) {
		$autoplay = ($_REQUEST['autoplay'] == 1) ? true : false;
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
  $size = human_filesize($item['size']);
  $mediaInfo[] = [
    'title' => $basename,
    'contentUrl' => $vid_link,
    'contentType' => 'video/mp4',
    'thumb' => $thumbnail,
    'duration' => $item['duration'],
    'subtitle' => 'vplaylist',
  ];
}
// Make sure JS indices line up with index values.
$mediaInfo = array_reverse($mediaInfo);

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

	<video <?php print $autoplay ? "autoplay" : ""; ?> <?php print $controls; ?> <?php print $muted; ?> <?php print $loop; ?> width="640" id="<?php print $vid_player_id; ?>" src="<?php print $vid_src; ?>">
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

	<?php if ($vid_player): ?>
        <div class="supernav">
          <div class="supernav-left-side">
            <?php include "include/javascript-equalizer.html"; ?>
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
		<div id="volvalue">0</div>
		<input type="range" class="vertical" id="volslider" />
		<input type="range" class="horizontal" min="-1" max="1" step="0.1" id="panner" />
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
		<input type="range" class="vertical" min="0.1" max="2" step="0.05" id="vidspeed" />
	    <div class="time-box">
              <div id="player-time">00:00:00</div>
              <div id="player-duration">&nbsp;/ <?php print $duration; ?></div>
              <div id="player-status" hidden>&nbsp;</div>
              <script type="text/javascript">framerate = <?php print $framerate; ?>;</script>
            </div>
            <a onclick="body.classList.toggle('backlight')">Backlight</a>
            <a onclick="body.classList.toggle('fixed-supernav')">Mode</a>
            <a id="maximize" onclick="toggleFullscreen()">Fullscreen</a>
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
	<div class="subnav">
	  <h4><a href="index.php">Home</a>|<a href="index.php?collection=<?php print $machine_name; ?>"><?php print $collections[$machine_name]['name']; ?></a></h4>
          <div class="subnav-right-side">
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
	<?php endif; ?>
	</div>

	<?php if ($vid_player): ?>
  <div id="carousel"></div>
	<?php endif; ?>
	</div>

	<div class="listing-box">
	<div class="listing">
	<?php foreach ($collections[$machine_name]['items'] as $i => $item): ?>
		<?php
			$basename = basename($item['filename'], '.mp4');
			$thumbnail = 'serve.php?collection=' . $machine_name . '&index=' . $i . '&file=.jpg';
			$vid_link = 'index.php?collection=' . $machine_name . '&index=' . $i;
		?>

		<div class="thumbnail">
		<a class="vid-link" data-id="<?php print $i; ?>" href="<?php print $vid_link; ?>">
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
