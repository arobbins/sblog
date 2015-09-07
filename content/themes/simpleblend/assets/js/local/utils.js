(function($) {

  var Utils = (function() {

    var $images = $('.is-lazy'),
        $videos = $('.content-video').parent(),
        $body = $('body'),
        $content = $('.entry-content');

    var lazyLoadImgs = function lazyLoadImgs() {
      $images.lazyload({
        effect: "fadeIn"
      });
    };

    var responsifyVideos = function responsifyVideos() {
      $videos.fitVids();
    };

    return {
      lazyLoadImgs: lazyLoadImgs,
      responsifyVideos: responsifyVideos
    }

  })();

  /* Exposing our functions to the rest of the application */
  module.exports = Utils;

})(jQuery);