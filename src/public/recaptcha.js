(function($){

  let $loginForm = $('#loginform');
  let $lostPasswordForm = $('#lostpasswordform');
  let $registrationForm = $('#registerform');

  let $formContainer = [$loginForm, $lostPasswordForm, $registrationForm];

  $formContainer.forEach(function($form){
    $form.on('submit', function(e){
      e.preventDefault();
      return validateRecaptchaAndAuthenticate($form);
    });
  });


  function setWordpressError(message, $container){

    $('.fluent-recaptcha-error').remove();

    // Check if there is already an error message
    let $errorContainer = $('#login_error');

    if($errorContainer.length){
      $errorContainer.prepend('<strong>Error</strong>: ' + message + '<br>');
      return;
    }

    // If not, create one
    let $error = `
      <div id="login_error" class="fluent-recaptcha-error error">
        <strong>ERROR</strong>: ${message}
      </div>
    `
    $container.before($error);
  }

  function validateRecaptchaAndAuthenticate($container){

    let token = grecaptcha.getResponse();

    if(!token || token === ''){
      setWordpressError('Please complete the reCAPTCHA', $container);
      return;
    }

    return $.ajax({
      url: window.fluent_auth_recaptcha.fls_ajax_url,
      type: 'POST',
      data: {
        action: window.fluent_auth_recaptcha.fls_action,
        token: token
      }
    }).success(function(response){

      //if(response.success){
      $container.unbind('submit').submit();
      //}

    }).fail(function(error){
      setWordpressError(error.responseJSON.message)
    })
  }
}(jQuery));

