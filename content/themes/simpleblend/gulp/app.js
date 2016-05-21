/////////
// App //
/////////

import gulp from 'gulp';
import config from './config';
import browserify from 'browserify';
import babelify from 'babelify';
import uglify from 'gulp-uglify';
import source from 'vinyl-source-stream';
import buffer from 'vinyl-buffer';
import sourcemaps from 'gulp-sourcemaps';
import rename from "gulp-rename";

gulp.task('build:app', () => {
  return browserify({
    entries: [config.files.entry],
    extensions: ['.js'],
    debug: true
  })
  .external(config.libs)
  .transform(babelify)
  .bundle()
  .pipe(source(config.names.app))
  .pipe(buffer())
  .pipe(sourcemaps.init())
    .pipe(uglify())
    .pipe(rename(config.names.app))
  .pipe(sourcemaps.write(config.folders.app))
  .pipe(gulp.dest(config.folders.app))
  .pipe(config.bs.stream());

});
