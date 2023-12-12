'use strict';

$(function() {

    window.addEventListener('load', function() {
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.getElementsByClassName('needs-validation');
        // Loop over them and prevent submission
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    $('#submit').addClass('disabled');
                    $('#submit').html('Sending request...');
                }
                form.classList.add('was-validated');

            }, false);
        });
    }, false);
    if ($('#blog').length) {
        $('#blog').html('Loading latest posts...');
        $.ajax({
            type: "POST",
            url: "php/hail.php",
            success: function (text) {
                $('#blog').html(text);
            }
        });
    }

    $('.toggleWebinarTypes').on('click',function () {
        var selectedClass = $(this).data('class');
        var showText = $(this).data('show');
        var hideText = $(this).data('hide');

        $('.'+selectedClass).toggle();
        $('.'+selectedClass).toggleClass('show');
        if ($(this).hasClass('show')){
            $(this).html(hideText);
        } else {
            $(this).html(showText);
        }

    });
    /*
    |--------------------------------------------------------------------------
    | Configure your website
    |--------------------------------------------------------------------------
    |
    | We provided several configuration variables for your ease of development.
    | Read their complete description and modify them based on your need.
    |
    */

    page.config({

        /*
        |--------------------------------------------------------------------------
        | Google API Key
        |--------------------------------------------------------------------------
        |
        | Here you may specify your Google API key if you need to use Google Maps
        | in your application
        |
        | https://developers.google.com/maps/documentation/javascript/get-api-key
        |
        */

        googleApiKey: 'AIzaSyDRBLFOTTh2NFM93HpUA4ZrA99yKnCAsto',

        /*
        |--------------------------------------------------------------------------
        | Google Analytics Tracking
        |--------------------------------------------------------------------------
        |
        | If you want to use Google Analytics, you can specify your Tracking ID in
        | this option. Your key would be a value like: UA-12345678-9
        |
        */

        googleAnalyticsId: '',

        /*
        |--------------------------------------------------------------------------
        | Google reCAPTCHA
        |--------------------------------------------------------------------------
        |
        | reCAPTCHA protects you against spam and other types of automated abuse.
        | Please signup for an API key pair and insert your `Site key` value to the
        | following variable.
        |
        | http://www.google.com/recaptcha/admin
        |
        */

        reCaptchaSiteKey:  '6Ldaf0MUAAAAAHdsMv_7dND7BSTvdrE6VcQKpM-n',

        // See available languages: https://developers.google.com/recaptcha/docs/language
        reCaptchaLanguage: '',

        /*
        |--------------------------------------------------------------------------
        | Disable AOS on mobile
        |--------------------------------------------------------------------------
        |
        | If true, the Animate On Scroll animations don't run on mobile devices.
        |
        */

        disableAOSonMobile: true,

        /*
        |--------------------------------------------------------------------------
        | Smooth Scroll
        |--------------------------------------------------------------------------
        |
        | If true, the browser's scrollbar moves smoothly on scroll and gives your
        | visitor a better experience for scrolling.
        |
        */

        smoothScroll: true,

    });





    /*
    |--------------------------------------------------------------------------
    | Custom Javascript code
    |--------------------------------------------------------------------------
    |
    | Now that you configured your website, you can write additional Javascript
    | code below this comment. You might want to add more plugins and initialize
    | them in this file.
    |
    */



});

