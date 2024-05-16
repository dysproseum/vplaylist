#!/bin/bash
# based on https://gist.github.com/yellowled/1439610
IN=$1
OUT=mp4/$(echo $1 | sed 's/^\(.*\)\.[a-zA-Z0-9]*$/\1/')

# webm
#ffmpeg -i "$IN" -f webm -vcodec libvpx -acodec libvorbis -ab 128000 -crf 22 -s 640x360 "$OUT.webm"

# mp4
#ffmpeg -i "$IN" -acodec aac -strict experimental -ac 2 -ab 128k -vcodec libx264 -preset slow -f mp4 -crf 22 -s 640x360 "$OUT.mp4"

# mp4 plays in chrome
ffmpeg -i "$IN" -n -acodec aac -b:a 400k -vcodec libx264 -preset medium -f mp4 -crf 18 -pix_fmt yuv420p -qcomp 0.8 -s 640x360 -x264-params ref=4 -profile:v baseline -level 3.1 -movflags +faststart "$OUT.mp4"

# ogg (if you want to support older Firefox)
#ffmpeg2theora $IN -o $OUT.ogv -x 640 -y 360 --videoquality 5 --audioquality 0  --frontend
