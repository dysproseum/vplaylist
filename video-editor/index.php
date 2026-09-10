<?php

chdir(dirname(__FILE__) . "/../");
include(dirname(__FILE__) . "/../include/bootstrap.php");
global $collections;

include(dirname(__FILE__) . "/../include/header.php");

?>

<link rel="stylesheet" href="../include/style.css">

<div class="subnav">
  <h2>Video Editor</h2>
  <div class="subnav-right-side">
    <h4><a href="/vplaylist/editor.php">Editor</a></h4>
    <h4><a href="download.php">Import Status</a></h4>
  </div>
</div>
<div class="listing-box">
   <div class="listing">

      <form action="post.php" method="post" class="video-editor">
      
        <label for="video1">Video URL</label>
        <input type="text" name="video1" autocomplete="off" />
        
        <label for="select_collection_name">Choose collection</label>
	<select name="select_collection_name">
          <option>- Select -</option>
          <?php foreach ($collections as $name => $values): ?>
            <option value="<?php print $name; ?>"><?php print $values['name']; ?></option>
          <?php endforeach; ?>
        </select>

        <input type="submit" />

      </form>
  </div>
</div>

<?php include(dirname(__FILE__) . "/../include/footer.php"); ?>
