
 setTimeout(function () { $('.js-set-meeting').fadeIn(); }, 500);

 $('.js-btn-day').click(function ()
   {
    if ($(this).hasClass('item-unavail')) return false;

    // Дата выбрана, выбор времени
    var Day = $(this).data('day'), 
        DayFull = $(this).data('day-full'),
        id_meeting_day = $(this).data('id');

    $('.js-dates').slideUp();
    $('.js-day-times[data-day="'+ Day +'"]').slideDown();

    $('.js-btn-time').click(function ()
      {
       var Field = $(this).data('field'),
           Time  = $(this).data('time');
       console.log(Day, DayFull, Field, Time);

       $('#setMeetingForm input[name=id_meeting_day]').val(id_meeting_day);
       $('#setMeetingForm input[name=day]').val(Day);
       $('#setMeetingForm input[name=time]').val(Time);
       $('#setMeetingForm input[name=time_field]').val(Field);

       $('.js-when-time').text(Time);
       $('.js-when-date').text(DayFull);

       $('.js-day-times[data-day="'+ Day +'"]').slideUp();
       $('.js-set-meeting-final').slideDown();
      });

    $('.js-back-day').click(function ()
      {
       $('.js-dates').slideDown();
       $('.js-day-times[data-day="'+ Day +'"]').slideUp();
      });

    $('.js-back-all').click(function ()
      {
       $('.js-set-meeting-result').html('');
       $('.js-set-meeting-final').slideUp();
       $('.js-dates').slideDown();
      });

   });

 $('.js-ref-meet-place').change(function ()
   {
    var val = $(this).val();
    if (val == 'my') { $('.js-area-meet-place').slideDown().find('input').addClass('js-req'); }
    else             { $('.js-area-meet-place').slideUp().find('input').removeClass('js-req'); }
   });

 $('.js-ref-meet-place').change();

 formCtrl('#setMeetingForm', function(options)
   {
    options.func.startProcessing(); // Show loading process
    $('.js-back-all').hide();

    api({ // Load Control panel
      cmd: 'stage-handler',
      data: options.formData,
      html: '.js-set-meeting-result',
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
         $('.js-back-all').show();
        }
      //,to_console: true
      });

   });
