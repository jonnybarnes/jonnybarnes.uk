'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var brotli = require('gulp-brotli');
var uglify = require('gulp-uglify');
var zopfli = require('gulp-zopfli');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');

gulp.task('sass', function () {
    return gulp.src('./resources/assets/sass/global.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(autoprefixer({browsers: ['last 2 version']}))
        .pipe(sourcemaps.write('./maps'))
        .pipe(gulp.dest('./public/assets/css'));
});

gulp.task('js-assets', function () {
    //return gulp.src(['resources/assets/js/**/*'])
    //    .pipe(gulp.dest('./public/assets/js'));
    return gulp.src(['resources/assets/js/**/*'])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(sourcemaps.write('./maps'))
        .pipe(gulp.dest('./public/assets/js'));
});

gulp.task('frontend', function () {
    //copy JS files
    gulp.src([
            'node_modules/whatwg-fetch/fetch.js',
            'node_modules/alertify.js/dist/js/alertify.js',
            'node_modules/store2/dist/store2.min.js',
            'node_modules/autolinker/dist/Autolinker.min.js',
            'node_modules/marked/marked.min.js',
        ])
        .pipe(gulp.dest('public/assets/frontend/'));
    //copy CSS files
    gulp.src([
            'node_modules/alertify.js/dist/css/alertify.css',
            'node_modules/sanitize.css/sanitize.css',
        ])
        .pipe(gulp.dest('public/assets/frontend/'));
});

gulp.task('compress', function () {
    //hand-made css
    gulp.src('public/assets/css/*.css')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/assets/css/'));
    gulp.src('public/assets/css/*.css')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/assets/css/'));
    //hand-made js
    gulp.src('public/assets/js/*.js')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/assets/js/'));
    gulp.src('public/assets/js/*.js')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/assets/js/'));
    //bower components
    gulp.src('public/assets/frontend/*.css')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/assets/frontend/'));
    gulp.src('public/assets/frontend/*.js')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/assets/frontend/'));
    gulp.src('public/assets/bower/*.css')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/assets/frontend/'));
    gulp.src('public/assets/bower/*.js')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/assets/frontend/'));
    //prism
    gulp.src('public/assets/prism/*.css')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/assets/prism/'));
    gulp.src('public/assets/prism/*.js')
        .pipe(zopfli({ format: 'gzip', append: true }))
        .pipe(gulp.dest('public/assets/prism/'));
    gulp.src('public/assets/prism/*.css')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/assets/prism/'));
    gulp.src('public/assets/prism/*.js')
        .pipe(brotli.compress({mode: 1, quality: 11}))
        .pipe(gulp.dest('public/assets/prism/'));
});
