FROM debian:12-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    fpc \
    fpc-source \
    libncurses-dev \
    libc6-dev \
    postgresql-client \
    ca-certificates \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /opt/legacy

COPY legacy.pas ./
COPY run.sh /usr/local/bin/run_legacy.sh
RUN chmod +x /usr/local/bin/run_legacy.sh

CMD ["/usr/local/bin/run_legacy.sh"]
