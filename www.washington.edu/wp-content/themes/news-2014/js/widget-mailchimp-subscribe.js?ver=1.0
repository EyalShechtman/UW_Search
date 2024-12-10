$(document).ready(function(){

   var form = $('#mailchimp'),
       response = form.find('.response'),
       submit = form.find('input[type=submit]');
   var msg  = {
     error   : 'An error has occurred. Please try again later.',
     failure : "Server's down! Please try again later."
   };
   form.submit(function() {
      submit.attr('disabled',true);
      response.removeClass().addClass('alert alert-info').html('Adding email address...');
      var data = {
        action : 'mailchimp_registration',
        email  : escape($('#email').val()),
        group  : form.find('input[name=pref]:checked').val()
      }

      $.post( mailchimp.ajaxurl, data, function(res) {
          response.removeClass('alert alert-info');
          if( res.indexOf('already subscribed') > -1 ) {
            response.addClass('alert alert-info').html(res);
          } else if( res.indexOf('Error') > -1 ) {
            response.addClass('alert alert-error').html(msg.error)
          } else {
            response.addClass('alert alert-success').html(res);
          }
          submit.attr('disabled', false);
        }).error( function(err) {
          response.html(msg.failure);
        });
      return false;
      });
});
