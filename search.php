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
		foreach ($values as $index => $item) {
			$filename = strtolower(basename($item['filename']));
			if (strstr($filename, $q) !== false) {
				$item['machine_name'] = $name;
				$item['index'] = $index;
				$matches[] = $item;
			}
		}
	}
}

?>

<div class="listing-box">
<?php foreach ($matches as $item): ?>
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

<?php endforeach; ?>
</div>
