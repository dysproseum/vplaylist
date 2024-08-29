<?php

chdir(dirname(__FILE__) . "/../");
include(dirname(__FILE__) . "/../include/bootstrap.php");
global $collections;

include(dirname(__FILE__) . "../include/header.php");

?>

<link rel="stylesheet" href="../include/style.css">

<div class="subnav">
</div>
<div class="listing-box">
   <div class="listing">

      <form action="post.php" method="post" class="video-editor">
        <h2>Video Editor</h2>
      
        <label for="video1">Video URL</label>
        <input type="text" name="video1" autocomplete="off" />
        
        <label for="select_collection_name">Choose collection</label>
	<select name="select_collection_name">
          <?php foreach ($collections as $name => $values): ?>
            <option value="<?php print $name; ?>"><?php print $values['name']; ?></option>
          <?php endforeach; ?>
        </select>

        <input type="submit" />

        <label for="video2">Video 2</label>
        <input type="text" name="video2" disabled placeholder="Coming Soon" />
      
        <label for="email">Email (optional) for notification when complete</label>
        <input type="text" name="email" disabled placeholder="Coming Soon" />
        
      
      </form>
  </div>
</div>

<?php include(dirname(__FILE__) . "../include/footer.php"); ?>
