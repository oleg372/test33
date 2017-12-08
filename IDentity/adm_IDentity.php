<?php
/**
 * @name IDentity
 * @copyright 2017, Potapov studio
 * @link http://potapov.com.ua/cms/IDentity/
 */

class TAdmIDentity
{
 /**
  * Приватные переменные
  */
 private $L; // массив текстровых строк, языковой поддержки

 
 /**
  * Публичные переменные
  */
 public  $WorkPath; 

 
 /**
  * Конструктор
  * @param string $WPath - Путь к папке модуля, передается из вне
  * @return void
  */
 public function __construct($WPath)
 {
  $this->WorkPath = $WPath;
 }
 
 
 /**
  * Получить заговолов страницы
  *
  * @return string Возвращает заголовок для страницы
  */
 public function GetTitle()
 {
  return 'IDentity';
 }


 /**
  * Сгенерировать HEAD 
  *
  * @return string Возвращает заголовок для страницы
  */
 public function GenHead()
 {
  global $CMSA;
  //$ajl = 'aj-mm='.$_GET['mm'].'&sm='.$_GET['sm'].'&act='; // AJaxLink
 
  // С менеджером таблиц:
  $S = $CMSA->AttachTblMng(true);
  $S .= '<script type="text/javascript" src="'. $this->WorkPath . 'adm_IDentity.js' .'"></script>';
  return $S;
 }


 /**
  * Сгенерировать контент модуля для админки
  * 
  * @return cod Возвращает HTML-код
  */
 public function GenContent()
 {
  $Cod = '';
  switch ($_GET['sm'])
    {
//     case 'msgs':      { $Cod = $this->PgMsgs(); break; }
//     case 'faq':       { $Cod = $this->PgFAQ(); break; }
//     case 'logostrip': { $Cod = $this->PgLogostrip(); break; }
//     case 'sitelogo':  { $Cod = $this->PgSitelogo(); break; }
     case 'reports':   { $Cod = $this->PgReports(); break; }
     case 'consultant':{ $Cod = $this->PgConsultant(); break; }
     default:          { $Cod = $this->PgHome(); break; }
    }
  return $Cod;
 }


 /**
  * Сгенерировать контент модуля для админки
  * 
  * @return string Возвращает HTML-код
  */
 public function AjaxContent()
 {
  switch ($_GET['act'])
    {
     //case 'hideph': { $Cod .= $this->PhotoConDel($_GET['id']); break; }
     default:       { $Cod .= 'Invalid request!'; break; }
    }
  return $Cod;
 }


 /**
  * Генерирует главную страницу модуля
  * 
  * @return string Возвращает HTML-код
  */
 public function PgHome()
 {
  global $CMSA;
  $Cod = '<p>Take a look in menu &uarr;</p>';
  /*
  $Cod .= '<pre>'.htmlspecialchars(print_r($this, true)).'</pre>
  <p>
    <a href="javascript:;" class="btn btn-primary" id="btnPopup"><i class="icon-table"></i> Sample popup</a>
  </p>
  ';*/

  return $Cod;
 }


 /**
  * Reports fillup page
  * 
  * @return string Возвращает HTML-код
  */
 public function PgReports()
 {
  global $CMSA;
  $S = '';

  $Parent = 'report_id';
  $ParentId = $_GET[$Parent];
  if ($ParentId > 0)
    {
     require_once('./../mods/IDentity/render_report.php');

     $Row = $CMSA->DB->GetRec('id_report', 'id='.$ParentId);

     $S .= "<div class='report'><h3>$Row[full_name] &lt;$Row[email]&gt;</h3><p>&larr; <a href=\"?mm=IDentity&sm=reports\">Назад к списку</a></p>";

     if ($Row['id'] <= 0) return '<div class="msgErr"><span>Ошибка: Отчет не найден</span></div>';

     $Row['human_type'] = $CMSA->DB->GetRec('id_human_type', 'id='.$Row['id_human_type']);

     $S .= RenderReport($Row);

     return $S.'</div>';
    }

  $TM = new TTableManager();
  //$TM->Set(TM_ORDER, 'this.ts_ins DESC');
  $TM->Set(TM_ORDER, 'this.full_name');
  $TM->Set(TM_SLAVE, $Parent);
  $TM->Set(TM_PAGEDIV, 20);
  $S .= $TM->Run('id_report');

  return $S;
 }


