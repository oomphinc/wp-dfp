// Plugins
var gulp        = require('gulp')
  , fs          = require('fs')
  , runSequence = require('gulp-sequence').use(gulp)
  , sass        = require('gulp-sass')
  , uglify      = require('gulp-uglify')
  , plumber     = require('gulp-plumber')
  , gutil       = require('gulp-util')
  , sourcemaps  = require('gulp-sourcemaps')
  , wpPot       = require('gulp-wp-pot')
  , pkg         = JSON.parse(fs.readFileSync('./package.json'))
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
  'wp-forms-api': {
    gulp: 'wp-forms-api/gulpfile.js',
    dest: 'wp-forms-api',
  }
};

/**
 * Scripts
 */
var scripts_frontend = ['js/jquery.dfp.js', 'js/wp-dfp.js'];
var scripts_admin = [];

// Styles
gulp.task('styles', function() {
  return gulp.src('sass/*.scss')
    .pipe(sass({ outputStyle: "compressed" }).on('error', sass.logError))
    .pipe(gulp.dest('wp-dfp/css'));
});

// Frontend scripts
gulp.task('frontend_scripts', function() {
  return gulp.src(scripts_frontend)
    .pipe(sourcemaps.init())
    .pipe(plumber({ errorHandler: onError }))
    .pipe(uglify())
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('wp-dfp/js'));
});

// Admin scripts
gulp.task('admin_scripts', function() {
  return gulp.src(scripts_admin)
    .pipe(sourcemaps.init())
    .pipe(plumber({ errorHandler: onError }))
    .pipe(uglify())
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('wp-dfp/js'));
});

// When ya wanna watch files
gulp.task('watch', function(cb) {
  // Watch the sass files
  gulp.watch('sass/**/*.scss', ['styles']);
  gulp.watch('js/*.js', ['scripts']);
  cb();
});

// Generate POT files
gulp.task('makepot', function() {
  return gulp.src(['wp-dfp/**/*.php', '!wp-dfp/externals/*'])
    .pipe(wpPot({
      domain:   'wp-dfp',
      destFile: 'wp-dfp.pot',
      package:  'wp-dfp'
    }))
    .pipe(gulp.dest('wp-dfp/languages'));
} );

// Build plugin header using the data from package.json
gulp.task('info', function(cb) {
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
  content = content
    .replace(matches[0], replace)
    .replace(/const VERSION = '([^']+)'/gm, "const VERSION = '" + pkg.version + "'")

  fs.writeFileSync(filename, content, 'utf-8');
  cb();
});

// Just build files including externals
gulp.task('build', runSequence('frontend', 'makepot', 'info'));

// Run all tasks by default
gulp.task('default', runSequence('build'));

// Build frontend assets
gulp.task('frontend', runSequence('styles', 'scripts'));

// All scripts
gulp.task('scripts', runSequence('frontend_scripts', 'admin_scripts'));
