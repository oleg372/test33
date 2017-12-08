$(document).ready(function() 
{
 $('#reg-users').filterTable({ inputSelector: '#reg-user-search' });
 var o = $('#reg-user-search'); if (o.length) { $('#reg-user-search').val('').focus(); }
 $('#reg-user-search').keyup(function(e) { if (e.keyCode == 27) { $('#reg-user-search').val(''); } });  // esc


 $('.js-btn-tab').click(function ()
   {
    var tab = $(this).attr('data-tab');
    $('.js-tab-list .js-tab').hide();
    $('.'+ tab).show();
    window.location.hash = '#'+ tab;
   });

 var h = window.location.hash.split('#')[1];
 if (typeof(h) != 'undefined') $('.js-btn-tab[data-tab='+ h +']').click();
 else $('.js-btn-tab:eq(0)').click();


 // Controll apply mini forms

 $('form.apply-ctrl').each(function (i, el)
   {
    $(el).find('.js-btn-apply').hide().click(function() 
      {
       $(el).submit();
      });

    $(el).find('select').change(function ()
      { // on change
       $(el).find('.js-btn-apply').show();
      });
    $(el).find('input').change(function ()
      { // on change
       $(el).find('.js-btn-apply').show();
      });

   });
});
