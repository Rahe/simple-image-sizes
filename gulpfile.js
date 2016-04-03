/*Load all plugin define in package.json*/
var gulp = require('gulp'),
	gulpLoadPlugins = require('gulp-load-plugins'),
	plugins = gulpLoadPlugins(),
	concat = require('gulp-concat-sourcemap');

/*JS task*/
gulp.task('dist', function () {
	gulp.src([
		'assets/js/src/sis.js',
		'assets/js/src/attachments.js',
		'assets/js/src/featured.js'
	])
		.pipe(plugins.uglify())
		.pipe(concat('app.min.js', { sourceRoot : '../../' }))
		.pipe(gulp.dest('assets/js/dist/'));

	gulp.src([
		'assets/css/sis-style.css'
	])
	.pipe(plugins.cssmin())
	.pipe(plugins.rename( { 'suffix' : '.min' } ))
	.pipe(gulp.dest('assets/css/'));
});

gulp.task('dev', function () {
	return gulp.src([
		'assets/js/src/sis.js',
		'assets/js/src/attachments.js',
		'assets/js/src/featured.js'
    ])
		.pipe(plugins.jshint())
		.pipe(concat('app.js', { sourceRoot : '../../' }))
		.pipe(gulp.dest('assets/js/dist/'));
});

// On default task, just compile on demand
gulp.task('default', function() {
	gulp.watch( [ 'assets/js/src/*.js', '!assets/js/src/*.min.js' ], [ 'dev' ] );
});