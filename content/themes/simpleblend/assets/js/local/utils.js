(function($) {

  var Tooltip = require('tether-tooltip');

  var Utils = (function() {

    var $images = $('.is-lazy'),
        $videos = $('.content-video').parent(),
        $tables = $('table'),
        $body = $('body'),
        $content = $('.entry-content');

    var lazyLoadImgs = function lazyLoadImgs() {
      $images.lazyload({
        effect: "fadeIn"
      });
    };

    var responsifyTables = function responsifyTables() {
      // $tables.basictable({
      //   breakpoint: 700
      // });
    };

    var responsifyVideos = function responsifyVideos() {
      $videos.fitVids();
    };

    var checkForIE = function checkForIE() {

      var sAgent = window.navigator.userAgent;
      var Idx = sAgent.indexOf("MSIE");

      // If IE, return version number.
      if (Idx > 0) {
        // return parseInt(sAgent.substring(Idx+ 5, sAgent.indexOf(".", Idx)));
        return true;

      } else if (!!navigator.userAgent.match(/Trident\/7\./)) {
        return true;

      } else {
        //It is not IE
        return false;
      }
    };

    var addFormClasses = function addFormClasses() {
      console.log('inside');
      $('#chimpy_shortcode_1').submit(function(event) {
        console.log('ready');
        $(this).addClass('is-submitting');
      });

    };

    var tooltipNewsletter = function() {
      new Tooltip({
        target: document.querySelector('.tooltip-newsletter'),
        content: "I will <b>never</b> send you spam or sell your email address to any third-party.",
        classes: 'tooltip-tether-arrows',
        position: 'top left'
      });
    };

    return {
      lazyLoadImgs: lazyLoadImgs,
      responsifyVideos: responsifyVideos,
      responsifyTables: responsifyTables,
      checkForIE: checkForIE,
      addFormClasses: addFormClasses,
      tooltipNewsletter: tooltipNewsletter
    };

  })();

  /* Exposing our functions to the rest of the application */
  module.exports = Utils;

})(jQuery);