 /**
  * Page: consultants
  * 
  * @return string Возвращает HTML-код
  */
 public function PgConsultant()
 {
  global $CMSA;
  $S = '';
/*
  $S .= '
  <p>Список встреч назначеных в $UD[stages][2_set_meeting][meeting_meet_place] == my
 и $UD[stages][2_set_meeting][condition_complete][is_meeting_confirmed] == false
<br> в таблице id_meeting_day вылавливать новые уведомления по флагу is_confirmed_t09_30 (сумме флагов)
<br> и показать список встреч, которые требуют подтверждения. Написать инструкцию по работе.
<br>
<br> Событие "перед записью" проверять снятие флага is_t09_30, если ==0: очистить поля: t09_30="", is_confirmed_t09_30=0
<br>
</p>
  <br>'; */

  $Recs = $CMSA->DB->Exec2Arr('SELECT *, 
(5-(is_confirmed_t09_30 + is_confirmed_t11_30 + is_confirmed_t13_30 + is_confirmed_t15_30 + is_confirmed_t18_30)) AS `need_confirm` 
FROM id_meeting_day 
WHERE ts_del=0 
  AND (5-(is_confirmed_t09_30 + is_confirmed_t11_30 + is_confirmed_t13_30 + is_confirmed_t15_30 + is_confirmed_t18_30)) > 0 
ORDER BY ts_day', 'id');

  $Times = [
    't09_30' => '9:30',
    't11_30' => '11:30',
    't13_30' => '13:30',
    't15_30' => '15:30',
    't18_30' => '18:30'
    ];

  $S .= '<p><b>Встречи, которые требуют подтверждения ('. count($Recs) .')</b></p><ul>';
  foreach ($Recs as $Row)
    {
     foreach ($Times as $Field => $Time)
       {
        if (!$Row['is_confirmed_'.$Field] && $Row['is_'.$Field])
          {
           $Arr = Txt1Arr($Row[$Field]);
           $S .= '<li>'. $Arr['who'] .', когда: <b>'. date('d.m.Y ', $Row['ts_day']) . $Time .'</b>; место: <b>'. $Arr['place'] .'</b> &mdash; <a href="?mm=IDentity&sm=consultant&rnd='.rand(0,32767).'#tm;upd-6;rs-0">изменить</a></li>';
          }
       }
    }
  $S .= '</ul><p>&nbsp;</p>';

  $TM = new TTableManager();
//  $TM->Set(TM_ORDER, 'this.consultant');
//  $S .= $TM->Run('id_consultant');
  $TM->Set(TM_ORDER, 'this.ts_day DESC');
  $TM->Set(TM_EVNT_BEFORE_LOAD, 'OnBeforeLoad_Report'); // Callback имя функции
  $TM->Set(TM_EVNT_BEFORE_SAVE, 'OnBeforeSave_Report'); // Callback имя функции
  $S .= $TM->Run('id_meeting_day');

  $S .= '<h3>Инструкции по работе:</h3>
  <p><big>Добавление новых свободных времен встреч:</big></p>
  <ul>
    <li>1. Необходимо нажать на [<i class="icon-plus-sign"></i> Добавить]
    <br>2. В открывшейся форме, указать Консультана, тип встречи и дату. 
        После зная рассписание свободного времени в гугл календаре консультанта, отметить временные рамки, 
        которые заняты в дате указанной вверху формы. Для этого необходимо проставить галочки в полях "9:30 занято?", 
        где 9:30 - соответственный промежуток времени. ВАЖНО! Учитывайте, что длительность встречи 60-90 минут!
    <br>3. Нажать [<i class="icon-ok"></i> Добавить новую запись]
    <br>Финиш
    </li>
  </ul>
  <p>&nbsp;<br><big>Изменение подтверждение / встречи:</big></p>
  <ul>
    <li>1. В начале страницы имеется блок "Встречи, которые требуют подтверждения" он указывает на количество встреч, которые требуют подтвеорждения.
    <br>Примечание: подтверждать необходимо только встречи, которые будут проходить не в офисе/скайпе.
    </li>
    <li>2. В списке встреч которые необходимо подтвердить, клик на [изменить], откроется форма со всеми встречами тот день.
    </li>
    <li>3. Найти строчку время и встречу которую необходимо подтвердить ("??:?? встреча подтверждена?"). В такой строке будет отсутствовать "галочка".
    <br>Далее необходимо согласовать возможность данной встречи и после поставить "галочку" напротив строки "встреча подтверждена?"
    <br>Если место проведения встречи изменено, на скайп, тогда в поле описание переговоров необходимо изменить строку "place:" на
    <br><b>place: skype:</b>
    <br>Если изменился адрес встречи, тогда в той же строке, поменять адрес после <b>my:</b>
    <br>Если изменился адрес встречи на офис IDentity, тогда строке, указать: <b>place: office:</b>
    <br>Если встречу необходимо удалить/очистить, тогда очистите поле "описание встречи" и поставте "галочку" в поле "встреча подтверждена". Занятость времени выставляйте согласно расписания консультанта по гугл календарю
    <br>3. Нажать [<i class="icon-ok"></i> Сохранить]
    <br>4. Внести изменения в гугл календарь консультанта о встрече.
    <br>Финиш
    </li>
  </ul>  ';

/*
  $S .= '<br><hr><br>';
  $Times = [
    '9:30' => 't09_30',
    '11:30' => 't11_30',
    '13:30' => 't13_30',
    '15:30' => 't15_30',
    '18:30' => 't18_30'
    ];
  $Scpt = '
SELECT * 
 ,FROM_UNIXTIME(meeting_day.ts_day, "%d.%m.%Y") AS `dt_day`
FROM `id_meeting_day` AS meeting_day
WHERE meeting_day.ts_del = 0 
  AND meeting_day.id_consultant = 1
  AND meeting_day.ts_day >= UNIX_TIMESTAMP(STR_TO_DATE("'. date('d.m.Y') .'", "%d.%m.%Y"))
ORDER BY
  meeting_day.ts_day -- DESC
';
  $Recs = $CMSA->DB->Exec2Arr($Scpt, 'dt_day');
  $S .= '<pre>'.print_r($Recs,true).'</pre>';

  $S .= '<br><hr><br>';
  $S .= '<pre>'.print_r($Times,true).'</pre>';
*/
  return $S;
 }








 /**
  * Генерирует главную страницу модуля
  * 
  * @return cod Возвращает HTML-код
  */
 public function PgFAQ()
 {
  global $CMSA;
  $S = '';

  $S .= $this->InitIfNecessary('faq');

  $sParent = 'prntid';
  $IdParent = $_GET[$sParent];

  $TM = new TTableManager();

  if ($IdParent > 0)
    {
     $Parent = $CMSA->DB->Exec1st('SELECT faq_list.list_name as `item` FROM faq_list WHERE faq_list.id='.$IdParent);
     $S .= '<p>FAQ in group: <span style="color:maroon; font-weight:bold;">“'. $Parent['item'] .'”</span> <a href="?mm='.$_GET['mm'].'&sm='.$_GET['sm'].'" class="btn btn-small">Other groups &uarr;</a></p>';

     $TM->Set(TM_WHERE, 'this.id_list='.$IdParent);
     $TM->Set(TM_ORDER, 'this.pos,this.id');
     $TM->Set(TM_ORDERED_TABLE, 'pos');
     $TM->Set(TM_EVNT_BEFORE_LOAD, 'OnBeforeLoad_FAQ'); // Callback имя функции
     $TM->Set(TM_EVNT_BEFORE_SAVE, 'OnBeforeSave_FAQ'); // Callback имя функции
     $Head = 'Frequently Asked Questions';
     $S .= $TM->Run('faq_item', $Head);
    }
  else 
    {
     $TM->Set(TM_ORDER, 'this.pos,this.id');
     $TM->Set(TM_ORDERED_TABLE, 'pos');
     $TM->Set(TM_SLAVE, $sParent);           
     $S .= $TM->Run('faq_list');
    }

  $S .= '<p style="opacity:0.1">v1.35; templates in files</p>';
  return $S;
 }



 /**
  * Возвращает блок для popup'а
  * 
  * @return cod Возвращает HTML-код
  */
 /*private function Popup1()
 {
  global $CMSA;  

  $Cod = '
  <div id="wrap" style="padding:0 5px; min-width:750px; width:auto; height:auto; margin-bottom:0; min-height:auto; ">
  <p>Привет мир!</p>
  <pre>GET: '.print_r($_GET,true).'</pre>
  <pre>POST: '.print_r($_POST,true).'</pre>
  <p>
    <a href="javascript:;" class="btn btn-danger" id="btnCancel"><i class="icon-remove"></i> Close</a>
  </p>
  </div>
  ';

  return $Cod;
 }*/


}


// REPORT ------------------------------------------------------------


/**
 * Callback: срабатывает перед началом редактированием/созданием записи
 * 
 * @param array $Row - Ассоциативный массив данных для обработки
 * @param int $IdRec - ID записи в таблице
 * @param array $GET - Содежимое $_GET. Так как работает на ajax, то напрямую $_GET не содержит нужных данных
 * @return array Возвращает ассоциативный массив с обработанными данными
 */
function OnBeforeLoad_Report($Row, $IdRec, $GET)
{
 if (!$IdRec)
   { // New record
    //$Row['id_list'] = $GET['prntid'];
   }
 return $Row;
}
/**
 * Callback: срабатывает перез сохранением данных
 * 
 * @param array $Row - Ассоциативный массив данных для обработки
 * @param int $IdRec - ID записи в таблице
 * @param array $GET - Содежимое $_GET. Так как работает на ajax, то напрямую $_GET не содержит нужных данных
 * @return array Возвращает ассоциативный массив с обработанными данными
 */
function OnBeforeSave_Report($Row, $IdRec, $GET)
{
 if (!$IdRec) return $Row;
 $Times = [
   't09_30' => '9:30',
   't11_30' => '11:30',
   't13_30' => '13:30',
   't15_30' => '15:30',
   't18_30' => '18:30'
   ];


 global $CMSA;
 require_once('../mods/SiteUsers/usersctrl.php');
 $User = new TUsersCtrl([ 'db' => $CMSA->DB ]);


 $Arr = [];
 foreach ($Times as $Field => $Time)
   {
    //if (!$Row['is_confirmed_'.$Field] && $Row['is_'.$Field])
      {
       $Arr = Txt1Arr($Row[$Field]);
      }

    if ($Arr['user_id'] > 0)
      {
       $UD = $User->GetData($Arr['user_id']); // Get UserData

       $Stage = $Arr['stage'];
       if (is_array($UD['stages'][$Stage]))
         { // есть нужный stage
          if (!strlen($Arr['who']))
            {
             $UD['stages'][$Stage]['condition_complete']['is_meeting_set'] = false; // встреча НЕ назначена
             unset($UD['stages'][$Stage]['condition_complete']['is_meeting_confirmed']);
             unset($UD['stages'][$Stage]['meeting_at']);
             unset($UD['stages'][$Stage]['meeting_skype']);
             unset($UD['stages'][$Stage]['meeting_addr']);
             unset($UD['stages'][$Stage]['meeting_meet_place']);
             unset($UD['stages'][$Stage]['meeting_consultant']);
             unset($UD['stages'][$Stage]['meeting_id_consultant']);
             unset($UD['stages'][$Stage]['meeting_id']);
             unset($UD['stages'][$Stage]['meeting_table']);
             $ret = $User->SetData($UD, $Arr['user_id']); // Save updated UserData
            }
          else 
            {
             $MeetPlace = explode(':', $Arr['place']);

             $UD['stages'][$Stage]['condition_complete']['is_meeting_set'] = true; // встреча назначена

             if ($Row['is_confirmed_'.$Field] > 0)
                $UD['stages'][$Stage]['condition_complete']['is_meeting_confirmed'] = true; // встреча подтверждена
             else
                $UD['stages'][$Stage]['condition_complete']['is_meeting_confirmed'] = false; // встреча НЕ подтверждена

             $UD['stages'][$Stage]['meeting_at'] = date('d.m.Y', strtotime($Row['ts_day'])) .' '. $Time;
             $UD['stages'][$Stage]['meeting_skype'] = $Arr['skype'];
             $UD['stages'][$Stage]['meeting_addr'] = trim($MeetPlace[1]);
             $UD['stages'][$Stage]['meeting_meet_place'] = trim($MeetPlace[0]);
             $UD['stages'][$Stage]['meeting_consultant'] = ''; // null
             $UD['stages'][$Stage]['meeting_id_consultant'] = $Row['id_consultant'];
             $UD['stages'][$Stage]['meeting_id'] = $IdRec;
             $UD['stages'][$Stage]['meeting_table'] = 'id_meeting_day';

             $ret = $User->SetData($UD, $Arr['user_id']); // Save updated UserData
            }
         }
      }
   }
 //file_put_contents('../mods/IDentity/~.txt', print_r($Arr,true));
/*
      "condition_complete": {
        "is_meeting_set": true,
        "is_complete": false
      },

    [t18_30] => Array
        (
            [who] => Oleg Potapov
            [place] => my:мое место
            [skype] => dreadnaut-
            [user_id] => 69
            [stage] => 2_set_meeting
        )

)
      "meeting_at": "25.09.2017 13:30",
      "meeting_skype": "dreadnaut-",
      "meeting_addr": "",
      "meeting_meet_place": "office",
      "meeting_consultant": null,
      "meeting_id_consultant": "1",
      "meeting_id": "6",
      "meeting_table": "id_meeting_day"
*/
 return $Row;
}


// ????????? ------------------------------------------------------------

function Txt1Arr($Str)
{
 $Out = [];
 $Lines = explode("\r\n", $Str);
 foreach ($Lines as $Line)
   {
    $Arr = explode(':', $Line, 2);
    $Arr[0] = trim($Arr[0]);
    $Arr[1] = trim($Arr[1]);
    if (strlen($Arr[0]))
      {
       $Out[trim($Arr[0])] = trim($Arr[1]);
      }
   }
 return $Out;
}
