var player;
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
	window.location.search = urlParams.toString();
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
