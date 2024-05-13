const events = 'abort canplay canplaythrough durationchange emptied encrypted ended error interruptbegin interruptend loadeddata loadedmetadata loadstart mozaudioavailable pause play playing progress ratechange seeked seeking stalled suspend timeupdate volumechange waiting';
var player;

function addListenerMulti(el, s, fn) {
    s.split(' ').forEach(e => el.addEventListener(e, fn, false));
}

function loadDoc(url) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        var data = JSON.parse(this.responseText);
	  var vidSrc = '/vplaylist/serve.php?filename=' + data.base64 + '&file=.mp4';
	  player.src = vidSrc;
          var label = document.querySelector("#vid_title");
          label.innerText = data.filename;
      }
    };
    xhttp.open("GET", url, true);
    xhttp.send();
    return true;
}

function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

function prepareVidLinks() {
	// @todo if already on player page class.

	var vids = document.querySelectorAll(".vid-link"), i;
	for (i = 0; i < vids.length; i++) {
		vids[i].addEventListener("click", function(e) {
			var id = this.getAttribute('data-id');
			// var collection = this.getAttribute('data-collection');
			e.preventDefault();
			// loadDoc
			// scroll to top
		});
	}
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
		return;
	}
	else {
		urlParams.set('autoplay', 1);
		mod_index++;
	}

	var shuffle = document.querySelector('input[name=vid_shuffle]');
	if (shuffle.checked == false) {
		urlParams.set('shuffle', 0);
	}
	else {
		urlParams.set('shuffle', 1);
		mod_index = Math.floor(Math.random() * count.value);
	}

	var repeat = document.querySelector('input[name=vid_repeat]');
	if (mod_index >= count.value) {
		if (repeat.checked == true) {
			urlParams.set('repeat', 1);
			mod_index = 0;
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
	var stateObj = '';
	var newUrl = "/vplaylist/index.php?" + urlParams.toString();
        window.history.pushState(stateObj, "vplaylist", newUrl);

	const collection = urlParams.get('collection');
	var ajaxUrl = '/vplaylist/getnextvideo.php?collection=' + collection + '&index=' + mod_index;
	loadDoc(ajaxUrl);
};

videoVolumeListener = function(event) {
	var volume = player.volume;
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
	var muted = getCookie("muted");
	if (muted == 1) {
		player.muted = true;
	}
	else {
		player.muted = false;
	}
};

addListener = function() {
	player = document.querySelector('video');

	if (player) {
		player.addEventListener('playing', videoPlayingListener);
		player.addEventListener('ended', videoEndedListener);
		player.addEventListener('volumechange', videoVolumeListener);

		addListenerMulti(player, events, function(e){
			if (e.type != 'timeupdate') {
				console.log(e.type);
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

window.onload = function(){
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
};
