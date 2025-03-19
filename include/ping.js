/**
 * Javascript for import status ping functionality.
 */

var div;

function loadPing(url) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        var msg = document.getElementById('imports');
        var data = [];
        try {
          data = JSON.parse(this.responseText);
        }
        catch (e) {
          console.log(new Date() + " " + e);
          data = [];
        }

        msg.innerHTML = '';

        // @todo only replace if different?
        var active = false;
        data.forEach(function(link, index) {

          // Throttle requests based on activity.
          if (link.status != 'completed' && link.status != 'error') {
            active = true;
          }

          // true means clone all childNodes and all event handlers
          var clone = div.cloneNode(true);
          clone.id = index;
          clone.hidden = false;
          var status = link.status;
          clone.classList.add(status);
          clone.querySelector('.status-text').innerHTML = status;
          var title = link.title ? link.title : link.url;
          clone.querySelector('.title').innerHTML = title;
          var duration = link.display_duration ? "(" + link.display_duration + ")" : '';
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
          var timeDiff = epochTime() - link.timestamp;
          var progress = clone.querySelector('.progress');
          var width = 0;
          var output = humanReadableTime(timeDiff) + " elapsed ";

          switch (link.status) {
            case 'queued':
              break;
            case 'downloading':
              // Count up from timestamp.
              timeDiff = epochTime() - link.time_downloading;
              // Function that approaches 100; y(x)=100(1−e^(−bx)).
              width = 50 * (1 - Math.exp((0.01 * Math.log(0.2)) * timeDiff / 2));
              break;
            case 'processing':
            case 'refreshing':
              // Count down remaining time.
              timeDiff = epochTime() - link.time_processing;
              var collection_count = 200;
              var processing_factor = 4;
              var total_estimate = parseInt(link.duration) / processing_factor + collection_count;
              width = 50 + (timeDiff / total_estimate) * 50;
              var left = total_estimate - timeDiff;
              if (left < 0) {
                output += humanReadableTime(Math.abs(left)) + " past estimate";
              }
              else {
                output += humanReadableTime(left) + " remaining";
              }
              break;
            case 'completed':
              width = 100;
              var total = link.time_completed - link.timestamp;
              output = humanReadableTime(total) + " total";
          }

          progress.style.width = width + '%';
          progress.title = width + '%';
          clone.querySelector('.timestamp').innerHTML = output;

          if (link.status == 'error' && link.error) {
            var errorMsg = link.error;
            timeDiff = epochTime() - link.time_error;
            var timeAgo = humanReadableTime(timeDiff) + " ago";
            clone.querySelector('.timestamp').innerHTML = timeAgo + " " + errorMsg;
          }

          msg.appendChild(clone);
        });

        if (data.length == 0) {
          msg.innerHTML = '<div class="item">No active links</div>';
        }

        if (active) {
          setTimeout(timeOut, activeDelay);
        }
        else {
          setTimeout(timeOut, normalDelay);
        }
      }
    };
    xhttp.open("GET", url, true);
    xhttp.send();
}

var timeOut;
var initialDelay = 2000;
var normalDelay = 15000;
var activeDelay = 500;
var url = "/vplaylist/ping.php";

timeOut = function() {
 loadPing(url);
}

window.addEventListener("load", function() {
  div = document.getElementById('index');
  setTimeout(timeOut,
    initialDelay);
});
