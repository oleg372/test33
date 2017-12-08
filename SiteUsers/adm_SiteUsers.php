<?php
/**
 * @name SiteUsers
 * @copyright 2013-2014, Potapov studio
 * @link http://potapov.com.ua/cms/siteusers/
 */

class TAdmSiteUsers
{
 /**
  * Приватные переменные
  */
 private $L; // массив текстровых строк, языковой поддержки

 
 /**
  * Публичные переменные
  */
 public  $Ver;   // Текущая версия модуля
 public  $WorkPath; 

 
 /**
  * конструктор объека
  * @param string $WPath - Путь к папке модуля, передается из вне
  * @return void
  */
 public function __construct($WPath)
 {
  $this->WorkPath = $WPath;
  $this->Ver = '1.0';
 }
 
 
 /**
  * Получить заговолов страницы
  *
  * @return string Возвращает заголовок для страницы
  */
 public function GetTitle()
 {
  return 'Управление зарегистрированными пользователями';
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
  $S .= '<script type="text/javascript" src="'.$this->WorkPath . 'adm_SiteUsers.js'.'"></script>'."\r\n"
    .'<script src="../3th/filtertable/jquery.filtertable.min.js"></script>'."\r\n"
    ;

/*
  // Без менеджера таблиц
  $Cod = '
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="../3th/fb2/lib/jquery.mousewheel-3.0.6.pack.js"></script>
<link rel="stylesheet" href="../3th/fb2/jquery.fancybox.css?v=2.0.6" type="text/css" media="screen" />
<script type="text/javascript" src="../3th/fb2/jquery.fancybox.pack.js?v=2.0.6"></script>
<script type="text/javascript">
'.str_replace('{aj}', $ajl, file_get_contents($this->WorkPath . 'adm_SiteData.js')) .'
</script>
';*/


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
     default: { $Cod = $this->PgHome(); break; }
    }
  return $Cod;
 }


 /**
  * Сгенерировать контент модуля для админки
  * 
  * @return cod Возвращает HTML-код
  */
 public function AjaxContent()
 {
  switch ($_GET['act'])
    {
//   case 'updobj':  { $Cod .= $this->PopupUpdateObj($_POST['tmid']); break; } // Popup управления
     case 'srch_report': { $Cod .= $this->ajSrchReport($_POST); break; }
     case 'share_report': { $Cod .= $this->ajShareReport($_POST); break; }
     default:        { $Cod .= 'Invalid request!'; break; }
    }
  return $Cod;
 }


 /**
  * Страница: главная, профили пользователей
  * 
  * @return cod Возвращает HTML-код
  */
 public function PgHome()
 {
  global $CMSA;
  $S = '';

  if ($_GET['user_id'] > 0)
    {
     return $this->PgUserCtrl(sprintf('%d', $_GET['user_id']));
    }

  $Network = $CMSA->DB->Exec2Arr('SELECT * FROM user_network WHERE 1', 'network_id');

  //$Recs = $CMSA->DB->Exec2Arr('SELECT * FROM user_profile WHERE ts_del=0 ORDER BY ts_ins DESC', 'user_id');
  $Recs = $CMSA->DB->Exec2Arr('SELECT * FROM user_profile WHERE ts_del=0 AND is_new_one=0  ORDER BY last_name, first_name', 'user_id');

  $S .= '
    <div id="tblPages" class="tblPages">
      <div class="formDef nopad">
        <input type="search" placeholder="Поиск" class="str w250 hint" value="" id="reg-user-search" style="margin-top:6px;">
        &nbsp; в списке: '. count($Recs) .'
      </div>
    </div>

    <table class="tView reg-users" id="reg-users"><thead>
    <th>ID</td>
    <th>&nbsp;</td>
    <th>Имя Фамилия</th>
    <th>Статус</th>
    <th>Ник</th>
    <th>E-mail</th>
    <th>Город<br>Страна</th>
    <th>Провайдер</th>
    <th class="r act">Регистр.</th>
    </thead><tbody>';
  foreach ($Recs as $Row)
    {
     $Row['network'] = $Network[ $Row['network_id'] ]['network'];

     if ((time() - $Row['ts_last']) < (10 * 60))  // меньше 10 мин назад, значи еще онлайн
       {
        $Status = '<span class="online">Онлайн</span>';
       }
     else if ($Row['ts_last'] > 10000)
       {
        $ago = $CMSA->tsDiff($Row['ts_last'], 0);
        $Status = '<span class="offline" style="font-weight:normal">В сети '. $ago .'<br><span> '. date('d.m.Y H:i', $Row['ts_last']) .' </span></span>';
       }
     else { $Status = ''; }


     //$Row['sn'] = json_decode($Row['sn'], true); { if (!is_array($Row['sn'])) $Row['sn'] = []; }
     $S .= '<tr>
     <td class="act r">'. $Row['user_id'] .'</td>
     <td class="act c"><img src="'. $Row['photo'] .'"></td>
     <td><a href="?mm=SiteUsers&sm=&user_id='. $Row['user_id'] .'" class="local">'. $Row['last_name'] .' '. $Row['first_name'] .'</a></td>
     <td>'. $Status .'</td>
     <td>'. $Row['nickname'] .'</td>
     <td>'. $Row['email'] .'</td>
     <td>'. $Row['city'] .'<br>'. $Row['country'] .'</td>
     <td>'. $Row['network'] .'</td>
     <td class="r">'. date('d.m.Y', $Row['ts_ins']) .'</td>
     </tr>';
    }
  $S .= '</tbody></table>';
  return $S;
 }


 /**
  * Страница просмотра и управлением профилем пользователя
  * 
  * @return cod Возвращает HTML-код
  */
 public function PgUserCtrl($UserId)
 {
  global $CMSA;
  $S = '';

  if ($_POST['form'] == 'change_stage')
    { // заменить этап у пользователя
     $S .= $this->User_ChangeStage($_POST);
    }

  $Network = $CMSA->DB->Exec2Arr('SELECT * FROM user_network WHERE 1', 'network_id');

  $User = $CMSA->DB->GetRec('user_profile', 'user_id='. $UserId);
  if ($User['user_id'] <= 0) return '<div class="msgErr"><span>Error: User by ID not found.</span></div>';

  $User['sn'] = json_decode($User['sn'], true);

  $UD = $CMSA->DB->GetRec('user_data', 'user_id='. $UserId, 'data');
  $UD = json_decode($UD, true);

  $OwnReport = $CMSA->DB->Exec1st ('SELECT id, ts_ins, ts_upd, owner_id, user_id, full_name, email, id_human_type, data FROM id_report WHERE user_id='.  $UserId .' ');
  $Reports   = $CMSA->DB->Exec2Arr('SELECT id, ts_ins, ts_upd, owner_id, user_id, full_name, email, id_human_type, data FROM id_report WHERE owner_id='. $UserId .' AND user_id!='. $UserId .' ORDER BY full_name');

  if ((time() - $User['ts_last']) < (10 * 60))  // меньше 10 мин назад, значи еще онлайн
    {
     $Status = '<span class="online">Онлайн</span>';
    }
  else 
    {
     $ago = $CMSA->tsDiff($User['ts_last'], 0); // https://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
     $Status = '<span class="offline">Был в сети '. $ago .' назад &nbsp; (<span style="font-weight:normal"> '. date('d.m.Y H:i', $User['ts_last']) .' </span>)</span>';
    }


  $sReports = '';
  if ($OwnReport['id'] > 0)
     $sReports .= '<p>Личный отчет <b>'. $OwnReport['full_name'] .'</b> от '. date('d.m.Y', $OwnReport['ts_ins']) .', <a target="_blank" href="?mm=IDentity&sm=reports&report_id='. $OwnReport['id'] .'">открыть</a></p>';
  else 
     $sReports .= '<p>Личный отчет <b>нету</b></p>';
  $sReports .= '<p>Отчеты сотрудников:</p><ul>';
  foreach ($Reports as $Report)
    {
     $sReports .= '<li><a target="_blank" href="?mm=IDentity&sm=reports&report_id='. $Report['id'] .'">'.$Report['full_name'].'</a> от '. date('d.m.Y', $Report['ts_ins']) .'</li>';
    }
  $sReports .= '</ul>';


  $sRoadMap = '<ul class="square">';
  $sRefRoadMap = '';
  foreach ($UD['road_map'] as $RM)
    {
     $tmp = '';
     if ($UD['stages'][$RM]['condition_complete']['ts_complete'] > 0) $tmp .= ' &nbsp; &rarr; завершен: '. date('d.m.Y H:i', $UD['stages'][$RM]['condition_complete']['ts_complete']);

     if ($RM == $UD['current_stage'])
       { $sRoadMap .= '<li><b>'. $RM .'</b> '.$tmp.'</li>'; $sRefRoadMap .= '<option value="'.$RM.'" selected>'.$RM.'</option>'; }
     else 
       { $sRoadMap .= '<li>'. $RM . $tmp.'</li>'; $sRefRoadMap .= '<option value="'.$RM.'">'.$RM.'</option>';  }
    }
  $sRoadMap .= '</ul>';



  $Data = [
    'user_id' => $User['user_id'],
    'photo' => $User['photo'],
    'last_name' => $User['last_name'],
    'first_name' => $User['first_name'],
    'is_new_one' => ($User['is_new_one'] ? 'НОВЫЙ ПОЛЬЗОВАТЕЛЬ! НЕ ЗАВЕРШИЛ РЕГИСТРАЦИЮ': ''),
    'email' => $User['email'],
    'country' => $User['country'],
    'city' => $User['city'],
    'network' => $Network[ $User['network_id'] ]['network'],
    'social_profile' => $User['sn']['profile'],
    '_phone' => $User['sn']['_phone'],
    '_bDate' => $User['sn']['_bDate'],
    '_bTime' => $User['sn']['_bTime'],
    '_bCountry' => $User['sn']['_bCountry'],
    '_bCity' => $User['sn']['_bCity'],
    'first_reg' => date('d.m.Y', $User['ts_ins']),
    'status' => $Status,

    'current_stage' => $UD['current_stage'],
    'road_map' => $sRoadMap,
    'ref_road_map' => $sRefRoadMap,

    'sn' => print_r($User['sn'], true),
    'now_stage' => print_r($UD['stages'][ $UD['current_stage'] ], true),
    'reports' => $sReports,
    'ud' => print_r($UD,true),

    'shared-with-me' => $this->SharedWithMe($User['user_id']),

    'url' => '?mm=SiteUsers&sm=&user_id='. $UserId
    ];

  $Tmpl = file_get_contents($this->WorkPath.'tmpl_user_ctrl.html');

  $S .= $CMSA->Gen($Tmpl, $Data);

  //$S .= '<hr>Пользователь+соцсети<pre>'.print_r($User['sn'],true).'</pre>';
  //$S .= '<hr>UserData<pre>'.print_r($UD,true).'</pre>';
  //$S .= '<hr>Отчет о пользователе<pre>'.print_r($OwnReport,true).'</pre>';
  //$S .= '<hr>Отчеты сотрудников<pre>'.print_r($Reports,true).'</pre>';

  return $S;
 }


 /**
  * объектры которыми со мной поделились пользователи.
  */
 public function SharedWithMe($IdUser)
 {
  global $CMSA;
  $S = '';

  $S = '';
 
  $Recs = $CMSA->DB->Exec2Arr('SELECT 
  sw.id,
  sw.ts_upd,
  sw.owner_id,
  sw.email,
  sw.user_id,
  sw.shared,
  users.last_name,
  users.first_name,
  users.email
FROM `shared_with` AS `sw` 
INNER JOIN `user_profile` AS `users` ON users.user_id = sw.owner_id
WHERE sw.ts_del = 0
  AND sw.user_id = '. $IdUser .'
ORDER BY sw.ts_upd DESC', 'id');

  if (!count($Recs)) return 'На данный момент, никто не поделился документами с Вами.';

  foreach ($Recs as $Key => $Items)
    {
     $Recs[$Key]['shared'] = json_decode($Recs[$Key]['shared'], true);
     if (!is_array($Recs[$Key]['shared'])) { $Recs[$Key]['shared'] = []; }

     foreach ($Recs[$Key]['shared'] as $Object => $Row)
       {
        if ($Object == 'personal_reports')
          { // поделились отчетами
           $S .= '<div class="shared-user"><b>'. $Items['first_name'] .' '. $Items['last_name'] .'</b>, поделился отчетами:</div>';
           $S .= $this->Shared_Reports($Row);
          }
       }
    }

/*
{
  "personal_reports": {
    "handler": "?",
    "table": "id_report",
    "key_field": "id",
    "shared": {
      "1": "ro",
      "2": "ro",
      "3": "ro",
      "id": "ro/rw"
    }
  }
}
*/

  return $S;
 }
 /**
  * Shared reports
  */
 public function Shared_Reports($In)
 {
  global $CMSA;
  $Table = $In['table'];
  $KeyField = $In['key_field'];
  $Shared = $In['shared'];

  $Ids = implode(array_keys($Shared), ','); // список ID отчетов которыми поделились
  $Recs = $CMSA->DB->Exec2Arr('SELECT id, owner_id, user_id, full_name, email, id_human_type 
  FROM id_report WHERE id IN ('. $Ids .') ORDER BY full_name', 'id');
  if (!count($Recs)) return '<div class="no-recs"> нет записей </div>';

  $S .= '<ul>';
  foreach ($Recs as $Row)
    {
     $S .= '<li><a href="?mm=IDentity&sm=reports&report_id='. $Row['id'] .'">'. $Row['full_name'] .'</a> <a data-id-report="'.$Row['id'].'" title="Закрыть доступ" href="javascript:;" style="text-decoration:none" class="js-remove-share-report"><i class="icon-remove-sign hot"></i></a></li>';
    }
  $S .= '</ul>';
/*
  $S .= TRC($Recs);
  $S .= '<pre><b>'.$In.'</b>: '. print_r($Table,true) .'</pre>';
  $S .= '<pre><b>'.$In.'</b>: '. print_r($KeyField,true) .'</pre>';
  $S .= '<pre><b>'.$In.'</b>: '. print_r($Shared,true) .'</pre>';
*/
  return $S;
 }


 /**
  * Изменить этап пользователя
  */
 public function User_ChangeStage($In)
 {
  global $CMSA;
  $S = '';

  require_once('../mods/SiteUsers/usersctrl.php');
  $User = new TUsersCtrl([ 'db' => $CMSA->DB ]);
  $UD = $User->GetData($In['user_id']);

  $UD['current_stage'] = $In['new_stage'];
  // обнуть выполнения stage в который перевели
  foreach ($UD['stages'][ $In['new_stage'] ]['condition_complete'] as $key => $val)
    {
     if ($key[0] == 'i' && 
         $key[1] == 's' && 
         $key[2] == '_')
       {
        $UD['stages'][ $In['new_stage'] ]['condition_complete'][$key] = false;
       }
    }
  unset($UD['stages'][ $In['new_stage'] ]['condition_complete']['ts_complete']); // убарть timestamp о завершении

  $ret = $User->SetData($UD, $In['user_id']);

  if (!$ret) $S .= '<div class="msgErr"><span>Ошибка в изменении Stage (этапа) пользователя.</span></div>';
  else       $S .= '<div class="msgOk"><span>Stage (этап) пользователя изменен успешно.</span></div>';

  //$S .= TRC($_POST);
  //$S .= TRC($User);
  //$S .= TRC($UD);
  return $S;
 }
 

 /**
  * Поиск отчетов
  */
 public function ajSrchReport($In)
 {
  global $CMSA;
  $Out = ['err' => 0, 'error' => '', 'html' => ''];
  $S = '';

  $Tmpl = '<p>
  <a href="javascript:;" class="btn btn-small js-share-report" data-id-report="{id_report}" data-id-owner="{owner_id}">Добавить</a> 
  {full_name}
  <a href="?mm=IDentity&sm=reports&report_id={id_report}" target="_blank">открыть отчет <i class="icon-external-link"></i></a>, 
  владелец отчета, пользователь: 
  <a href="?mm=SiteUsers&sm=&user_id={owner_id}" target="_blank">{owner}</a>
  </p>';

  $Que = trim($In['q']);

  $Scpt = 'SELECT report.id AS `id_report`, report.ts_ins, report.ts_upd, report.owner_id, report.user_id, report.full_name, report.email, CONCAT(user_profile.last_name, " ", user_profile.first_name) AS `owner`
  FROM `id_report` AS `report`
  LEFT OUTER JOIN user_profile ON user_profile.user_id = report.owner_id
  WHERE report.ts_del=0
  AND report.full_name LIKE "'. $Que .'%" OR report.full_name LIKE "% '. $Que .'%"';

  $Recs = $CMSA->DB->Exec2Arr($Scpt, '');
  foreach ($Recs as $Row)
    {
     //$Row['full_name'] = preg_replace("/\p{L}*?".preg_quote($Que)."\p/ui", "<b>$0</b>", $Row['full_name']);
     //$Row['full_name'] = str_ireplace($Que, "<b>".$Que."</b>",$Row['full_name']);//preg_replace("|($keyword)|Ui", "<b>$1</b>", $str);
     $S .= $CMSA->Gen($Tmpl, $Row);
    }

  $Out['html'] = $S;
  return json_encode($Out);
 }

 
 /**
  * Расшаривание отчета / удаление отчета
  */
 public function ajShareReport($In)
 {
  global $CMSA;
  $Out = ['err' => 0, 'error' => '', 'html' => '', 'msg' => ''];
  $S = '';

  $IdReport = sprintf('%d', $In['id_report']);
  $IdUser   = sprintf('%d', $In['id_user']);

  $Report = $CMSA->DB->GetRec('id_report', 'id='.$IdReport);
  if ($Report['id'] <= 0) { $Out['msg'] = 'Отчет не найден.'; return json_encode($Out); }

  if ($Report['owner_id'] <= 0) { $Out['msg'] = 'У данного отчета не указан владелец. Поделиться не возможно.'; return json_encode($Out); }

  // Найти: делился ли данный  owner_id раньше с пользователем $IdUser отчетами
  $Scpt = 'SELECT * FROM `shared_with` WHERE owner_id = '. $Report['owner_id'] .' AND user_id = '. $IdUser;
  $Share = $CMSA->DB->Exec1st($Scpt);
  if ($Share['id'] <= 0)
    { // Не делился ранее, новая запись
     $Arr = json_decode('{
  "personal_reports": {
    "handler": "?",
    "table": "id_report",
    "key_field": "id",
    "shared": {}
  }
}', true);

     // Добавить доступ к отчету
     $Arr['personal_reports']['shared'][$IdReport] = 'ro';

     // Создать запись
     $CMSA->DB->SetFV([
       'owner_id' => $Report['owner_id'],
       'email' => '', // можна поднять из user_profile.email/alogin
       'user_id' => $IdUser,
       'shared' => json_encode($Arr)
       ]);
     $CMSA->DB->MakeInsert('shared_with'); //, $AutoFillSpec = true, $IsQuotes = true)
     if ($CMSA->DB->ErrNo) { $Out['msg'] = 'Ошибка: MakeInsert(): '.$CMSA->DB->Error; return json_encode($Out); }

    }
  else
    { // Делился ранее, изменить запись
     $Arr = json_decode($Share['shared'], true);
     if (!is_array($Arr)) { $Out['msg'] = 'Некоректная структура JSON в shared_with.shared'; return json_encode($Out); }

     if ($In['unshare'] > 0)
       { // Отменить доступ к отчету
        unset($Arr['personal_reports']['shared'][$IdReport]);
       }
     else 
       { // Добавить доступ к отчету
        $Arr['personal_reports']['shared'][$IdReport] = 'ro';
       }

     if (!count($Arr['personal_reports']['shared']))
       { // Удалить запись, т.к. нет отчетов которыми поделился "владелец"
        $CMSA->DB->MakeDelete('shared_with', 'id='. $Share['id']);
        if ($CMSA->DB->ErrNo) { $Out['msg'] = 'Ошибка: MakeDelete(): '.$CMSA->DB->Error; return json_encode($Out); }
       }
     else
       {
        // Сохранить запись
        $CMSA->DB->SetFV([
          'shared' => json_encode($Arr)
          ]);
        $CMSA->DB->MakeUpdate('shared_with', 'id='. $Share['id'], 0, true, true);
        if ($CMSA->DB->ErrNo) { $Out['msg'] = 'Ошибка: MakeUpdate(): '.$CMSA->DB->Error; return json_encode($Out); }
       }
     //$Out['msg'] = 'updated';
    }

  //$Out['msg'] .= TRC($Share);

  return json_encode($Out);
 }


}

