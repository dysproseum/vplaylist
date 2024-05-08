var player;
var ajax = true;

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

listener = function () {

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
	if (ajax == false) {
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

addListener = function() {
	player = document.querySelector('video');
	if (player) {
		player.addEventListener('ended', listener);
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
waitForVideo();

// Define onclick functions for mute and loop.
window.onload = function(){
	var mute_button = document.querySelector('input[name=vid_muted]');
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
