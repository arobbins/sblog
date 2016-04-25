////////////
// Server //
////////////

import gulp from 'gulp';
import config from './config';

gulp.task('server', () => {

  config.bs.init({
    proxy: 'sblog.dev',
    notify: false
  });

  gulp.watch(config.files.css, gulp.series('css'));
  gulp.watch(config.files.html, gulp.series('html'));
  gulp.watch(config.files.js, gulp.series('build:app'));

});
