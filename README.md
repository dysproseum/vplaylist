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

#### Clone the repository:

````
$ git clone git@github.com:dysproseum/vplaylist.git
$ cd vplaylist
$ cp config.php.example config.php
````

#### Create a media storage directory

Set `video_dir` config value to point to a writable storage location:

````
$conf['video_dir'] = '/var/www/vplaylist';
````


#### Generate collections

````
$ php update.php create "New Vids"

2024-09-01 11:52pm  Creating New Vids as new_vids...
2024-09-01 11:52pm  Directory /path/to/vplaylist/collections already exists.
2024-09-01 11:52pm  Created /path/to/vplaylist/collections/new_vids.json
2024-09-01 11:52pm  Directory /var/www/vplaylist_mp4/new_vids created.
2024-09-01 11:52pm  Done.
````

Copy the media processing scripts from the webroot:

````
$ cp scripts/* /var/www/vplaylist/video-editor/
````

#### Add content

Configure the import cron service:

````
# m h  dom mon dow   command
*/5 *  *   *   *     /usr/bin/php /path/to/vplaylist/video-editor/run.php 2>&1
````

Use the import function under `/vplaylist/video-editor`

Monitor the log output for any errors.

#### Generate thumbnails

````
$ php generate.php new_vids
````

---

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
