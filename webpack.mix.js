const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | PR bullshit removed
 |
 */

mix
    .js('resources/assets/js/app.js',           'public/js')

    .sass('resources/assets/sass/app.scss',     'public/css')
    .sass('resources/assets/sass/Chart.scss',   'public/css')
    .sass('resources/assets/sass/PHPlot.scss',  'public/css')
    .sass('resources/assets/sass/diff.scss',    'public/css')

    .copy('resources/assets/js/PHPlot.js',      'public/js')
    .copy('resources/assets/js/Dummy.js',       'public/js')
    .copy('resources/assets/js/Mainchart.js',   'public/js')
    .copy('resources/assets/js/GTrader.js',     'public/js')

    .copy('node_modules/nouislider/distribute/nouislider.min.js',   'public/js')
    .copy('node_modules/nouislider/distribute/nouislider.min.css',  'public/css')

    .copy('node_modules/js-datepicker/dist/datepicker.min.js',      'public/js')
    .copy('node_modules/js-datepicker/dist/datepicker.min.css',     'public/css')

    .copy('node_modules/vis-network/dist/vis-network.min.js',       'public/js')
    .copy('node_modules/vis-network/dist/vis-network.min.css',      'public/css')

    ;
