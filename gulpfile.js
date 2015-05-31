/*Load all plugin define in package.json*/
var gulp = require('gulp'),
	gulpLoadPlugins = require('gulp-load-plugins'),
	plugins = gulpLoadPlugins(),
	concat = require('gulp-concat-sourcemap');

/*JS task*/
gulp.task('dist', function () {
    gulp.src([
        'assets/js/src/main.js',
        'assets/js/src/tools.js',
        'assets/js/src/vendor/*.js',
        'assets/js/src/tools/*.js',
        'assets/js/src/views/*.js',
        'assets/js/src/models/*.js',
        'assets/js/src/routers/*.js',
        'assets/js/src/collections/*.js',
        'assets/js/src/init.js'
    ])
		.pipe(plugins.uglify())
		.pipe(concat('sis.min.js', { sourceRoot : '../../' }))
		.pipe(gulp.dest('assets/js/'));
});

gulp.task('dev', function () {
    return gulp.src([
        'assets/js/src/main.js',
        'assets/js/src/tools.js',
        'assets/js/src/vendor/*.js',
        'assets/js/src/tools/*.js',
        'assets/js/src/views/*.js',
        'assets/js/src/models/*.js',
        'assets/js/src/routers/*.js',
        'assets/js/src/collections/*.js',
        'assets/js/src/init.js'
    ])
        .pipe(plugins.jshint())
        .pipe(concat('sis.js', { sourceRoot : '../../' }))
        .pipe(gulp.dest('assets/js/'));
});

// On default task, just compile on demand
gulp.task('default', function() {
	gulp.watch( [ 'assets/js/src/*.js' ], [ 'dev' ] );
});