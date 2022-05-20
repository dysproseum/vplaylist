var player;
listener = function () {


	var url = window.location.href;
	const urlParams = new URLSearchParams(window.location.search);
	var index = urlParams.get('index');

	var autoplay = document.querySelector('input[name=vid_autoplay]');
	if (autoplay.checked == false) {
		return;
	}
	else {
		urlParams.set('autoplay', 1);
	}

	var shuffle = document.querySelector('input[name=vid_shuffle]');
	if (shuffle.checked == false) {
		index++;
	}
	else {
		index = Math.floor(Math.random() * index);
		urlParams.set('shuffle', 1);
	}
	var repeat = document.querySelector('input[name=vid_repeat]');
	if (repeat.checked == true) {
		urlParams.set('repeat', 1);
	}

	urlParams.set('index', index);
	//urlParams.set('autoplay', 1);
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
