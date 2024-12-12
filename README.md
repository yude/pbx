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
# Configuration to append
appConfig='
[applications]
exten => _98.,1,Answer()
    same => n,AGI(cdr_connector.php,${ISTRANSFER}dial_answer)
    same => n,AGI(address_get.php)
    same => n,AGI(/root/application/timerCall.sh,${RESULT},${EXTEN:2})
    same => n,Playback(beep)
    same => n,Playback(silence/1)
    same => n,SayDigits(${CALLERID(number)})
    same => n,Playback(silence/1)
    ; 2-3桁目を抽出して発声
    same => n,Set(PART1=${EXTEN:2:2})
    same => n,SayNumber(${PART1})
    same => n,Playback(digits/oclock)
    ; 4-5桁目を抽出して発声
    same => n,Set(PART2=${EXTEN:4:2})
    same => n,SayNumber(${PART2})
    same => n,Playback(minutes)
    same => n,Playback(is-set-to)
    same => n,Playback(silence/1)
    same => n,Hangup()

'

# Append the configuration to the file
echo "$appConfig" >> "$configPath"
```

## License

MIT
