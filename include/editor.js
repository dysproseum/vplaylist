var player;
var recorder;
var previewing = false;

window.addEventListener("load", function() {
  player = document.getElementById("source");
  recorder = document.getElementById("target");

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

    player.style.filter = "brightness(0)";
    recorder.style.filter = "brightness(0)";
    //player.currentTime = 0;
    //recorder.currentTime = 0;

    setTimeout(function() {

    player.currentTime = sourceMarkInValue.value - 5
    recorder.currentTime = targetMarkInValue.value - 5;

    // check options
    player.muted = true;
    recorder.muted = false;

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

    // do the switch
    // audio insert
    // sourceMarkInValue: unmute
    if (player.currentTime >= sourceMarkInValue.value) {
      player.muted = false;
      recorder.muted = true;
    }

    // switch back
    // audio insert
    // sourceMarkOutValue: mute
    if (player.currentTime >= sourceMarkOutValue.value) {
      player.muted = true;
      recorder.muted = false;
      console.log("source switch back");
    }

    // end 
    if (player.currentTime >= Number(sourceMarkOutValue.value) + 5) {
      console.log(player.currentTime + " " + sourceMarkOutValue.value);
      player.pause();
      recorder.pause();
      console.log("source pause");
      previewing = false;
    }
  });

});
