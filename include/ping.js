/**
 * Javascript for import status ping functionality.
 */

var div;

function loadPing(url) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        var msg = document.getElementById('imports');

        try {
          var data = JSON.parse(this.responseText);
        }
        catch (e) {
          msg.innerHTML = this.responseText;
          console.log(e);
          return;
        }

        msg.innerHTML = '';
        data.forEach(function(link, index) {
          // true means clone all childNodes and all event handlers
          var clone = div.cloneNode(true);
          clone.id = index;
          clone.hidden = false;
          var status = link.status;
          clone.classList.add(status);
          clone.querySelector('.status-text').innerHTML = status;
          var timeDiff = Date.now() - link.timestamp * 1000;
          var timestamp = millisecondsToStr(timeDiff);
          clone.querySelector('.timestamp').innerHTML = timestamp;
          var title = link.title ? link.title : link.url;
          clone.querySelector('.title').innerHTML = title;
          var target = link.target ? link.target : '';
          if (target) {
            var targetLink = clone.querySelector('.target a');
            targetLink.hidden = false;
            targetLink.href = target;
          }

          // Update progress bar based on timestamp.
          var progress = clone.querySelector('.progress');
          var width = 0;
          switch (link.status) {
            case 'downloading':
              width = 3;
              break;
            case 'processing':
              width = 33;
              break;
            case 'refreshing':
              width = 75;
              break;
          }
          width += (timeDiff / 1000) / 60;
          progress.style.width = width + '%';

          msg.appendChild(clone);
        });

        if (data.length == 0) {
          msg.innerHTML = 'No active links';
        }
      }
    };
    xhttp.open("GET", url, true);
    xhttp.send();
}

var timeOut;
var initialDelay = 2000;
var delay = 5000;
var url = "/vplaylist/ping.php";

timeOut = function() {
 loadPing(url);
 setTimeout(timeOut,
   delay);
}

window.addEventListener("load", function() {
  div = document.getElementById('index');
  setTimeout(timeOut,
    initialDelay);
});
