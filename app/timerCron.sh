#!/bin/bash

#
# timerCall.sh
# Originally made by 108jiminy https://github.com/108jiminy/
# Licensed under the MIT license

call_dir="/root/application/call/"
outgoing_dir="/storage/usbdisk1/mikopbx/astspool/outgoing/"

current_time=$(date +%s)

for file in "$call_dir"*; do
    file_time_str=$(basename "$file" | cut -d'+' -f2)
    file_time=$(date -d "$file_time_str" +%s 2>/dev/null)
    if [ "$file_time" -le "$current_time" ]; then
      echo "Moving file: $file (time: $file_time_str)"
      mv "$file" "$outgoing_dir"
    else
      echo "Skipping file: $file (time: $file_time_str)"
    fi
done

exit 0
