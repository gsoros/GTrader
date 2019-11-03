# About GTrader

[![Docker Automated Build](https://img.shields.io/docker/cloud/automated/gsoros/gtrader?style=plastic)](https://hub.docker.com/r/gsoros/gtrader/) [![Docker Build Status](https://img.shields.io/docker/cloud/build/gsoros/gtrader?style=plastic)](https://hub.docker.com/r/gsoros/gtrader/)

GTrader is a trading strategy back-tester and bot manager.

# Users

Please use [GTrader-env](https://github.com/gsoros/GTrader-env) to set up the PHP, MySQL and Nginx-SSL environment in Docker containers.

# Developers

#### Either run from the Dockerhub container...

1. `docker run -d --name gtrader_php -p 127.0.0.1:9000:9000 gsoros/gtrader:latest`
2. inside the container, edit .env with your database settings
3. configure your webserver to use php-fpm

#### ... or rebuild everything

##### Requirements

- PHP 7 with GD support
- [PHP-FANN extension](http://php.net/manual/en/book.fann.php)
- [Trader extension](http://php.net/manual/en/book.trader.php)
- [Composer](https://getcomposer.org/)
- [NPM](https://www.npmjs.com/)

##### Installation

1. `git clone https://github.com/gsoros/GTrader.git`
2. `cd GTrader`
3. `composer install`
4. `npm install`
5. `cp .env.example .env`
6. `php artisan key:generate`
7. edit .env
8. set up db
9. `php artisan migrate`
10. `npm run dev`
11. ``(crontab -l; echo -e "### GTrader Schedule\n* * * * * `which php` `pwd`/artisan schedule:run >> `pwd`/storage/logs/schedule.log 2>&1") | crontab -``
12. `php artisan serve`

# Screenshots

![main chart](https://cloud.githubusercontent.com/assets/12033369/23566860/fdeaecca-0053-11e7-9c57-7de5d9aa8297.png)

![settings](https://cloud.githubusercontent.com/assets/12033369/23566869/08e82b60-0054-11e7-9637-3de98b20c5cf.png)

![training](https://cloud.githubusercontent.com/assets/12033369/23566864/01f26f1e-0054-11e7-82fd-c23d142728fa.png)

![strategies](https://cloud.githubusercontent.com/assets/12033369/23566871/0e0255da-0054-11e7-861d-3412d534c426.png)

# Warning and disclaimer

Trading carries a high level of risk. This software is at an early stage of development and most likely contains serious bugs. Please do not use it with a live exchange account unless you are prepared to lose part or all of your balance. Even if you are confident having extensively backtested a strategy and are familiar with the inner workings of this software, it is very likely that your strategy is suffering from overfitting or any number of the many pitfalls of algo-trading.

# License

[GPLv3](https://www.gnu.org/licenses/gpl-3.0.en.html)
