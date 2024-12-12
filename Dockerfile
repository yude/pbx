#FROM ubuntu:jammy AS builder
#
#RUN ln -sf /usr/share/zoneinfo/Asia/Tokyo /etc/localtime
#RUN apt update; apt -y install git build-essential asterisk-dev
#
#WORKDIR /work
#RUN git clone https://github.com/nadirhamid/asterisk-audiofork audiofork
#RUN cd audiofork; make

FROM ghcr.io/mikopbx/mikopbx-x86-64 AS runner

#WORKDIR /work
#COPY --from=builder /work/audiofork/app_audiofork.so /offload/asterisk/modules/

COPY ./app/timerCall.sh /var/lib/asterisk/agi-bin/
COPY ./app/timerCron.sh /var/lib/asterisk/agi-bin/
COPY ./app/address_get.php /var/lib/asterisk/agi-bin/

RUN mkdir -p /root/application/call

WORKDIR /work
RUN curl -o asterisk-core-sounds-ja-gsm-current.tar.gz https://downloads.asterisk.org/pub/telephony/sounds/asterisk-core-sounds-ja-gsm-c>
WORKDIR /work/ja-jp
RUN tar xf ../asterisk-core-sounds-ja-gsm-current.tar.gz
RUN rm -rf /offload/asterisk/sounds/ja-jp
RUN mv -f /work/ja-jp /offload/asterisk/sounds/
RUN rm -rf /work/asterisk-core-sounds-ja-gsm-current.tar.gz
