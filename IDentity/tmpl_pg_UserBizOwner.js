
 $('.js-open').click(function ()
 {
  var open = $(this).data('open');
  var hide = $(this).data('hide');
  if (typeof(hide) == 'undefined') { hide = ''; }

  if (hide.length) $(hide).slideUp();
  $(open).slideDown();
 });


 $('.js-btn-employee-report').click(function ()
 {
  var sendData = { 
    f_formName: 'get-report',
    id_report: $(this).data('id-report') 
    };

  api({ // Load Control panel
    cmd: 'stage-handler',
    data: sendData,
    html: '#employee_report',
    callback: function (data) 
      {
       $('.shared-info').slideUp();
       $('.shared-item').slideDown();
      }
    //,to_console: true
    });
 });


 $('.js-btn-employee-report2').click(function ()
 {
  var sendData = { 
    f_formName: 'get-report',
    item: 'shared',
    id_report: $(this).data('id') 
    };

  api({ // Load Control panel
    cmd: 'stage-handler',
    data: sendData,
    html: '#shared_item',
    callback: function (data) 
      {
       $('.shared-info').slideUp();
       $('.shared-item').slideDown();
      }
    //,to_console: true
    });

 });


 $('.js-btn-shared-back').click(function ()
 {

  $('.shared-info').slideDown();

  $('.shared-item').slideUp();
  
 });

 $('.js-btn-employee-report-unshare').click(function ()
 {
  var sendData = { 
    f_formName: 'unshare-report',
    id_report: $(this).data('id-report'),
    id_share: $(this).data('id-share'),
    id_user: $(this).data('id-user')
    };

  /*api({ // Load Control panel
    cmd: 'stage-handler',
    data: sendData,
    html: '#shared_item',
    callback: function (data) 
      {
       $('.shared-info').slideUp();
       $('.shared-item').slideDown();
      }
    });*/
 });


 formCtrl('#shareReportForm', function(options)
   {
    var count = 0;
    options.formData.push({ name: 'report_ids',
    value: $(".table-default input:checkbox:checked[name=employee]").map(function() { 
      count ++;
      return $(this).val(); 
      }).get() // <----
    });

    if (!count) { alert('Вы не отметили ни одного отчета.'); return false; }

    options.func.startProcessing(); // Show loading process
    api({ // Load Control panel
      cmd: 'stage-handler',
      data: options.formData,
      html: '.js-share-result',
      callback: function (data) 
        {
         $('.table-default input:checkbox[name=employee]').prop('checked', false);
         options.func.cancelProcessing(); // unsuccess

         /*if (typeof(data.reload_full) == 'undefined') { ; }
         else if (data.reload_full) 
           { // success
            options.func.endProcessing();
            WebAppRefresh();
            return; 
           }
         options.func.cancelProcessing(); // unsuccess
         */
        }
      });

   });
