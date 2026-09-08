#!/bin/bash
# based on https://gist.github.com/yellowled/1439610
IN=$1
OUT=../mp4/$(echo $1 | sed 's/^\(.*\)\.[a-zA-Z0-9]*$/\1/')

# webm
#ffmpeg -i "$IN" -f webm -vcodec libvpx -acodec libvorbis -ab 128000 -crf 22 -s 640x360 "$OUT.webm"

# mp4
#ffmpeg -i "$IN" -acodec aac -strict experimental -ac 2 -ab 128k -vcodec libx264 -preset slow -f mp4 -crf 22 -s 640x360 "$OUT.mp4"

# mp4 plays in chrome
#ffmpeg -i "$IN" -n -acodec aac -b:a 400k -vcodec libx264 -preset medium -f mp4 -crf 18 -pix_fmt yuv420p -qcomp 0.8 -s 640x360 -x264-params ref=4 -profile:v baseline -level 3.1 -movflags +faststart "$OUT.mp4"

# ogg (if you want to support older Firefox)
#ffmpeg2theora $IN -o $OUT.ogv -x 640 -y 360 --videoquality 5 --audioquality 0  --frontend

# mp4 with aspect ratio check
WIDTH=$(ffprobe -loglevel error -select_streams v:0 -show_entries stream=width -of csv=s=,:p=0 "$IN")
HEIGHT=$(ffprobe -loglevel error -select_streams v:0 -show_entries stream=height -of csv=s=,:p=0 "$IN")

if [ $WIDTH -lt $HEIGHT ]; then
  # vertical
  echo "Vertical"

  # Calculate final dimensions
  #
  # 1620    x
  # ---- = ---
  # 1080   360
  #
  DEST_WIDTH=360
  DEST_HEIGHT=$(($HEIGHT * $DEST_WIDTH / $WIDTH))
  if (( $DEST_HEIGHT % 2 == 0 )); then
    echo "Height is even"
  else
    echo "Height is odd"
    DEST_WIDTH=$(($DEST_WIDTH+1))
  fi
  ffmpeg -progress - -nostats -loglevel quiet -i "$IN" -n -acodec aac -b:a 400k -vcodec libx264 -preset medium -f mp4 -crf 18 -pix_fmt yuv420p -qcomp 0.8 -s ${DEST_WIDTH}x${DEST_HEIGHT} -x264-params ref=4 -profile:v baseline -level 3.1 -movflags +faststart "$OUT.mp4"

else
  # horizontal
  echo "Horizontal"
  # Calculate final dimensions
  #
  # 1620    x
  # ---- = ---
  # 1080   360
  #
  DEST_HEIGHT=360
  DEST_WIDTH=$(($WIDTH * $DEST_HEIGHT / $HEIGHT))
  if (( $DEST_WIDTH % 2 == 0 )); then
    echo "Width is even"
  else
    echo "Width is odd"
    DEST_WIDTH=$(($DEST_WIDTH+1))
  fi
  ffmpeg -progress - -nostats -loglevel quiet -i "$IN" -n -acodec aac -b:a 400k -vcodec libx264 -preset medium -f mp4 -crf 18 -pix_fmt yuv420p -qcomp 0.8 -vf scale=${DEST_WIDTH}:${DEST_HEIGHT}:force_divisible_by=2 -x264-params ref=4 -profile:v baseline -level 3.1 -movflags +faststart "$OUT.mp4"
fi
