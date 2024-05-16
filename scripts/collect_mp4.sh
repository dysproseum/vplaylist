#!/bin/bash

for i in *.*
do
	echo "$i"
	../convert_mp4_html5.sh "$i"
done
