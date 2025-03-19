const debug = false;
const events = 'abort canplay canplaythrough durationchange emptied encrypted ended error interruptbegin interruptend loadeddata loadedmetadata loadstart mozaudioavailable pause play playing progress ratechange seeked seeking stalled suspend timeupdate volumechange waiting';
var player;
var state;
var counter;
var framerate = 0;

var volslider;
var volvalue;

function addListenerMulti(el, s, fn) {
    s.split(' ').forEach(e => el.addEventListener(e, fn, false));
}

videoEndedListener = function () {
	var url = window.location.href;
	var urlParams = new URLSearchParams(window.location.search);
	const imm_index = urlParams.get('index');
	const count = document.querySelector('input[name=vid_count]');
	var mod_index = imm_index;

	var loop = document.querySelector('input[name=vid_loop]');
	if (loop.checked == true) {
		return;
	}

	var autoplay = document.querySelector('input[name=vid_autoplay]');
	if (autoplay.checked == false) {
		var play = document.querySelector("#player-play");
		play.classList.remove("pressed");
		return;
	}
	else {
		urlParams.set('autoplay', 1);
		if (use_ajax == false) {
			mod_index++;
		}
	}

	var shuffle = document.querySelector('input[name=vid_shuffle]');
	if (shuffle.checked == false) {
		urlParams.set('shuffle', 0);
	}
	else {
		urlParams.set('shuffle', 1);
		if (use_ajax == false) {
			mod_index = Math.floor(Math.random() * count.value);
		}
	}

	var repeat = document.querySelector('input[name=vid_repeat]');
	if (parseInt(mod_index) >= parseInt(count.value)) {
		if (repeat.checked == true) {
			urlParams.set('repeat', 1);
			if (use_ajax == false) {
				mod_index = 0;
			}
		}
		else {
			urlParams.set('repeat', 0);
			return;
		}
	}

	var muted = document.querySelector('input[name=vid_muted]');
	if (muted.checked == true) {
		urlParams.set('muted', 1);
	}
	else {
		urlParams.set('muted', 0);
	}

	urlParams.set('index', mod_index);
	if (use_ajax == false) {
		window.location.search = urlParams.toString();
        }

	// Load next video via ajax.
	getNextVideo();
};

getNextVideo = function() {
	var urlParams = new URLSearchParams(window.location.search);
	const mod_index = urlParams.get('index');
	var shuffle = document.querySelector('input[name=vid_shuffle]');
	var repeat = document.querySelector('input[name=vid_repeat]');

	var stateObj = '';
	var newUrl = "/vplaylist/index.php?" + urlParams.toString();
        window.history.pushState(stateObj, "vplaylist", newUrl);

	const collection = urlParams.get('collection');
	var ajaxUrl = '/vplaylist/getnextvideo.php?collection=' + collection + '&index=' + mod_index;
	if (shuffle.checked == true) {
		ajaxUrl += '&shuffle=1';
	}
	if (repeat.checked == true) {
		ajaxUrl += '&repeat=1';
	}
	loadDoc(ajaxUrl);

}

videoVolumeListener = function(event) {
	var volume = player.volume;
	// var volslider = document.getElementById("volslider");
	// var volvalue = document.getElementById("volvalue");
	volslider.value = volume * 100;
        const sliderValue = volslider.value;
	volvalue.innerText = Math.round(volume * 100);
	volslider.style.background = `linear-gradient(to right, #f50 ${sliderValue}%, #ccc ${sliderValue}%)`;

	var muted = document.querySelector('input[name=vid_muted]');
	if (player.muted == true) {
		muted.checked = true;
		document.cookie = "muted=1";
	}
	else {
		muted.checked = false;
		document.cookie = "muted=0";
		document.cookie = "volume=" + volume;
	}
};

videoPlayingListener = function() {
	// Set persistent volume if cookie exists.
	var volume = getCookie("volume");
	player.volume = volume;
        volslider.value = volume * 100;
	var muted = getCookie("muted");
	if (muted == 1) {
		player.muted = true;
	}
	else {
		player.muted = false;
	}
	var play = document.querySelector("#player-play");
	play.classList.add("pressed");

	var vidspeed = document.getElementById("vidspeed");
	player.playbackRate = vidspeed.value;

        InitPanner()
          .then(InitEqualizer());
};

addListener = function() {
	player = document.querySelector('video');
	state = document.getElementById("player-status");
	counter = document.getElementById("player-time");

	if (player) {
		player.addEventListener('playing', videoPlayingListener);
		player.addEventListener('ended', videoEndedListener);
		player.addEventListener('volumechange', videoVolumeListener);

		addListenerMulti(player, events, function(e) {
			if (e.type == 'timeupdate') {
					var q = player.getVideoPlaybackQuality();
					var dropped = q.droppedVideoFrames;
					var total = q.totalVideoFrames;
					var pcnt = (dropped / total * 100).toFixed(2);
					var frames = framerate;
					if (!Number.isInteger(framerate)) {
						frames = frames.toFixed(2);
					}
					frames += "fps";
					state.hidden = false;
					state.innerHTML = frames + " tracking " + pcnt + "%<br>" + dropped + "/" + total;
				var current = counter.innerHTML;
				var update = secondsToClockTime(player.currentTime) + " ";
					counter.innerHTML = update;
				if (current != update) {

					if (debug) {
						console.log("Dropped " + dropped + ", " + pcnt + "%");
					}
				}
			}
			else {
				if (debug) {
					console.log(e.type);
				}

                                var y = document.createElement('span');
                                //y.innerHTML = " " + e.type;
				state.append(y);

                                setTimeout(function() {
				  //x.hidden = true;
                                  //state.removeChild(y);
				}, 2500);

			}
		});

		return true;
	}
	else {
		return false;
	}
}

