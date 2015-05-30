/*Load all plugin define in package.json*/
var gulp = require('gulp'),
	gulpLoadPlugins = require('gulp-load-plugins'),
	plugins = gulpLoadPlugins(),
	concat = require('gulp-concat-sourcemap');

/*JS task*/
gulp.task('dist', function () {
	gulp.src([
		'assets/js/sis.js'
	])
		.pipe(plugins.uglify())
		.pipe(concat('sis.min.js', { sourceRoot : '../../' }))
		.pipe(gulp.dest('assets/js/'));

    gulp.src([
        'assets/js/sis-attachments.js'
    ])
        .pipe(plugins.uglify())
        .pipe(concat('sis-attachments.min.js', { sourceRoot : '../../' }))
        .pipe(gulp.dest('assets/js/'));

		gulp.src([
			'assets/css/sis-style.css'
		])
		.pipe(plugins.cssmin())
		.pipe(plugins.rename( { 'suffix' : '.min' } ))
		.pipe(gulp.dest('assets/css/'));
});

gulp.task('dev', function () {
	return gulp.src([
        'assets/js/sis.js',
        'assets/js/sis-attachments.js'
    ])
		.pipe(plugins.jshint());
});

// On default task, just compile on demand
gulp.task('default', function() {
	gulp.watch( [ 'assets/js/*.js', '!assets/js/*.min.js' ], [ 'dev' ] );
});