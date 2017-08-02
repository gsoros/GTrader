FROM debian:stretch-slim

WORKDIR /gtrader

ENV PAXIFY 'setfattr -n user.pax.flags -v "m"'
ENV PAX_PHP "$PAXIFY /usr/bin/php"
ENV PAX_NODE "$PAXIFY /usr/bin/nodejs"

ENV SUG "su -s /bin/sh -m gtrader -c"
ENV CACHE /tmp/cache


RUN DEBIAN_FRONTEND=noninteractive LC_ALL=C.UTF-8 \
    apt-get update && apt-get install -y --no-install-recommends \
    software-properties-common dirmngr gnupg locales \
    && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update && apt-get install -y --no-install-recommends \
                                            php-dev \
                                        php-cli \
                                    php-fpm \
                                php-mysql \
                            php-gd \
                        php-mcrypt \
                    php-xml \
                php-zip \
            php-mbstring \
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
    && curl -sL https://deb.nodesource.com/setup_7.x | bash - \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs \
    \
    \
    && echo "############### CLEAN UP ######################" \
    && apt-get -y remove libfann-dev make php-dev software-properties-common \
    dirmngr gnupg locales \
    && apt-get -y autoremove && apt-get clean \
    && rm -rfv /var/cache/apt/* /var/lib/apt/lists/* /tmp/pear*

COPY . /gtrader

RUN    echo "############### FILES #########################" \
    && cp -Rv /gtrader/docker-fs/* / \
    && useradd -G www-data -d /gtrader -s /bin/bash -M gtrader \
    && for file in laravel schedule trainingManager bots; \
        do touch /gtrader/storage/logs/$file.log; \
    done \
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
    && rm -rfv /tmp/npm* \
    \
    \
    && echo "############### CRONTAB #######################" \
    && $SUG "crontab -u gtrader crontab"

EXPOSE 9000

CMD ["/usr/bin/runsvdir", "/etc/service"]
