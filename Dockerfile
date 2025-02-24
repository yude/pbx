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

USER root

# Install full-featured Japanese voice
WORKDIR /work
RUN curl -o asterisk-core-sounds-ja-gsm-current.tar.gz https://downloads.asterisk.org/pub/telephony/sounds/asterisk-core-sounds-ja-gsm-current.tar.gz
WORKDIR /work/ja-jp
RUN tar xf ../asterisk-core-sounds-ja-gsm-current.tar.gz
WORKDIR /work
RUN rm -rf /offload/asterisk/sounds/ja-jp
RUN mv -f /work/ja-jp /offload/asterisk/sounds/
RUN rm -rf /work/asterisk-core-sounds-ja-gsm-current.tar.gz

# Install FFmpeg
WORKDIR /work
RUN curl -O https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz
RUN tar xf ffmpeg-release-amd64-static.tar.xz && \
    rm ffmpeg-release-amd64-static.tar.xz && \
    mv ffmpeg* ffmpeg
WORKDIR /work/ffmpeg
RUN chown -R root:root . && \
    ls -al . && \
    cp ffmpeg /usr/bin/ && \
    cp ffprobe /usr/bin/
WORKDIR /work
RUN rm -rf ffmpeg

# Install yt-dlp
WORKDIR /work
RUN curl -Is https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_linux | grep Location | sed -e "s/Location: //g" | sed -e 's/\r//g' > url.txt
RUN xargs -n 1 curl -Is < url.txt | grep "Location: " | sed -e "s/Location: //g" | sed -e 's/\r//g' > url2.txt
RUN xargs -n 1 curl -o yt-dlp < url2.txt
RUN chmod +x yt-dlp
RUN cp yt-dlp /usr/bin/
RUN rm *.txt yt-dlp

# Installing my117
COPY --from=my117-builder /work/my117/target/release/my117 /usr/local/bin/
