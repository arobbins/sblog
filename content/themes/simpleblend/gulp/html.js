//////////
// HTML //
//////////

import gulp from 'gulp';
import config from './config';

gulp.task('html', () => {
  return gulp.src(config.files.html)
    .pipe(gulp.dest(config.folders.dest))
    .pipe(config.bs.stream());
});
