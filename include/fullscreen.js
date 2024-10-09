/* Get the documentElement (<html>) to display the page in fullscreen */
var elem = document.documentElement;

/* View in fullscreen */
function openFullscreen() {
  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) { /* Safari */
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) { /* IE11 */
    elem.msRequestFullscreen();
  }
}

/* Close fullscreen */
function closeFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.webkitExitFullscreen) { /* Safari */
    document.webkitExitFullscreen();
  } else if (document.msExitFullscreen) { /* IE11 */
    document.msExitFullscreen();
  }
}

var isFullscreen = false;
function toggleFullscreen() {
  var btn = document.getElementById('maximize');
  btn.onclick = function(){};

  if (isFullscreen == true) {
    closeFullscreen();
    btn.onclick=function() {toggleFullscreen()};
    isFullscreen = false;
  }
  else {
    openFullscreen();
    btn.onclick=function() {toggleFullscreen()};
    isFullscreen = true;
  }
  return true;
}
