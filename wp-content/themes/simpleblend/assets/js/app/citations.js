import { touchClick } from './utils';

/*

Position Citations

*/
function positionCitations($) {

  var $citationContent = $('.citation');
  var $citationNum = $('.citation-num');
  var topPos = [];

  $citationNum.each(function() {
    topPos.push( $(this).offset().top );
  });

  $citationContent.each(function(index, value) {
    $(this).css('top', topPos[index]);
    $(this).addClass('animated fadeIn is-visible');
  });

};


/*

Move Citations on Desktop

*/
function moveCitationsDesktop($) {

  var $citationContent = $('.citation');

  $citationContent.reverse().each(function() {
    $('.entry-content')
      .prepend(
        $(this)
          .removeClass('citation-mobile')
          .detach()
          .addClass('is-visible')
      );
  });

  $('.citation-num.citation-num-mobile').unbind('click');
  $('.citation-num').removeClass('citation-num-mobile');

  hoverCitationNum($);
  positionCitations($);

}


/*

On Citations Hover

*/
function hoverCitationNum($) {
  var $citationContent = $('.citation');
  var $citationNum = $('.citation-num');

  $('.citation-num:not(.citation-num-mobile)').bind({
    mouseenter: function(e) {
      console.log('in')
      var $citNum = $(this).data('citation-num');
      var $element = $('.citation[data-citation=' + $citNum + ']');
      $element.removeClass('fadeInDown').removeClass('fadeIn').addClass('wobble');
    },
    mouseleave: function(e) {
      var $citNum = $(this).data('citation-num');
      var $element = $('.citation[data-citation=' + $citNum + ']');

      $element.removeClass('wobble');
      console.log('out');
    }
   });
};


/*

Move Citations on Mobile

*/
function moveCitationOnMobile($) {

  $('.citation').each(function() {
    var citNum = $(this).data('citation');
    var $citNum = $('.citation-num[data-citation-num=' + citNum + ']');

    $citNum
      .unbind('mouseenter mouseleave')
      .addClass('citation-num-mobile')
      .after(
        $(this)
          .addClass('citation-mobile')
          .removeClass('is-visible wobble')
          .detach()
      );
  });
};


/*

Show Citations on Click

*/
function showCitationOnClick($) {
  $('.citation-num.citation-num-mobile').each(function() {
    touchClick($(this), () => {
      $(this).next().addClass('fadeIn').slideToggle(200);
    })
  });
}


/*

Init Citations

*/
function citationsInit($) {
  var resizeTimer;

  function resizeFunction() {
    if (window.innerWidth < 1300) {
      console.log('below 1300');
      moveCitationOnMobile($);
      showCitationOnClick($);

    } else {
      console.log('above 1300');
      moveCitationsDesktop($);
    }
  };

  $(window).resize(function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(resizeFunction, 250);
  });

  resizeFunction();

}



export {
  citationsInit
}
