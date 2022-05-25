<?php

require_once 'include/bootstrap.php';
require_once 'include/header.php';

if (isset($_REQUEST['q'])) {
  $q = strtolower($_REQUEST['q']);
}
else {
  exit;
}

$matches = array();
foreach ($collections as $name => $values) {

	foreach ($collections[$name] as $index => $values) {
		if ($index == "name") continue;
		foreach ($values as $key => $item) {
			$filename = strtolower(basename($item['filename']));
			if (strstr($filename, $q) !== false) {
				$item['machine_name'] = $name;
				$item['index'] = $key;
				$matches[] = $item;
			}
		}
	}
}

?>
<div class="subnav">
  <h2>Search results</h2>
</div>
<div class="listing-box">
<?php foreach ($matches as $item): ?>
  <div class="listing">
	<?php
	        $basename = basename($item['filename'], '.mp4');
	        $vid_param = base64_encode($item['filename']);
	        $thumbnail = THUMBS_PATH . $item['machine_name'] . '/' . $basename . '.jpg';
	        $vid_link = 'index.php?collection=' . $item['machine_name'] . '&index=' . $item['index'];
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
  </div>
<?php endforeach; ?>
</div>
