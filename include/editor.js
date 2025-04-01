var player;
var recorder;
var playerContainer;
var recorderContainer;
var previewing = false;
var canvas;
var context;
var previewInterval;

window.addEventListener("load", function() {
  player = document.getElementById("player");
  recorder = document.getElementById("recorder");

  player.volume = 0.1;
  recorder.volume = 0.1;

  playerContainer = document.querySelector(".player-player");
  recorderContainer = document.querySelector(".player-recorder");

  // controls to load videos into players.
  var cols = document.querySelectorAll(".load-collection");
  cols.forEach(function(col, index) {
    col.addEventListener("change", function() {
      // which player
      var target = this.dataset.player;

      // first hide all
      var itemLists = document.querySelectorAll("[data-player=" + target + "].load-item");
      itemLists.forEach(function(item, index) {
        item.style.display = "none";
      });

      var itemList = document.getElementById(target + "-collection-" + this.value);
      itemList.style.display = null;
    });
  });

  var itemLists = document.querySelectorAll(".load-item");
  itemLists.forEach(function(item, index) {
    item.addEventListener("change", function() {
      var playerTarget;
      var machine_name;
      if (this.dataset.player == "player") {
        playerTarget = player;
        machine_name = cols[0].value;
      }
      else if (this.dataset.player == "recorder") {
        playerTarget = recorder;
        machine_name = cols[1].value;
      }

      // load video
      playerTarget.src = '/vplaylist/serve.php?collection=' + machine_name + '&index=' + this.value + '&file=.mp4';
      // @todo set form values for edit submission
      var targetId = document.getElementById(this.dataset.player + "_id");
      targetId.value = this.value;
      var targetCollection = document.getElementById(this.dataset.player + "_collection");
      targetCollection.value = machine_name;
    });
  });

  var editInsert = document.getElementById("edit-insert");
  var audioInsert = document.getElementById("audio-insert");
  var videoInsert = document.getElementById("video-insert");
  var editAssemble = document.getElementById("edit-assemble");
  var editClip = document.getElementById("edit-clip");
  var editDub = document.getElementById("edit-dub");

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
    // @todo unset other mark out?
    playerMarkOutValue.value = secondsToTimeCode(player.currentTime);
  });

  var btnRecorderMarkOut = document.getElementById("recorder-mark-out");
  var recorderMarkOutValue = document.getElementById("recorder-mark-out-value");
  btnRecorderMarkOut.addEventListener("click", function(e) {
    recorderMarkOutValue.value = secondsToTimeCode(recorder.currentTime);
  });

  var playerTimeCounter = document.getElementById("player-time-counter");
  var recorderTimeCounter = document.getElementById("recorder-time-counter");

  var insertPreview = function() {
    var source = recorder;
    // end at +5 seconds
    if (player.currentTime >= timeCodeToSeconds(playerMarkOutValue.value) + 5) {
      previewDone();
      return;
    }

    // switch back at playerMarkOutValue
    else if (player.currentTime >= timeCodeToSeconds(playerMarkOutValue.value)) {
      if (videoInsert.checked) {
        source = recorder;
      }
      else if (audioInsert.checked) {
        recorder.muted = false;
        player.muted = true;
      }
    }

    // do the switch at playerMarkInValue
    else if (player.currentTime >= timeCodeToSeconds(playerMarkInValue.value)) {
      if (videoInsert.checked) {
        source = player;
      }
      else if (audioInsert.checked) {
        recorder.muted = true;
        player.muted = false;
      }
    }

    context.drawImage(source, 0, 0, canvas.width, canvas.height);

    // also update time counters.
    playerTimeCounter.innerHTML = secondsToTimeCode(player.currentTime);
    recorderTimeCounter.innerHTML = secondsToTimeCode(recorder.currentTime);
  };

  var assemblePreview = function() {
    var source = recorder;
    // end at playerMarkOutValue
    if (player.currentTime >= timeCodeToSeconds(playerMarkOutValue.value)) {
      previewDone();
      return;
    }

    // do the switch at playerMarkInValue
    else if (player.currentTime >= timeCodeToSeconds(playerMarkInValue.value)) {
      source = player;
      recorder.muted = true;
      player.muted = false;
    }

    context.drawImage(source, 0, 0, canvas.width, canvas.height);

    // also update time counters.
    playerTimeCounter.innerHTML = secondsToTimeCode(player.currentTime);
    recorderTimeCounter.innerHTML = secondsToTimeCode(recorder.currentTime);
  };

  var clipPreview = function() {
    // @todo allow for looping?

    var source = player;
    // end at playerMarkOutValue
    if (player.currentTime >= timeCodeToSeconds(playerMarkOutValue.value)) {
      player.currentTime = timeCodeToSeconds(playerMarkInValue.value);
    }

    // do the switch at playerMarkInValue
    else if (player.currentTime >= timeCodeToSeconds(playerMarkInValue.value)) {
      source = player;
      recorder.muted = true;
      player.muted = false;
    }

    context.drawImage(source, 0, 0, canvas.width, canvas.height);

    // also update time counters.
    playerTimeCounter.innerHTML = secondsToTimeCode(player.currentTime);
    recorderTimeCounter.innerHTML = secondsToTimeCode(recorder.currentTime);
  };

  var dubPreview = function() {
    var source = recorder;
    recorder.muted = true;
    player.muted = false;

    // end at player duration
    if (player.currentTime >= player.duration || recorder.currentTime >= recorder.duration) {
      previewDone();
      return;
    }

    context.drawImage(source, 0, 0, canvas.width, canvas.height);

    // also update time counters.
    playerTimeCounter.innerHTML = secondsToTimeCode(player.currentTime);
    recorderTimeCounter.innerHTML = secondsToTimeCode(recorder.currentTime);
  };

  var previewDone = function() {
    clearInterval(previewInterval);
    player.pause();
    recorder.pause();
    previewing = false;
    canvas.style.display = "none";

    btnPreview.disabled = false;
    player.style.display = null;
    player.style.filter = null;
    player.muted = false;
    recorder.style.filter = null;
    recorder.style.display = null;
    recorder.muted = false;

  };

  var btnPreview = document.getElementById("preview");
  btnPreview.addEventListener("click", function() {
    previewing = true;
    btnPreview.disabled = true;

    player.muted = true;
    recorder.muted = false;
    player.style.filter = "brightness(0)";
    recorder.style.filter = "brightness(0)";

    // cue up at -5 seconds
    setTimeout(function() {
      player.currentTime = timeCodeToSeconds(playerMarkInValue.value) - 5

      if (editInsert.checked) {
        recorder.currentTime = timeCodeToSeconds(recorderMarkInValue.value) - 5;
      }
      else if (editAssemble.checked) {
        recorder.currentTime = recorder.duration - 5;
      }
      else if (editClip.checked) {
        player.currentTime = timeCodeToSeconds(playerMarkInValue.value);
      }

      // set up preview canvas
      // https://stackoverflow.com/questions/24496605/how-can-i-show-the-same-html-5-video-twice-on-a-website-without-loading-it-twice
      canvas = document.getElementById("canvas-recorder");
      context = canvas.getContext('2d');
      canvas.width = recorder.offsetWidth;
      canvas.height = recorder.offsetHeight;

      if (editInsert.checked) {
        previewInterval = setInterval(insertPreview, 20);
      }
      else if (editAssemble.checked) {
        previewInterval = setInterval(assemblePreview, 20);
      }
      else if (editClip.checked) {
        previewInterval = setInterval(clipPreview, 20);
      }
      else if (editDub.checked) {
        previewInterval = setInterval(dubPreview, 20);
      }
      else {
        console.log("Invalid edit type");
        return;
      }

      player.play();
      recorder.play();

      player.style.filter = null;
      recorder.style.filter = null;
      recorder.style.display = "none";
      canvas.style.display = null;
    }, 1000);
    
  });

  // timeupdate fires every ~200ms so add interval for faster time updates
  var playInterval;
  player.addEventListener("play", function() {
    if (previewing == true) {
      return;
    }
    playInterval = setInterval(function() {
      playerTimeCounter.innerHTML = secondsToTimeCode(player.currentTime);
    }, 20);
  });

  player.addEventListener("pause", function() {
    clearInterval(playInterval);
  });

  var recInterval;
  recorder.addEventListener("play", function() {
    if (previewing == true) {
      return;
    }
    recInterval = setInterval(function() {
      recorderTimeCounter.innerHTML = secondsToTimeCode(recorder.currentTime);
    }, 20);
  });

  recorder.addEventListener("pause", function() {
    clearInterval(recInterval);
  });

  var btnStop = document.getElementById("stop");
  btnStop.addEventListener("click", function() {
    previewDone();
  });

  var btnRecord = document.getElementById("record");
  btnRecord.addEventListener("click", function() {
    // collection, player and recorder
    // along with set mark ins and outs
    // and edit type
    // are submitted with form
  });

  var editForm = document.getElementById("video-editor-form");
  editForm.addEventListener("submit", function(e) {
    e.preventDefault();
    if (playerMarkInValue.value && playerMarkOutValue.value && (recorderMarkInValue.value || recorderMarkOutValue.value)) {
      this.submit();
      return true;
    }
    else if ((playerMarkInValue.value || playerMarkOutValue.value) && recorderMarkInValue.value && recorderMarkOutValue.value) {
      this.submit();
      return true;
    }
    else {
      console.log("Invalid mark ins/outs");
      return false;
    }
  });

});
