
 setTimeout(function () { $('.js-open-1').fadeIn(); }, 1000);
 formCtrl('#promocodeForm', function(options)
   {
    options.func.startProcessing(); // Show loading process

    api({ // Load Control panel
      cmd: 'stage-handler',
      data: options.formData,
      html: '.js-promo-code-result',
      callback: function (data) 
        {
         if (typeof(data.reload_full) == 'undefined') { ; }
         else if (data.reload_full) 
           { // success
            options.func.endProcessing();
            WebAppRefresh();
            return; 
           }
         options.func.cancelProcessing(); // unsuccess
        }
      //,to_console: true
      });

   });
