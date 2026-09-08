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

## Prerequisites

#### Check ffmpeg version

Versions before 8.0 are tested and working:

````
ffmpeg version 4.2.7-0ubuntu0.1 Copyright (c) 2000-2022 the FFmpeg developers
  built with gcc 9 (Ubuntu 9.4.0-1ubuntu1~20.04.1)
````

However, version 8.0 has a regression that breaks processing - [fix committed here](https://github.com/FFmpeg/FFmpeg/commit/618fc15e65f57c9ce25d4562f4b516129815608c).

````
ffmpeg version 8.0.1-3ubuntu2 Copyright (c) 2000-2025 the FFmpeg developers
  built with gcc 15 (Ubuntu 15.2.0-13ubuntu3)
````

There are 8.1, 8.2, and 9.0 tags now, but until there is a newer tag available in distro repositories, you will need to compile the latest source with the fix included.

#### Compile ffmpeg from source

To work around this, you will need to remove the distro-installed ffmpeg and compile from source.
````
$ sudo apt remove ffmpeg
$ sudo apt install nasm pkg-config libdav1d-dev libsvtav1enc-dev libx264-dev libx265-dev

$ git clone https://github.com/FFmpeg/FFmpeg.git
$ cd FFmpeg
$ ./configure --enable-libdav1d --enable-libsvtav1 --enable-libx264 --enable-libx265 --enable-gpl
$ make
$ sudo make install
````

## Installation

#### Clone the repository:

````
$ git clone git@github.com:dysproseum/vplaylist.git
$ cd vplaylist
$ cp config.php.example config.php
````

#### Set media storage directory

Update `config.php` to point to a writable storage location:

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

## Requirements

1. Keyboard input must bubble to the video

2. Time progress must be shown without user input, without blocking the video
