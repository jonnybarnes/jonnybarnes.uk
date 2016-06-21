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
    mix.copy('resources/assets/js', 'public/assets/js');
    mix.version([
        //hand-made css
        'assets/css/global.css',
        'assets/css/projects.css',
        //hand-made js
        'assets/js/form-save.js',
        'assets/js/links.js',
        'assets/js/maps.js',
        'assets/js/newplace.js',
        'assets/js/newnote.js',
        //bower components
        'assets/bower/alertify.css',
        'assets/bower/sanitize.css',
        'assets/bower/fetch.js',
        'assets/bower/alertify.js',
        'assets/bower/store2.min.js',
        'assets/bower/Autolinker.min.js',
        'assets/bower/marked.min.js',
        //prism
        'assets/prism/prism.js',
        'assets/prism/prism.css',
    ]);
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
        .pipe(gulp.dest('public/assets/bower/'));
    //copy CSS files
    gulp.src([
            'bower_components/alertify.js/dist/css/alertify.css',
            'bower_components/sanitize-css/sanitize.css',
        ])
        .pipe(gulp.dest('public/assets/bower/'));
});

gulp.task('compress', function () {
    //hand-made css
    gulp.src('public/build/assets/css/*.css')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/build/assets/css/'));
    gulp.src('public/build/assets/css/*.css')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/build/assets/css/'));
    //hand-made js
    gulp.src('public/build/assets/js/*.js')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/build/assets/js/'));
    gulp.src('public/build/assets/js/*.js')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/build/assets/js/'));
    //bower components
    gulp.src('public/build/assets/bower/*.css')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/build/assets/bower/'));
    gulp.src('public/build/assets/bower/*.js')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/build/assets/bower/'));
    gulp.src('public/build/assets/bower/*.css')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/build/assets/bower/'));
    gulp.src('public/build/assets/bower/*.js')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/build/assets/bower/'));
    //prism
    gulp.src('public/build/assets/prism/*.css')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/build/assets/prism/'));
    gulp.src('public/build/assets/prism/*.js')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/build/assets/prism/'));
    gulp.src('public/build/assets/prism/*.css')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/build/assets/prism/'));
    gulp.src('public/build/assets/prism/*.js')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/build/assets/prism/'));
});
