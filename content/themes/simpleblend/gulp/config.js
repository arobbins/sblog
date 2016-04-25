////////////
// Config //
////////////

import browserSync from 'browser-sync';

const config = {
  files: {
    html: './assets/**/*.html',
    js: './assets/**/*.js',
    css: './assets/css/local/**/*.scss',
    cssEntry: './assets/css/local/app.scss',
    entry: './assets/app.js'
  },
  folders: {
    dest: './assets/css',
    app: './app'
  },
  names: {
    vendor: 'vendor.min.js',
    app: 'app.min.js',
    css: 'app.min.css'
  },
  libs: [
    'rx',
    'rx-dom',
    'jquery',
    'bitcoinjs-lib'
  ],
  bs: browserSync.create()

}

export default config;
