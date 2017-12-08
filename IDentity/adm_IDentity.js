/*

function SamplePopup()
{
 $.fancybox.open(
   {
    type : 'ajax',
    href : '{aj}smpl',
    ajax : { type : 'POST', data : 'param1=value1&param2=value2' },
    openEffect : 'none', closeEffect : 'none', prevEffect : 'none', nextEffect : 'none',
    //width : 800, height : '100%', autoSize : false,
    afterShow : (function() 
      {
       $('#btnCancel').click(function() { $.fancybox.close(true); });
      }) // /afterShow
   }); // /$.fancybox.open
}
*/

$(document).ready(function() 
{
 $('.js-hide-item').click(function() 
   { 
    if (!confirm('Удалить фото участника? (спрятать физически)')) { return; }
    var id = $(this).attr('data-id');
    ajHidePhoto(id);
//    $(this).parent().parent().hide();
   });

 $('a.js-orig-logo').click(function ()
   { 
    $('div.js-orig-logo').slideDown();
   });
});


function ajHidePhoto(id)
{
 $.ajax(
   {
    type: 'POST',
    url: 'aj-mm=SiteData&sm=photoa&j=1&act=hideph&id='+ id,
    cache: false,
    dataType: 'json',
    data: '',
    success: function(data)
      {
       $('.js-hide-item[data-id='+ id +']').parent().parent().hide();
       console.log(id);
      },
    error: function(xhr, ajaxOptions, thrownError)
      {
       alert('Ошибка');
      }
   });
}