function waitForVideo() {
	var result = addListener();
	if (!result) {
		setTimeout(function() {
			waitForVideo();
		}, 5000);
	}
}

function InitPanner() {
  return new Promise((resolve, reject) => {
    if (context) {
      return;
    }
    // for cross browser
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    const audioCtx = new AudioContext();
    context = new AudioContext();
    
    // load some sound
    const audioElement = document.querySelector('audio');
    //const track = context.createMediaElementSource(audioElement);
    source = context.createMediaElementSource(player);
    
    // panning
    const pannerOptions = {pan: 0};
    const panner = new StereoPannerNode(context, pannerOptions);
    
    const pannerControl = document.getElementById("panner");
    pannerControl.addEventListener('input', function() {
    	panner.pan.value = this.value;
    }, false);
    
    // volume
    //const gainNode = context.createGain();
    
    // connect our graph
    //track.connect(gainNode).connect(panner).connect(audioCtx.destination);
    source.connect(panner).connect(context.destination);

    resolve();
  });
}

window.onload = function(){

	// Set persistent volume if cookie exists.
	volslider = document.getElementById("volslider");
	volvalue = document.getElementById("volvalue");
	var volume = getCookie("volume");
	// player.volume = volume;
        volslider.value = volume * 100;
	volvalue.innerText = Math.round(volume * 100);

	waitForVideo();

	// Define onclick functions.
	var mute_button = document.querySelector('input[name=vid_muted]');
	if (mute_button == null) {
		return;
	}
	mute_button.onclick = function(obj) {
		var video = document.querySelector("video");
		video.muted = obj.srcElement.checked ? true : false;
	};

	var loop_button = document.querySelector('input[name=vid_loop]');
	loop_button.onclick = function(obj) {
		var video = document.querySelector("video");
		video.loop = obj.srcElement.checked ? true : false;
	};

	var shuffle = document.querySelector('input[name=vid_shuffle]');
	shuffle.onclick = function(obj) {
		var autoplay = document.querySelector("input[name=vid_autoplay]");
		if (obj.srcElement.checked) {
			autoplay.checked = true;
		}
	};

	var rec = document.querySelector("#player-rec");
	var play = document.querySelector("#player-play");
	var stop = document.querySelector("#player-stop");
	var prev = document.querySelector("#player-prev");
	var next = document.querySelector("#player-next");
	var pause = document.querySelector("#player-pause");

	rec.addEventListener("mousedown", function() {
		if (play.classList.contains("pressed")) {
			return false;
		}
		if (pause.classList.contains("pressed")) {
			return false;
		}
		window.location.href="video-editor/index.php";
	});

	play.addEventListener("mousedown", function() {
		this.classList.add("pressed");
		if (!pause.classList.contains("pressed")) {
			player.play();
		}
	});

	stop.addEventListener("mousedown", function() {
		player.pause();
		player.currentTime = 0;
		play.classList.remove("pressed");
		this.classList.add("pressed");
		setTimeout(function() {
			stop.classList.remove("pressed");
		}, 500);
	});

	prev.addEventListener("mousedown", function() {
		if (play.classList.contains("pressed")) {
			this.classList.toggle("pressed");
			if (this.classList.contains("pressed")) {
				// setInterval
			}
			else {
				// clearInterval
			}
		}
	});

	next.addEventListener("mousedown", function() {
		if (play.classList.contains("pressed")) {
			this.classList.toggle("pressed");
			if (this.classList.contains("pressed")) {
				// setInterval
			}
			else {
				// clearInterval
			}
		}
		else {
			getNextVideo();
		}
	});

	pause.addEventListener("mousedown", function(e) {
		// no right click.
		if (e.button == 2) {
			return false;
		}
		this.classList.toggle("pressed");
		if (this.classList.contains("pressed")) {
			player.pause();
		}
		else if (play.classList.contains("pressed")) {
			player.play();
		}
	});

	var body = document.querySelector("body");
	body.addEventListener("keydown", function(e) {
		if (e.target.nodeName == "INPUT" && e.target.type == "text") {
			console.log(e.srcElement);
			return false;
		}
		if (debug) {
			console.log(e);
		}

		var oneFrame = 0.06;
		if (framerate > 0) {
			oneFrame = 1 / framerate;
		}

		switch (e.code) {
		  case "Space":
			pause.classList.toggle("pressed");
			if (pause.classList.contains("pressed")) {
				player.pause();
			}
			else if (play.classList.contains("pressed")) {
				player.play();
			}
			e.preventDefault();
			break;

		  case "KeyM":
			player.muted = !player.muted;
			e.preventDefault();
			break;

		  case "ArrowUp":
			player.volume += 0.01;
			e.preventDefault();
			break;

		  case "ArrowDown":
			player.volume -= 0.01;
			e.preventDefault();
			break;

		  case "ArrowLeft":
			player.currentTime -= 5;
			e.preventDefault();
			break;

		  case "ArrowRight":
			player.currentTime += 5;
			e.preventDefault();
			break;

		  case "Comma":
			if (player.paused) {
				player.currentTime -= oneFrame;
			}
			e.preventDefault();
			break;

		  case "Period":
			if (player.paused) {
				player.currentTime += oneFrame;
			}
			e.preventDefault();
			break;
		}
	});

	var canvas = document.getElementById("canvas");
	canvas.addEventListener("click", function() {
		InitEqualizer();
	});

	volslider.addEventListener("input", function(e) {
		volume = this.value / 100;
		player.volume = volume;
	});

	var pan = document.getElementById("panner");
	pan.addEventListener("click", function(e) {
		InitPanner();
	});

	var vidspeed = document.getElementById("vidspeed");
	vidspeed.addEventListener("input", function(e) {
		player.playbackRate = this.value;
	});
};
