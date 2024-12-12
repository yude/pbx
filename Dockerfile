FROM rust:bullseye AS my117-builder

WORKDIR /work
RUN apt update && apt -y upgrade
RUN git clone https://github.com/metastable-void/my117
WORKDIR /work/my117
RUN cargo build --release

FROM ghcr.io/mikopbx/mikopbx-x86-64 AS runner

COPY ./app/timerCall.sh /var/lib/asterisk/agi-bin/
COPY ./app/timerCron.sh /var/lib/asterisk/agi-bin/
COPY ./app/address_get.php /var/lib/asterisk/agi-bin/

RUN mkdir -p /root/application/call

# Installing full-featured Japanese voice
WORKDIR /work
RUN curl -o asterisk-core-sounds-ja-gsm-current.tar.gz https://downloads.asterisk.org/pub/telephony/sounds/asterisk-core-sounds-ja-gsm-current.tar.gz
WORKDIR /work/ja-jp
RUN tar xf ../asterisk-core-sounds-ja-gsm-current.tar.gz
WORKDIR /work
RUN rm -rf /offload/asterisk/sounds/ja-jp
RUN mv -f /work/ja-jp /offload/asterisk/sounds/
RUN rm -rf /work/asterisk-core-sounds-ja-gsm-current.tar.gz

# Installing my117
COPY --from=my117-builder /work/my117/target/release/my117 /usr/local/bin/
