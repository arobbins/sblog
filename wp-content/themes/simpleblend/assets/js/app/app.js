import {
  responsifyVideos,
  responsifyTables
} from "./utils";

import {
  lazyLoadImgs
} from "./performance";

import {
  tooltipNewsletter,
  addFormClasses,
  onFormSubmission
} from "./forms";

import {
  citationsInit
} from "./citations";


(function($) {

  $(function() {

    /* Performance */
    lazyLoadImgs($);

    /* Forms */
    // tooltipNewsletter($);
    addFormClasses($);
    onFormSubmission($);
    
    /* Misc */
    responsifyVideos($);
    responsifyTables($);


    setTimeout(function() {
      /* Citations */
      citationsInit($);
    }, 250)


  });

})(jQuery);
