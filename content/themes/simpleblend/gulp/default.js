/////////////
// Default //
/////////////

import gulp from 'gulp';

gulp.task('default',
  gulp.parallel('css', 'server')
);
