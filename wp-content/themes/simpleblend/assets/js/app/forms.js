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

MC: Get list by ID
Returns promise

*/
function McSignUp() {

  var options = {
    type: 'POST',
    url: '/wp/wp-admin/admin-ajax.php',
    dataType: 'json',
    data: {
      action: "mailinglist_signup",
      email: $("#mailinglist-email").val(),
      nonce: $("#mailinglist-form #_wpnonce").val()
    }
  };

  return $.ajax(options);

}


function showLoader($form) {
  $form.find('.spinner').addClass('is-visible');
}


function hideLoader($form) {
  $form.find('.spinner').removeClass('is-visible');
}


function disableForm($form) {
  console.log(1);
  $form.find('input').addClass('is-disabled');
  console.log(2);
}


function enableForm($form) {
  console.log(3);
  $form.find('input').removeClass('is-disabled');
  console.log(4);
}

function testtt() {
  return new Promise(
    // The resolver function is called with the ability to resolve or
    // reject the promise
    function(resolve, reject) {

      window.setTimeout(
        function() {
            // We fulfill the promise !
            // resolve('done');
            reject();
        }, Math.random() * 2000 + 1000);
    }
  );
}


/*

On form submission

*/
function onFormSubmission() {

  // var $form = $("#mailinglist-form");

  $("#mailinglist-form").validate({

    submitHandler: function(form, e) {

      e.preventDefault();
      $(form).find('input').addClass('is-disabled')
      $(form).find('.spinner').addClass('is-visible');

      McSignUp()
        .done(function(data) {


          console.log("Success: ", data);

          if(data.code >= 400) {
            $(form).find('.mailinglist-error').addClass('is-visible');
            $(form).find('#mailinglist-email-error').append('<i class="fa fa-times-circle" aria-hidden="true"></i> Uh oh, we have an error! Looks like ' + data.message.title + '. Please try again');
            $(form).find('.spinner').removeClass('is-visible');
            $(form).find('input').removeClass('is-disabled');

          } else {
            $(form).find('.form-control').hide();
            $(form).find('.mailinglist-success').addClass('is-visible');
            $(form).find('.mailinglist-success').append('<i class="fa fa-check-circle" aria-hidden="true"></i> Success! Please check your email to finish signing up.');

            $(form).find('.spinner').removeClass('is-visible');
            $(form).find('input').removeClass('is-disabled');
          }


        })
        .fail(function(jqXHR, textStatus) {
          $(form).find('.mailinglist-error').addClass('is-visible');
          $(form).find('#mailinglist-email-error').append('Error!');

          $(form).find('.spinner').removeClass('is-visible');
          $(form).find('input').removeClass('is-disabled');

          console.log("textStatus: ", textStatus);
          console.log("jqXHR: ", jqXHR);

        });

    },

    rules: {
      email: {
        required: true,
        email: true
      }
    },

    errorClass: 'error',
    validClass: 'succes',

    highlight: function (element, errorClass, validClass) {
      $('#mailinglist-email').parent().removeClass('form-valid');
      $('.mailinglist-error').addClass('is-visible');
      $('.mailinglist-success').removeClass('is-visible');

    },
    unhighlight: function (element, errorClass, validClass) {
      // $('.mailinglist-success').addClass('is-visible');
      $('.mailinglist-error').removeClass('is-visible');

    },
    success: function(label){
      $('#mailinglist-email').parent().addClass('form-valid');

    },
    errorPlacement: function(error, element) {

      console.log("errorerror: ", error);
      // $('#mailinglist-email').parent().removeClass('form-valid');
      // $('.mailinglist-error').fadeIn('fast');
      error.appendTo($('.mailinglist-error'));

    }

  });

}

export {
  tooltipNewsletter,
  addFormClasses,
  onFormSubmission
}
