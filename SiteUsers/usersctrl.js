var UserAuthSuccess_callback = '';
$(document).ready(function()
{
 UserCtrlActivate();
});


 /**
  * Popup sign in form
  */
 function UserCtrlActivate()
 {
  $('.js-uc-sign-in').click(function()  { UsersAuthPopup(); });
  $('.js-uc-sign-out').click(function() { $.removeCookie('WebAppAuthSID', { path: '/' }); location.reload(); });
 }


 /**
  * Popup sign in form
  */
 function UsersAuthPopup(callbackSuccess)
 {
  if (typeof(callbackSuccess) == 'function') { UserAuthSuccess_callback = callbackSuccess; }
  else UserAuthSuccess_callback = false;

  UsersCtrlReq('popup', {
    //id_company: parseInt($('#reviews').data('id'))
    }, function (data)
    {
     if (data.err) { alert('ERROR #'+data.err+': '+data.error); return; }
     $('body').append(data.html);
     //console.log(data);

     $('#uc_layer').click(function () { $('#uc_layer').remove(); });
     $('#uc_layer .uc-popup').click(function () { return false; });
     $('#uc_layer .uc-close').click(function () { $('#uc_layer').remove(); });
    });
  
  //formCtrl('#reviewForm');
 }


 /**
  * Request router to backend
  */
 function UsersCtrlReq(cmd, prm, callback)
 {
  //var url = 'aj-catalog&act=users&cmd=' + cmd;
  var tmp = JSON.stringify(prm);

  api({ 
    cmd: cmd,
    //to_console: true,
    data: prm,
    callback: callback
    });
  
  /*$.ajax({
    type: 'POST',
    url: url,
    cache: false,
    data: prm, //{ 'in': prm },
    //contentType: 'application/json; charset=utf-8',
    dataType: 'json',
    success: function(data)
      {
       if (data.err) { alert('Error #'+ data.err); return; }
       if (typeof(callback) == 'function') { callback(data); }
      },
    error: function(xhr, ajaxOptions, thrownError)
      {
       alert('ERROR: '+thrownError);
      },
    failure: function(errMsg) 
      {
       alert('FAILURE: '+ errMsg);
      }
    });*/
 }


 /**
  * Popup sign in form
  * This function should be outside others
  */
 function UsersAuthSignIn(token)
 {
  UsersCtrlReq('signin', {
    token: token
    }, function (data)
    {
     if (data.err) { alert('ERROR #'+data.err+': '+data.error); return; }
     $('#uc_layer .uc-popup').html('<img src="core/mods/catalog/loading-dots.gif">');
     $.cookie('WebAppAuthSID', data.sid, { expires: 30, path: '/' }); // remember for 30 days

     if (typeof(UserAuthSuccess_callback) == 'function') 
       {
        $('#uc_layer').remove(); // close popup
        if (data.html_state === null) { ; } else { $('.uc-auth-state').html(data.html_state); }
        UserAuthSuccess_callback(data); 
        return; 
       }
     location.reload();
    });
 }

