var player;
var recorder;
var playerContainer;
var recorderContainer;
var previewing = false;

window.addEventListener("load", function() {
  player = document.getElementById("player");
  recorder = document.getElementById("recorder");
  playerContainer = document.querySelector(".player-player");
  recorderContainer = document.querySelector(".player-recorder");
  var audioInsert = document.getElementById("audio-insert");
  var videoInsert = document.getElementById("video-insert");

  var btnPlayerMarkIn = document.getElementById("player-mark-in");
  var playerMarkInValue = document.getElementById("player-mark-in-value");
  btnPlayerMarkIn.addEventListener("click", function(e) {
   playerMarkInValue.value = secondsToTimeCode(player.currentTime);
  });

  var btnRecorderMarkIn = document.getElementById("recorder-mark-in");
  var recorderMarkInValue = document.getElementById("recorder-mark-in-value");
  btnRecorderMarkIn.addEventListener("click", function(e) {
   recorderMarkInValue.value = secondsToTimeCode(recorder.currentTime);
  });

  var btnPlayerMarkOut = document.getElementById("player-mark-out");
  var playerMarkOutValue = document.getElementById("player-mark-out-value");
  btnPlayerMarkOut.addEventListener("click", function(e) {
   playerMarkOutValue.value = secondsToTimeCode(player.currentTime);
  });

  var btnRecorderMarkOut = document.getElementById("recorder-mark-out");
  var recorderMarkOutValue = document.getElementById("recorder-mark-out-value");
  btnRecorderMarkOut.addEventListener("click", function(e) {
   recorderMarkOutValue.value = secondsToTimeCode(recorder.currentTime);
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
      player.currentTime = timeCodeToSeconds(playerMarkInValue.value) - 5
      recorder.currentTime = timeCodeToSeconds(recorderMarkInValue.value) - 5;

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
    if (player.currentTime >= timeCodeToSeconds(playerMarkInValue.value)) {
      // audio insert
      if (audioInsert.checked) {
        player.muted = false;
        recorder.muted = true;
      }

      // video insert
      if (videoInsert.checked) {
        recorder.style.display = "none";
        recorderContainer.append(player);
      }
    }

    // switch back at playerMarkOutValue
    if (player.currentTime >= timeCodeToSeconds(playerMarkOutValue.value)) {
      // audio insert
      if (audioInsert.checked) {
        // console.log("player switch back");
        player.muted = true;
        recorder.muted = false;
      }

      // video insert
      if (videoInsert.checked) {
        recorder.style.display = null;
        playerContainer.append(player);
      }
    }

    // end at +5 seconds
    if (player.currentTime >= timeCodeToSeconds(playerMarkOutValue.value) + 5) {
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
    player.style.filter = null;
    recorder.style.filter = null;
  });

  var btnRecord = document.getElementById("record");
  btnRecord.addEventListener("click", function() {
    // collection, player and recorder
    // along with set mark ins and outs
    // and edit type
    // are submitted with form
  });

});
