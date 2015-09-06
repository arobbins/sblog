(function($) {

  var Utils = (function() {

    var $images = $("body");

    var sayHello = function sayHello() {
      alert('Hello!!');
    };

    return {
      sayHello: sayHello
    }

  })();

  /* Exposing our functions to the rest of the application */
  module.exports = Utils;

})(jQuery);