import Tooltip from 'tether-tooltip';

/*

Tooltip Newsletter

*/
function tooltipNewsletter($) {
  new Tooltip({
    target: document.querySelector('.tooltip-newsletter'),
    content: "I will never send spam or sell your email address to third-parties",
    classes: 'tooltip-tether-arrows',
    position: 'top right'
  });
};

/*

Adding form classes

*/
function addFormClasses($) {

  $('#chimpy_shortcode_1').submit(function(event) {
    $(this).addClass('is-submitting');
  });

};


/*

On form submission

*/
function onFormSubmission() {

  $('.chimpy_signup_form').unbind('keydown');

  $('#chimpy_shortcode_1').submit(function(e) {
    e.stopPropagation();
    e.preventDefault();
    return false;

    // $("#chimpy_shortcode_submit").trigger("click");
    // console.log('before submit');
    //
    // console.log('after prevent');



    // console.log( "Handler for .keypress() called." );
    //
    // if (!e) {
    //   e = window.event;
    // }
    //
    // var keyCode = e.keyCode || e.which;
    //
    // if (keyCode == '13') {
    //   // Enter pressed
    //   console.log('console.log');
    //   return false;
    // }


  });

}

export {
  tooltipNewsletter,
  addFormClasses,
  onFormSubmission
}
