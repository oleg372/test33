/**
 * Initialization (start)
 */
function WebAppRun()
{
 $('#WebApp').html('');
 console.log('Web application is running.');
 api({ 
   cmd: 'login-page', //'start',
   html: '*',
   callback: function (data) {
     if (data.is_already_auth) {
        console.log('Already authorized, move to personal area.');
        WebAppRefresh();
        /*api({ // Load Control panel
          cmd: 'stage-handler',
          html: '*',
          callback: RunPersonalArea
          });*/
        }
     }
   });
}


/** 
 * API request
 */
function api(In)
{
 if (typeof(In.html) == 'undefined') { In.html = ''; }
 if (typeof(In.to_console) == 'undefined') { In.to_console = false; }
 var TS = Math.floor(Date.now() / 1000);
 $.ajax({
   type: 'POST',
   url: apiHref+'&_='+TS+'&cmd='+In.cmd,
   cache: false,
   data: In.data,
   dataType: 'json',
   success: function(data)
     {
      if (In.to_console) { console.log(data); } // trace

      if (/*data.status == 200 &&*/ data.error.code == 401) // Unauthorized
        {
         location.reload(true);
        }

      if (data.status != 200)
        {
         alert('ERROR #'+ data.error.code +'\r\n'+ data.error.message); 
         return false;
        }

      if (In.html == '*') { $('#WebApp').html(data.html); }
      else                { $(In.html).html(data.html); }

      if (typeof(data.title) == 'undefined') { ; } else { document.title = data.title; } // Update page title
      if (typeof(data.js) == 'undefined') { ; } else { eval(data.js); } // Eval script if returned
      if (typeof(In.callback) == 'function') { In.callback(data); } // callback with "data" (request result)
     },
   error: function(xhr, ajaxOptions, thrownError)
     {
      /*if (data.status == 401) // Unauthorized
        { // reload page (automaticly move to login page)
         console.log(thrownError);
         location.reload(true);
         return;
        }*/
      alert(thrownError);
      console.log(xhr);
      console.log(ajaxOptions);
      console.log(thrownError);
      }
   }); // end of: $.ajax
}


/**
 * Callback: after user auth/register new one
 */
UserAuthSuccess_callback = function (data) 
{
 if (data.is_new_one)
   { // a new one user
    // Fill up fields with profile data
    $('.sign-in-approve input[name=first_name]').val(data.profile.first_name);
    $('.sign-in-approve input[name=last_name]').val(data.profile.last_name);
    $('.sign-in-approve input[name=email]').val(data.profile.email);

    formCtrl('#signInApproveForm', function (options)//formData, sendTo, gotoLink)
      {
       options.func.startProcessing(); // Show loading process

       api({ 
         cmd: 'signin-approve',
         data: options.formData,
         callback: function (data) { 
           options.func.endProcessing();
           WebAppRefresh();
           }
         });

       return false; // don't allow to send
      });

    $('.sign-in').slideUp({ complete: function() { $(this).remove(); } });
    $('.sign-in-approve').slideDown({ complete: function() 
      { 
       // GUI: focus first field
       $('.sign-in-approve input[name=first_name]').focus();
      } 
    });
   }
 else
   { // Auth success
    WebAppRefresh();
   }
}


/**
 * REFRESH. Full reload page
 */
function WebAppRefresh()
{
 api({ // Load Control panel
   cmd: 'stage-handler',
   html: '*',
   callback: RunPersonalArea
   });
}


/**
 * Activate personal area 
 */
function RunPersonalArea()
{
 if (!$('.personal-area').length) return false;

 console.log('Pesonal area is running.');

 $('.js-btn').css('cursor', 'pointer');

 UserCtrlActivate(); // for sign out
 RunPgContent(); // first run

 $('.js-user-profile').click(function ()
   {
    $('.main-nav li.s').removeClass('s'); // deactivate actived item in main nav
    api({ 
      cmd: 'user-profile',
      html: '#content'
      });
   });

 // Run main (side-left) navigation menu
 $('.main-nav a').click(function ()
   {
    $('.main-nav li.s').removeClass('s');
    $(this).parent().addClass('s');
    api({ 
      cmd: 'pg',
      html: '#content',
      data: { pg: $(this).data('location') },
      callback: RunPgContent
      });
   });
}



function RunPgContent()
{
 // Run Tabs (if they exist)
 $('#content .js-tabs').each(function (index, el)
   {
    $(el).find('.js-tab-item').hide();
    var currTab = '';
    $(el).find('.js-tab-list .js-btn').click(function ()
      {
       var newTab = $(this).data('tab');
       if (newTab == currTab) return false;

       $(el).find('.js-tab-list .js-btn').parent().removeClass('s');
       $(el).find(this).parent().addClass('s');

       if (currTab) $(el).find('.js-tab-item[data-tab='+currTab+']').slideUp();
       $(el).find('.js-tab-item[data-tab='+newTab+']').slideDown();
       currTab = newTab;
      });

    $(el).find('.js-tab-list .js-btn:first').click();
   });
}
