<?php
include("../include/bootstrap.php");
include("../include/header.php");
?>

<link rel="stylesheet" href="../include/style.css">

<div class="subnav">
</div>
<div class="listing-box">
   <div class="listing">

      <form action="post.php" class="video-editor">
        <h2>Video Editor</h2>
      
        <label for="video1">Video URL</label>
        <input type="text" name="video1" autocomplete="off" />
        
        <label for="select_collection_name">Choose collection</label>
	<select name="select_collection_name">
          <option selected value="video_editor">Video Editor</option>
          <option value="ambient">Ambient</option>
        </select>

        <input type="submit" />

        <label for="video2">Video 2</label>
        <input type="text" name="video2" disabled placeholder="Coming Soon" />
      
        <label for="email">Email (optional) for notification when complete</label>
        <input type="text" name="email" disabled placeholder="Coming Soon" />
        
      
      </form>
  </div>
</div>

<?php include ("../include/footer.php"); ?>
