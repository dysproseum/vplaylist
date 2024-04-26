vplaylist is an HTML5 video player for desktop and mobile for personal use

"Nosotros te vemos"

## Project requirements
- Simple, lightweight interface
- Convert all videos into web playable format
- Customizable playback options
- Stream media from outside docroot
- No database needed

## Generate/update collections
- Web Interface
- Command line

## Generate/update thumbnails
- Command line

## Server options
- Docker
- PHP/Apache

## Upload/cron service

````
# m h  dom mon dow   command
*/5 *  *   *   *     /usr/bin/php /path/to/vplaylist/video-editor/run.php >> /var/log/vplaylist-cron.txt
````

1. Listing.txt?
	a. Need path, and run find 
2. Thumbnails?
	a. Generate thumbnails
3. Settings json?
	a. Generate settings json
		- create index number
		- path to video
		- file size
		- length
		- path to thumbnail
4. Listing screen
	a. with search functionality
5. Playing screen


Things to add:
- Demo link?
- Screenshots?
