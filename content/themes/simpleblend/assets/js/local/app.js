(function($) {

  $(function() {

    var Utils = require('./utils');

    Utils.lazyLoadImgs();
    Utils.responsifyVideos();

  });

})(jQuery);