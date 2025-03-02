#!/bin/sh

/usr/bin/yt-dlp -f bestaudio -o - "https://youtu.be/2ZGC_V4PrVE" | /usr/bin/ffmpeg -i pipe:0 -af "volume=-3dB" -acodec g722 -ac 1 -ar 16000 -f g722 -
