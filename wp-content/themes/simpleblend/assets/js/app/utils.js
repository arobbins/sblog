jQuery.fn.reverse = [].reverse;


/*

Touch Click

*/
function touchClick($btn, fnc) {

  $btn.on("click touchstart", function(event) {

    event.stopPropagation();
    event.preventDefault();

    if(event.handled !== true) {
      fnc(event);
      event.handled = true;

    } else {
      return false;
    }

  });

}


/*

Responsify Tables

*/
function responsifyTables($) {
  // $('table').basictable({
  //   breakpoint: 700
  // });
};


/*

Responsify Videos

*/
function responsifyVideos($) {
  $('.content-video').parent().fitVids();
};


/*

Check for IE

*/
function checkForIE($) {

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

export {
  responsifyVideos,
  responsifyTables,
  checkForIE,
  touchClick
};
