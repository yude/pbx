# pbx

## Setup

### Dialplans

#### Alarm

* Add line to `/var/spool/cron/crontabs/root`

```
* * * * *  /bin/bash /root/application/timerCron.sh > /root/application/log 2>&1
```

* Add script to `/etc/asterisk/extensions.conf`

```
#!/bin/bash 

configPath="$1" # Path to the original config file

# Configuration to append
appConfig='
[applications]
exten => _9.,1,Answer()
    same => n,AGI(cdr_connector.php,${ISTRANSFER}dial_answer)
    same => n,AGI(address_get.php)
    same => n,AGI(timerCall.sh,${RESULT},${EXTEN:1})
    same => n,Playback(beep)
    same => n,Playback(silence/1)
    same => n,SayDigits(${CALLERID(number)})
    same => n,Playback(silence/1)
    ; 2-3桁目を抽出して発声
    same => n,Set(PART1=${EXTEN:1:2})
    same => n,SayNumber(${PART1})
    same => n,Playback(digits/oclock)
    ; 4-5桁目を抽出して発声
    same => n,Set(PART2=${EXTEN:3:2})
    same => n,SayNumber(${PART2})
    same => n,Playback(minutes)
    same => n,Playback(is-set-to)
    same => n,Playback(silence/1)
    same => n,Hangup()

'

# Append the configuration to the file
echo "$appConfig" >> "$configPath"

awk '{
    print $0;
    # 特定の行を検出し、新しいロジックを追記
    if ($0 ~ /^\s*same => n,Set\(__M_CALLID=\$\{CHANNEL\(callid\)\}\)/) {
        print "    same => n,Set(__FROM_HEADER=${PJSIP_HEADER(read,Via)})";
        print "    same => n,Set(TEMP=${CUT(FROM_HEADER,=,3)})";
        print "    same => n,Set(__IP_ADDRESS=${FILTER(0-9.,${TEMP})})";
    }
}' "$configPath" > /tmp/config.tmp && mv /tmp/config.tmp "$configPath"
```

## License

MIT
