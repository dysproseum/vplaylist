<?php require_once 'include/header.php'; ?>

<form action="install.php" method="post">

<?php if (isset($_REQUEST['count'])):

	$path = trim($_REQUEST['path']);
	$count = 0;
	if (!isset($_REQUEST['collection'])) {
		print "Invalid collection name";
	}
	else {
		$name = trim($_REQUEST['collection']);
		$machine_name = preg_replace('/[^a-z0-9]+/', '_', strtolower($name));

		$collection = array();
		$collection[$machine_name] = array();
		$collection[$machine_name]['name'] = $name;
		$collection[$machine_name]['items'] = array();

		foreach(glob($path.'*') as $filename){
			$collection[$machine_name]['items'][] = array(
				'filename' => $filename,
				'size' => filesize($filename),
				'length' => FALSE,
				'thumbnail' => FALSE,
			);
		}
		$json = json_encode($collection);

		// @todo write out collection.json to config path
		$fp = fopen(CONFIG_PATH . $machine_name . '.json', 'w');
		if ($fp) {
			fwrite($fp, $json);
			fclose($fp);
		}
		else {
			print "Failed to open CONFIG_PATH for writing";
		}
		print '<pre>' . $json . '</pre>';
	}

?>

<?php elseif (isset($_REQUEST['path'])):

	$path = trim($_REQUEST['path']);
	$count = 0;
	foreach(glob($path.'*') as $filename){
		$count++;
	}
?>

	<label for="count">Found files</label>
	<input type="text" name="count" value="<?php print $count; ?>" readonly="readonly" />
	<label for="path">Directory Path</label>
	<input type="text" name="path" value="<?php print $path; ?>" size="36" readonly="readonly" />
	<label for="collection">Name for this collection</label>
	<input type="text" name="collection" maxlength="255" />

<?php else: ?>

	<label for="path">Add Directory Path with trailing slash</label>
	<input type="text" name="path" maxlength="255" size="36" />

<?php endif; ?>

<input type="submit" value="Continue" />
</form>

<?php require_once 'include/footer.php'; ?>
