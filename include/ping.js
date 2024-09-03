/**
 * Javascript for import status ping functionality.
 */

var div;

function loadPing(url) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        var data = JSON.parse(this.responseText);

        var msg = document.getElementById('imports');
        msg.innerHTML = '';

        data.forEach(function(link, index) {
          // true means clone all childNodes and all event handlers
          var clone = div.cloneNode(true);
          clone.id = index;
          clone.hidden = false;
          var status = link.status;
          clone.querySelector('.status').innerHTML = status;
          var timestamp = millisecondsToStr(Date.now() - link.timestamp * 1000);
          clone.querySelector('.timestamp').innerHTML = timestamp;
          var title = link.title ? link.title : link.url;
          clone.querySelector('.title').innerHTML = title;

          msg.appendChild(clone);
        });

        if (data.length == 0) {
          msg.innerHTML = 'No active links';
        }
      }
    };
    xhttp.open("GET", url, true);
    xhttp.send();
    return true;
}

var timeOut;
var delay = 5000;
var url = "/vplaylist/ping.php";

timeOut = function() {
  if (loadPing(url)) {
    setTimeout(timeOut,
      delay);
  }
}

window.addEventListener("load", function() {
  div = document.getElementById('index');
  setTimeout(timeOut,
    delay);
});
