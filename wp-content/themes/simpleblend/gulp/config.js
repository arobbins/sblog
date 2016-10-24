////////////
// Config //
////////////

import browserSync from 'browser-sync';

const config = {
  files: {
    js: [
      './assets/js/app/**/*.js',
      '!./assets/js/app.min.js',
      '!./assets/js/vendor.min.js',
      '!./assets/js/app.min.js.map'
    ],
    jsEntry: './assets/js/app/app.js',
    css: './assets/css/**/*.scss',
    cssEntry: './assets/css/app/app.scss'
  },
  folders: {
    css: './assets/css',
    js: './assets/js'
  },
  names: {
    jsVendor: 'vendor.min.js',
    js: 'app.min.js',
    css: 'app.min.css'
  },
  libs: [
    'jquery',
    'imagesloaded',
    'lodash'
  ],
  bs: browserSync.create(),
  serverName: "sblog.dev"

};

export default config;
