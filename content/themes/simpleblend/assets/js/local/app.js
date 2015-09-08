(function($) {

  $(function() {

    var Utils = require('./utils');

    Utils.lazyLoadImgs();
    Utils.responsifyVideos();
    Utils.responsifyTables();

    if(Utils.checkForIE()) {
      $('body').addClass('is-ie');
    }

  });

})(jQuery);