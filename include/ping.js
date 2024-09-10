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

        // @todo only replace if different?
        data.forEach(function(link, index) {
          // true means clone all childNodes and all event handlers
          var clone = div.cloneNode(true);
          clone.id = index;
          clone.hidden = false;
          var status = link.status;
          clone.classList.add(status);
          clone.querySelector('.status-text').innerHTML = status;
          var title = link.title ? link.title : link.url;
          clone.querySelector('.title').innerHTML = title;
          var duration = link.duration ? millisecondsToStr(link.duration * 1000) : '';
          clone.querySelector('.duration').innerHTML = duration;
          var collection = link.collection ? link.collection : '';
          clone.querySelector('.collection').innerHTML = collection;

          // Target links.
          var target = link.target ? link.target : '';
          if (target) {
            var targetLinks = clone.querySelectorAll('.target');
            targetLinks.forEach(function(targetLink, index) {
              targetLink.hidden = false;
              targetLink.href = target;
            });
            var icon = clone.querySelector(".icon");
            icon.classList.add("icon-border");
          }

          // Thumbnail.
          if (link.index && link.status == "completed") {
            var uri = "/vplaylist/serve.php?collection=" + link.collection + "&index=" + link.index + "&file=.jpg";
            var thumbnail = clone.querySelector(".icon .thumb");
            thumbnail.src = uri;
          }

          // Update progress bar based on timestamp and duration.
          var timeDiff = Date.now() - link.timestamp * 1000;
          var td = timeDiff / 1000;
          var progress = clone.querySelector('.progress');
          var width = 0;
          var output = '';

          switch (link.status) {
            case 'queued':
            case 'downloading':
              // Count up from timestamp.
              output = millisecondsToStr(timeDiff) + " elapsed";
              width = td ;
              break;
            case 'processing':
            case 'refreshing':
              // Count down remaining time.
              width = td / link.duration * 100;
              var left = link.duration * 1000 - timeDiff;
              if (left < 0) {
                output = "almost... taking " + millisecondsToStr(Math.abs(left)) + " longer than estimated";
              }
              else {
                output = millisecondsToStr(left) + " remaining";
              }
              break;
            case 'completed':
              width = 100;
              var total = link.time_complete * 1000 - link.timestamp * 1000;
              output = millisecondsToStr(total) + " total";
          }

          progress.style.width = width + '%';
          clone.querySelector('.timestamp').innerHTML = output;

          msg.appendChild(clone);
        });

        if (data.length == 0) {
          msg.innerHTML = '<div class="item">No active links</div>';
        }
      }
    };
    xhttp.open("GET", url, true);
    xhttp.send();
}

var timeOut;
var initialDelay = 2000;
var delay = 45000;
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
