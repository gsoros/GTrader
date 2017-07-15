## About GTrader

GTrader is a trading strategy back-tester and bot manager.

## For users
Please use [GTrader-env] (https://github.com/gsoros/GTrader-env) which sets up the PHP, MySQL and Nginx-SSL environment in Docker containers.

## For developers
# Requirements
* PHP 7 with GD support
* [PHP-FANN extension] (http://php.net/manual/en/book.fann.php)
* [Trader extension] (http://php.net/manual/en/book.trader.php)
* [Composer] (https://getcomposer.org/)
* [NPM] (https://www.npmjs.com/)

# Installation
1. ```git clone https://github.com/gsoros/GTrader.git```
2. ```cd GTrader```
3. ```composer install```
4. ```npm install```
5. ```cp .env.example .env```
6. ```php artisan key:generate```
7. edit .env
8. set up db
9. ```php artisan migrate```
10. ```npm run dev```
11. ```(crontab -l; echo -e "### GTrader Schedule\n* * * * * `which php` `pwd`/artisan schedule:run >> `pwd`/storage/logs/schedule.log 2>&1") | crontab -```
12. ```php artisan serve```

## Screenshots
![main chart](https://cloud.githubusercontent.com/assets/12033369/23566860/fdeaecca-0053-11e7-9c57-7de5d9aa8297.png)

![settings](https://cloud.githubusercontent.com/assets/12033369/23566869/08e82b60-0054-11e7-9637-3de98b20c5cf.png)

![training](https://cloud.githubusercontent.com/assets/12033369/23566864/01f26f1e-0054-11e7-82fd-c23d142728fa.png)

![strategies](https://cloud.githubusercontent.com/assets/12033369/23566871/0e0255da-0054-11e7-861d-3412d534c426.png)

## License
[GPLv3] (https://www.gnu.org/licenses/gpl-3.0.en.html)
