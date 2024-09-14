function loadDoc(url) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      var data = JSON.parse(this.responseText);
        var vidSrc = '/vplaylist/serve.php?collection=' + data.collection + '&index=' + data.index + '&file=.mp4';
        player.src = vidSrc;
        var label = document.querySelector("#vid_title");
        label.innerText = data.filename;
        document.title = data.filename + " | vplaylist";
        var urlParams = new URLSearchParams(window.location.search);
        if (data.index >= 0) {
          urlParams.set('index', data.index);
	  var newUrl = "/vplaylist/index.php?" + urlParams.toString();
          var stateObj = '';
          window.history.pushState(stateObj, "vplaylist", newUrl);
        }
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
  // @todo set url params on vid links
  
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

function millisecondsToStr(milliseconds) {
    // TIP: to find current time in milliseconds, use:
    // var  current_time_milliseconds = new Date().getTime();

    function numberEnding (number) {
        return (number > 1) ? 's' : '';
    }

    var temp = Math.floor(milliseconds / 1000);
    var years = Math.floor(temp / 31536000);
    if (years) {
        return years + ' year' + numberEnding(years);
    }
    //TODO: Months! Maybe weeks?
    var days = Math.floor((temp %= 31536000) / 86400);
    if (days) {
        return days + ' day' + numberEnding(days);
    }
    var hours = Math.floor((temp %= 86400) / 3600);
    if (hours) {
        return hours + ' hour' + numberEnding(hours);
    }
    var minutes = Math.floor((temp %= 3600) / 60);
    if (minutes) {
        return minutes + ' minute' + numberEnding(minutes);
    }
    var seconds = temp % 60;
    if (seconds) {
        return seconds + ' second' + numberEnding(seconds);
    }
    return 'less than a second'; //'just now' //or other string you like;
}
