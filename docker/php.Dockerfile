FROM debian:stretch-slim

WORKDIR /gtrader

ENV PAXIFY 'setfattr -n user.pax.flags -v "m"'
ENV PAX_PHP "$PAXIFY /usr/bin/php"
ENV PAX_NODE "$PAXIFY /usr/bin/nodejs"

ENV SUG "su -s /bin/sh -m gtrader -c"
ENV CACHE /tmp/cache


RUN DEBIAN_FRONTEND=noninteractive \
    apt-get update && apt-get install -y --no-install-recommends \
                                            php7.0-dev \
                                        php7.0-cli \
                                    php7.0-fpm \
                                php7.0-mysql \
                            php7.0-gd \
                        php7.0-mcrypt \
                    php7.0-zip\
                php7.0-mysql \
            php7.0-mbstring \
                php-pear \
                    curl \
                        openssl \
                            ca-certificates \
                                git \
                                    unzip \
                                        mysql-client \
                                    libfann2 \
                                libfann-dev \
                            make \
                        attr \
                    nano \
                cron \
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
    && curl -sL https://deb.nodesource.com/setup_7.x | bash - \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs \
    \
    \
    && echo "############### CLEAN UP ######################" \
    && apt-get -y remove libfann-dev make php7.0-dev \
    && apt-get -y autoremove && apt-get clean \
    && rm -rfv /var/cache/apt/* /var/lib/apt/lists/* /tmp/pear*

COPY . /gtrader

RUN    echo "############### FILES #########################" \
    && cp -Rv /gtrader/docker/fs-gtrader/* / \
    && useradd -G www-data -m gtrader \
    && chown -Rc gtrader:gtrader /gtrader \
    && for dir in /gtrader/storage /gtrader/bootstrap/cache; do \
            chgrp -Rc www-data $dir; \
            find $dir -type d -exec chmod -c 775 {} \;; \
            find $dir -type f -exec chmod -c 664 {} \;; \
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
    && $SUG "mkdir $CACHE/npm && npm_config_cache=$CACHE/npm npm install" \
    && rm -rfv $CACHE \
    \
    \
    && echo "############### ARTISAN #######################" \
    && $SUG "cp docker/docker-gtrader.env .env" \
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
    && $SUG "crontab -u gtrader /gtrader/docker/crontab.gtrader"


CMD /usr/bin/runsvdir -P /etc/service
