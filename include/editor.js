var player;
var recorder;
var playerSource;
var playerTarget;
var previewing = false;

window.addEventListener("load", function() {
  player = document.getElementById("player");
  recorder = document.getElementById("recorder");
  playerSource = document.querySelector(".player-player");
  playerTarget = document.querySelector(".player-recorder");
  var audioInsert = document.getElementById("audio-insert");
  var videoInsert = document.getElementById("video-insert");

  var btnSourceMarkIn = document.getElementById("player-mark-in");
  var playerMarkInValue = document.getElementById("player-mark-in-value");
  btnSourceMarkIn.addEventListener("click", function(e) {
   // account for fractions
   var total = player.currentTime;
   var whole = parseInt(total);
   var diff = total - whole;
   diff = diff.toFixed(2);
   var output = secondsToClockTime(whole);
   output += diff.replace('0', '');
   playerMarkInValue.value = output;
  });

  var btnTargetMarkIn = document.getElementById("recorder-mark-in");
  var recorderMarkInValue = document.getElementById("recorder-mark-in-value");
  btnTargetMarkIn.addEventListener("click", function(e) {
   // account for fractions
   var total = recorder.currentTime;
   var whole = parseInt(total);
   var diff = total - whole;
   diff = diff.toFixed(2);
   var output = secondsToClockTime(whole);
   output += diff.replace('0', '');
   recorderMarkInValue.value = output;
  });

  var btnSourceMarkOut = document.getElementById("player-mark-out");
  var playerMarkOutValue = document.getElementById("player-mark-out-value");
  btnSourceMarkOut.addEventListener("click", function(e) {
   // account for fractions
   var total = player.currentTime;
   var whole = parseInt(total);
   var diff = total - whole;
   diff = diff.toFixed(2);
   var output = secondsToClockTime(whole);
   output += diff.replace('0', '');
   playerMarkOutValue.value = output;
  });

  var btnTargetMarkOut = document.getElementById("recorder-mark-out");
  var recorderMarkOutValue = document.getElementById("recorder-mark-out-value");
  btnTargetMarkOut.addEventListener("click", function(e) {
   // account for fractions
   var total = recorder.currentTime;
   var whole = parseInt(total);
   var diff = total - whole;
   diff = diff.toFixed(2);
   var output = secondsToClockTime(whole);
   output += diff.replace('0', '');
   recorderMarkOutValue.value = output;
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
      player.currentTime = playerMarkInValue.value - 5
      recorder.currentTime = recorderMarkInValue.value - 5;

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

    // do the switch at playerMarkInValue
    if (player.currentTime >= playerMarkInValue.value) {
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

    // switch back at playerMarkOutValue
    if (player.currentTime >= playerMarkOutValue.value) {
      // audio insert
      if (audioInsert.checked) {
        // console.log("player switch back");
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
    if (player.currentTime >= Number(playerMarkOutValue.value) + 5) {
      // console.log(player.currentTime + " " + playerMarkOutValue.value);
      // console.log("player pause");

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
    // collection, player and recorder
    // along with set mark ins and outs
    // and edit type
    // are submitted with form
  });

});
