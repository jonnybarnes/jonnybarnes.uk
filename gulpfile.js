var gulp = require('gulp');
var zopfli = require('gulp-zopfli');
var brotli = require('gulp-brotli');
var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.sass('global.scss', 'public/assets/css');
    mix.version([
        'assets/css/global.css',
        'assets/css/projects.css',
        'assets/css/alertify.css',
        'assets/css/sanitize.min.css',
        'assets/css/prism.css',
        'assets/js/form-save.js',
        'assets/js/links.js',
        'assets/js/maps.js',
        'assets/js/newplace.js',
        'assets/js/newnote.js',
        'assets/js/fetch.js',
        'assets/js/alertify.js',
        'assets/js/store2.min.js',
        'assets/js/Autolinker.min.js',
        'assets/js/marked.min.js',
        'assets/js/prism.js',
    ]);
});

gulp.task('gzip-built-css', function() {
    return gulp.src('public/build/assets/css/*.css')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/build/assets/css/'));
});

gulp.task('br-built-css', function() {
    return gulp.src('public/build/assets/css/*.css')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/build/assets/css/'));
});

gulp.task('gzip-built-js', function() {
    return gulp.src('public/build/assets/js/*.js')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/build/assets/js/'));
});

gulp.task('br-built-js', function() {
    return gulp.src('public/build/assets/js/*.js')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/build/assets/js/'));
});

gulp.task('bower', function() {
    //copy JS files
    gulp.src([
            'bower_components/fetch/fetch.js',
            'bower_components/alertify.js/dist/js/alertify.js',
            'bower_components/store2/dist/store2.min.js',
            'bower_components/Autolinker.js/dist/Autolinker.min.js',
            'bower_components/marked/marked.min.js',
        ])
        .pipe(gulp.dest('public/assets/js/'));
    //copy CSS files
    gulp.src([
            'bower_components/alertify.js/dist/css/alertify.css',
            'bower_components/sanitize-css/dist/sanitize.min.css',
        ])
        .pipe(gulp.dest('public/assets/css/'));
});

gulp.task('compress', ['gzip-built-css', 'br-built-css', 'gzip-built-js', 'br-built-js']);
