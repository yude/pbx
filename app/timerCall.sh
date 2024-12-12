#!/bin/bash

#
# timerCall.sh
# Originally made by 108jiminy https://github.com/108jiminy/
# Licensed under the MIT license

hhmm=$2

current_time=$(date +%s)
current_date=$(date +"%Y-%m-%d")
current_hhmm=$(date +"%H%M")

input_hour=${hhmm:0:2}
input_minute=${hhmm:2:2}

input_time=$(date -d "$current_date $input_hour:$input_minute" +%s 2>/dev/null)

if [ "$input_time" -le "$current_time" ]; then
  input_time=$((current_time + 86400)) # 86400秒 = 1日
  input_date=$(date -d "@$input_time" +"%Y-%m-%d")
  input_time=$(date -d "$input_date $input_hour:$input_minute" +%s)
fi

seconds=$((input_time - current_time))

future_time=$(date -d @"$input_time" +"%Y-%m-%d %H:%M:%S")
file_path="/root/application/call/$1+$future_time"

#seconds=$2
#future_time=$(date -d @"$(($(date +%s) + seconds))" +"%Y-%m-%d %H:%M:%S")
#file_path="/root/application/call/$1+$future_time"

touch "$file_path"
cat <<EOF > "$file_path"
Channel: PJSIP/$1
MaxRetries: 3
RetryTime: 60
WaitTime: 60
Context: applications
Extension: 9400
Priority: 2
EOF

exit 0
