(function($) {

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

    return {
      lazyLoadImgs: lazyLoadImgs,
      responsifyVideos: responsifyVideos,
      responsifyTables: responsifyTables,
      checkForIE: checkForIE
    }

  })();

  /* Exposing our functions to the rest of the application */
  module.exports = Utils;

})(jQuery);