vplaylist is an HTML5 video player for desktop and mobile for personal use

"Nosotros te vemos"

## Project requirements
- Simple, lightweight interface
- Convert all videos into web playable format
- Customizable playback options
- Stream media from outside docroot
- No database needed

<<Screenshots>>

## Features

* Fullscreen autoplay mode in shuffle and sequential playback
* Keep the user's volume setting on each video
* Import status page

Visit the online demo: https://dysproseum.com/vplaylist/

## Server options
- Docker
- PHP/Apache

## Installation

Clone the repository:
````
$ git clone git@github.com:dysproseum/vplaylist.git
$ cd vplaylist
$ cp config.php.example config.php
````
Create a media storage directory and set `video_dir` config value to point here.

````
$conf['video_dir'] = '/var/www/vplaylist';
````

Copy the media processing scripts from the webroot:

````
$ cp scripts/* /var/www/vplaylist/video-editor/
````

Generate/update collections

````
$ php update.php create "New Vids"

2024-09-01 11:52pm  Creating New Vids as new_vids...
2024-09-01 11:52pm  Directory /var/www/html/dysproseum.com/vplaylist/collections already exists.
2024-09-01 11:52pm  Created /var/www/html/dysproseum.com/vplaylist/collections/new_vids.json
2024-09-01 11:52pm  Directory /mnt/video/vplaylist/vplaylist_mp4/new_vids created.
2024-09-01 11:52pm  Done.
````
Add content
- Use the import function under `/vplaylist/video-editor`
- Manually add files to the download folder
- Run cron

Generate/update thumbnails

````
$ php generate.php new_vids
````

## Import cron service

````
# m h  dom mon dow   command
*/5 *  *   *   *     /usr/bin/php /path/to/vplaylist/video-editor/run.php 2>&1
````


## Initial planning

1. Listing.txt?
- Need path, and run find

2. Thumbnails?
- Generate thumbnails

3. Settings json?

- Generate settings json
  - create index number
  - path to video
  - file size
  - length
  - path to thumbnail

4. Listing screen
- with search functionality
5. Playing screen
