// Plugins
var gulp       = require('gulp')
  , fs         = require('fs')
  , del        = require('del')
  , exec       = require('child_process').exec
  , watch      = require('gulp-watch')
  , sass       = require('gulp-ruby-sass')
  , shell      = require('gulp-shell')
  , concat     = require('gulp-concat')
  , uglify     = require('gulp-uglify')
  , rem        = require('gulp-pixrem')
  , plumber    = require('gulp-plumber')
  , gutil      = require('gulp-util')
  , symlink    = require('gulp-sym')
  , sourcemaps = require('gulp-sourcemaps')
  , wpPot      = require('gulp-wp-pot')
  , zip        = require('gulp-zip')
  , path       = require('path')
  , pkg        = JSON.parse(fs.readFileSync('./package.json'))
;

// Outputs an error through plumber plugin
var onError = function (err) {
  gutil.beep();
  console.log(err);
};

/**
 * External plugins, as { src: dest, ... }
 *
 * Copies externals/<src> to wp-dfp/externals/<dest>
 */
var external_plugins = {
  'wp-forms-api/wp-forms-api': {
		gulp: 'wp-forms-api/gulpfile.js',
		dest: 'wp-forms-api',
  	}
};

/**
 * Scripts
 */
var scripts_frontend = ['js/jquery.dfp.js', 'js/wp-dfp.js'];
var scripts_admin = [];

// Clean up build artifacts
gulp.task('clean', function() {
  del(['wp-dfp/css/*', 'wp-dfp/js/*', 'wp-dfp/externals/*', '!.gitignore'])
});

// Lint PHP
gulp.task('phplint', function() {
  gulp.src('wp-dfp/*.php')
	 .pipe(shell('php -l "<%= file.path %>"'))
});

// All PHP build tasks
gulp.task('php', ['phplint']);

// Styles
gulp.task('styles', function() {
  sass('sass/frontend.scss', { style: 'compressed' })
	 .pipe(plumber({ errorHandler: onError }))
	 .pipe(gulp.dest('wp-dfp/css/'));
  sass('sass/admin.scss', { style: 'compressed' })
	 .pipe(plumber({ errorHandler: onError }))
	 .pipe(gulp.dest('wp-dfp/css/'));
});

// Frontend scripts
gulp.task('frontend_scripts', function() {
  gulp.src(scripts_frontend)
	 .pipe(sourcemaps.init())
	 .pipe(plumber({ errorHandler: onError }))
	 //.pipe(concat('frontend.js'))
	 .pipe(uglify())
	 .pipe(sourcemaps.write('./'))
	 .pipe(gulp.dest('wp-dfp/js'));
});

// Admin scripts
gulp.task('admin_scripts', function() {
  gulp.src(scripts_admin)
	 .pipe(sourcemaps.init())
	 .pipe(plumber({ errorHandler: onError }))
	 //.pipe(concat('admin.js'))
	 .pipe(uglify())
	 .pipe(sourcemaps.write('./'))
	 .pipe(gulp.dest('wp-dfp/js'));
});

// All scripts
gulp.task('scripts', ['frontend_scripts', 'admin_scripts']);

// When ya wanna watch files
gulp.task('watch', function() {
  // Watch the sass files
  gulp.watch('sass/**/*.scss', ['styles']);
  gulp.watch('js/*.js', ['scripts']);
});

// Copy externals
gulp.task('externals', function() {
  Object.keys(external_plugins).forEach(function(src) {
	 var info = external_plugins[src];

	 if(typeof info !== "object") {
		info = { dest: info };
	 }

	 var cb = function() {
		gulp.src('externals/' + src + '/**', { base: 'externals/' + src })
		  .pipe(plumber({ errorHandler: onError }))
		  .pipe(gulp.dest('wp-dfp/externals/' + info.dest));
	 }

	 if(info.gulp) {
		exec('gulp', { cwd: 'externals/' + src }, function (err, stdout, stderr) {
		  console.log(stdout);
		  console.log(stderr);
		  cb();
		});
	 }
	 else {
		cb();
	 }
  });
});

// Link externals (for development, so we don't have a development file and a file being debugged)
gulp.task('link-externals', function() {
  Object.keys(external_plugins).forEach(function(src) {
	 var info = external_plugins[src];

	 if(typeof info !== "object") {
		info = { dest: info };
	 }

	 if(info.gulp) {
		exec('gulp', { cwd: 'externals/' + src }, function (err, stdout, stderr) {
		  console.log(stdout);
		  console.log(stderr);
		});
	 }

	 gulp.src('externals/' + src, { base: 'externals/' + src })
		.pipe(plumber({ errorHandler: onError }))
		.pipe(symlink('wp-dfp/externals/' + info.dest))
  });
});

// Generate POT files
gulp.task('makepot', function() {
	gulp.src(['wp-dfp/**/*.php', '!wp-dfp/externals/*'])
		.pipe(wpPot({
			domain:   'wp-dfp',
			destFile: 'wp-dfp.pot',
			package:  'wp-dfp'
		}))
		.pipe(gulp.dest('wp-dfp/languages'));
} );

// Build plugin header using the data from package.json
gulp.task('header', function() {
	var filename = 'wp-dfp/wp-dfp.php'
	  , content  = fs.readFileSync(filename, 'utf-8')
	  , replace  = ''
	  , tpl      = fs.readFileSync('./header.txt', 'utf-8')
	  , d        = new Date()
	;

	replace = tpl
		.replace('{{pluginName}}', pkg.pluginName)
		.replace('{{homepage}}', pkg.homepage)
		.replace('{{description}}', pkg.description)
		.replace('{{version}}', pkg.version)
		.replace(/\{\{author\.name\}\}/g, pkg.author.name)
		.replace(/\{\{author\.url\}\}/g, pkg.author.url)
		.replace('{{license}}', pkg.license)
		.replace('{{year}}', d.getFullYear())
		// remove linefeed after last *, otherwise the comment would break
		.replace(/^\*[\r|\r\n]$/gm, '*')
	;

	matches = content.match(/\*(.|[\r\n])*?\*/);

	fs.writeFileSync(filename, content.replace(matches[0], replace), 'utf-8');
});

// Compress compiled plugin into zip file
gulp.task('compress', function() {
	gulp.src('wp-dfp/*', { base: '.' })
		.pipe(zip('wp-dfp-' + pkg.version + '.zip'))
		.pipe(gulp.dest('dist'));
});

// Just build files including externals
gulp.task('build', ['externals', 'frontend', 'makepot', 'header']);

// Run all tasks by default
gulp.task('default', ['build', 'php']);

// Build frontend assets
gulp.task('frontend', ['externals', 'styles', 'scripts']);

// Build for development: don't copy externals, don't merge scripts, watch
gulp.task('develop', ['link-externals', 'styles', 'scripts', 'header', 'watch']);

// Package up as zip file
gulp.task('package', ['build', 'compress']);
