FROM debian:stretch-slim

WORKDIR /gtrader

ENV PAXIFY 'setfattr -n user.pax.flags -v "m"'
ENV PAX_PHP "$PAXIFY /usr/bin/php"
ENV PAX_NODE "$PAXIFY /usr/bin/nodejs"

ARG GTRADER_UID
ENV SUG "su -s /bin/sh -m gtrader -c"
ENV CACHE /tmp/cache
ENV PHPVER 7.3


RUN DEBIAN_FRONTEND=noninteractive LC_ALL=C.UTF-8 \
    apt-get update && apt-get install -y --no-install-recommends \
    software-properties-common dirmngr gnupg locales wget apt-transport-https lsb-release ca-certificates \
    && wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
    && sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list' \
    && apt-get update && apt-get install -y --no-install-recommends \
                                                build-essential \
                                            php-mcrypt php-pear \
                                        php$PHPVER-dev \
                                    php$PHPVER-cli \
                                php$PHPVER-fpm \
                            php$PHPVER-mysql \
                        php$PHPVER-gd \
                    php$PHPVER-xml \
                php$PHPVER-zip \
            php$PHPVER-mbstring \
        php$PHPVER-bcmath \
            php$PHPVER-curl curl \
                openssl \
                    libpng-dev \
                        git \
                            unzip \
                                mysql-client \
                                    libfann2 \
                                libfann-dev \
                            make \
                        attr \
                    nano \
                cron \
            logrotate \
        gnupg \
    runit


RUN set -eux; \
    echo "############### PECL ##########################" \
    && pecl channel-update pecl.php.net \
    && pecl install trader \
    && pecl install fann \
    \
    \
    && echo "############### GET COMPOSER ##################" \
    && $PAX_PHP \
    && curl -sL https://getcomposer.org/installer | php -- --install-dir /usr/bin --filename composer \
    \
    \
    && echo "############### GET NODE ######################" \
    && curl -sL https://deb.nodesource.com/setup_10.x | bash - \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs

COPY . /gtrader

RUN cp -Rv /gtrader/docker-fs/etc /

RUN    echo "############### FILES #########################" \
    && mkdir -p /run/php \
    && useradd -u "${GTRADER_UID:-1001}" -G www-data -d /gtrader -s /bin/bash -M gtrader \
    && for file in GTrader laravel schedule trainingManager bots; \
        do touch /gtrader/storage/logs/$file.log; \
    done \
    && chown -R gtrader:gtrader /gtrader \
    && mkdir -p /gtrader/storage/cache/data \
    && for dir in /gtrader/storage /gtrader/bootstrap/cache; do \
            chgrp -R www-data $dir; \
            find $dir -type d -exec chmod 775 {} \;; \
            find $dir -type f -exec chmod 664 {} \;; \
        done \
    && phpenmod pdo_mysql trader fann \
    \
    \
    && echo "############### COMPOSER INSTALL ##############" \
    && $PAX_PHP \
    && $SUG "mkdir -p $CACHE/composer && COMPOSER_CACHE_DIR=$CACHE/composer composer install" \
    \
    \
    && echo "############### NPM INSTALL ###################" \
    && $PAX_NODE \
    && $SUG "mkdir -p $CACHE/npm && npm_config_cache=$CACHE/npm npm install" \
    && rm -rf $CACHE \
    \
    \
    && echo "############### ARTISAN #######################" \
    && $SUG "cp docker.env .env" \
    && $PAX_PHP \
    && $SUG "php artisan key:generate" \
    && $PAX_PHP \
    && $SUG "php artisan optimize" \
    \
    \
    && echo "############### NPM RUN DEV ###################" \
    && $PAX_NODE \
    && $SUG "HOME=/tmp npm run dev" \
    && rm -rf /tmp/npm* \
    \
    \
    && echo "############### CRONTAB #######################" \
    && $SUG "crontab -u gtrader crontab" \
    \
    \
    && echo "############### CLEAN UP ######################" \
    && apt-get -y remove libfann-dev make php-dev software-properties-common \
    dirmngr gnupg locales \
    && apt-get -y autoremove && apt-get clean \
    && rm -rf /var/cache/apt/* /var/lib/apt/lists/* /tmp/pear*

EXPOSE 9000

CMD ["/usr/bin/runsvdir", "/etc/service"]
