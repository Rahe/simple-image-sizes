const { watch, series, src, dest } = require('gulp');
const concat = require('gulp-concat');
const cssmin = require('gulp-cssmin');
const rename = require('gulp-rename');
const uglify = require('gulp-uglify');


function defaultTask(cb) {
    watch('assets/js/src/*.js', dev);
  cb();
}


function dist(cb) {
    src(
        [
            'assets/js/src/sis.js',
            'assets/js/src/attachments.js',
            'assets/js/src/featured.js'
        ], { sourcemaps: true }
    )
        .pipe(uglify())
        .pipe(concat('app.min.js', {sourceRoot: '../../'}))
        .pipe(dest('assets/js/dist/', { sourcemaps: '.' }));

    src(
        [
            'assets/css/sis-style.css'
        ]
    )
        .pipe(cssmin())
        .pipe(rename({'suffix': '.min'}))
        .pipe(dest('assets/css/'));

  cb();
}

function dev(cb) {
    src(
            [
                'assets/js/src/sis.js',
                'assets/js/src/attachments.js',
                'assets/js/src/featured.js'
            ], { sourcemaps: true }
        )
        .pipe(concat('app.js', {sourceRoot: '../../'}))
        .pipe(dest('assets/js/dist/', { sourcemaps: '.' }));
  cb();
}


exports.default = defaultTask
exports.dist = dist;
exports.dev = dev;