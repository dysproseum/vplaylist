var player;
var recorder;
var playerSource;
var playerTarget;
var previewing = false;

window.addEventListener("load", function() {
  player = document.getElementById("source");
  recorder = document.getElementById("target");
  playerSource = document.querySelector(".player-source");
  playerTarget = document.querySelector(".player-target");
  var audioInsert = document.getElementById("audio-insert");
  var videoInsert = document.getElementById("video-insert");

  var btnSourceMarkIn = document.getElementById("source-mark-in");
  var sourceMarkInValue = document.getElementById("source-mark-in-value");
  btnSourceMarkIn.addEventListener("click", function(e) {
   sourceMarkInValue.value = player.currentTime.toFixed(2);
  });

  var btnTargetMarkIn = document.getElementById("target-mark-in");
  var targetMarkInValue = document.getElementById("target-mark-in-value");
  btnTargetMarkIn.addEventListener("click", function(e) {
   targetMarkInValue.value = recorder.currentTime.toFixed(2);
  });

  var btnSourceMarkOut = document.getElementById("source-mark-out");
  var sourceMarkOutValue = document.getElementById("source-mark-out-value");
  btnSourceMarkOut.addEventListener("click", function(e) {
   sourceMarkOutValue.value = player.currentTime.toFixed(2);
  });

  var btnTargetMarkOut = document.getElementById("target-mark-out");
  var targetMarkOutValue = document.getElementById("target-mark-out-value");
  btnTargetMarkOut.addEventListener("click", function(e) {
   targetMarkOutValue.value = recorder.currentTime.toFixed(2);
  });

  var btnPreview = document.getElementById("preview");
  btnPreview.addEventListener("click", function() {
    previewing = true;

    player.muted = true;
    recorder.muted = false;
    player.style.filter = "brightness(0)";
    recorder.style.filter = "brightness(0)";

    // cue up at -5 seconds
    setTimeout(function() {
      player.currentTime = sourceMarkInValue.value - 5
      recorder.currentTime = targetMarkInValue.value - 5;

      // check options
      if (audioInsert.checked) {
        //player.muted = true;
        //recorder.muted = false;
      }

      player.play();
      recorder.play();

      player.style.filter = null;
      recorder.style.filter = null;
    }, 1000);
    
  });

  player.addEventListener("timeupdate", function() {
    if (previewing == false) {
      return;
    }

    // do the switch at sourceMarkInValue
    if (player.currentTime >= sourceMarkInValue.value) {
      // audio insert
      if (audioInsert.checked) {
        player.muted = false;
        recorder.muted = true;
      }

      // video insert
      if (videoInsert.checked) {
        recorder.style.display = "none";
        playerTarget.append(player);
      }
    }

    // switch back at sourceMarkOutValue
    if (player.currentTime >= sourceMarkOutValue.value) {
      // audio insert
      if (audioInsert.checked) {
        // console.log("source switch back");
        player.muted = true;
        recorder.muted = false;
      }

      // video insert
      if (videoInsert.checked) {
        recorder.style.display = null;
        playerSource.append(player);
      }
    }

    // end at +5 seconds
    if (player.currentTime >= Number(sourceMarkOutValue.value) + 5) {
      // console.log(player.currentTime + " " + sourceMarkOutValue.value);
      // console.log("source pause");

      player.pause();
      recorder.pause();
      previewing = false;
    }
  });

  recorder.addEventListener("timeupdate", function() {
    // @todo add events here also?
  });

  var btnStop = document.getElementById("stop");
  btnStop.addEventListener("click", function() {
    player.pause();
    recorder.pause();
    previewing = false;
  });

  var btnRecord = document.getElementById("record");
  btnRecord.addEventListener("click", function() {
    // collection, source and target
    // along with set mark ins and outs
    // and edit type
    // are submitted with form
  });

});
