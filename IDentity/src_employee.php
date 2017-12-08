<?php
 /**
  * Список - раздел "команда"
  */
 function Employee($In)
 {
  global $WebApp;
  $OwnerId = $WebApp->User->ID;

//  if ($_SERVER['REMOTE_ADDR'] == '95.158.59.135') {
//  $OwnerId = 89; // TODO REMOVE
//  }

  $Recs = $WebApp->DB->Exec2Arr('
    SELECT id, owner_id, user_id, full_name, email, id_human_type 
    FROM id_report 
    WHERE /* user_id != '. $OwnerId .' AND */ owner_id='. $OwnerId .' 
    ORDER BY full_name', 'id');

  $RecsShared = $WebApp->DB->Exec2Arr('SELECT
  sw.id, 
  sw.owner_id, 
  sw.email, 
  sw.user_id, 
  user.last_name,
  user.first_name,
  user.email,
  sw.shared
FROM `shared_with` AS `sw`
INNER JOIN user_profile AS `user` ON user.user_id = sw.user_id
WHERE sw.ts_del = 0
  AND sw.owner_id = '.$OwnerId.'
ORDER BY user.last_name, user.first_name', 'id');
  foreach ($RecsShared as $Key => $Row)
    {
     $RecsShared[$Key]['shared'] = json_decode($Row['shared'], true);
     if (!is_array($RecsShared[$Key]['shared'])) $RecsShared[$Key]['shared'] = [];
    }

  //$TmplEmployee = self::GetTemplate('empoloyee_list');
  $TmplEmployeeItem = $WebApp->GetTemplate('empoloyee_list_item');
  //$TmplEmployeeItemAccess = $WebApp->GetTemplate('empoloyee_list_item_access');

//if ($_SERVER['REMOTE_ADDR'] == '95.158.59.135'){$Recs=[];}



  $S = '';
  foreach($Recs as $Row)
    {
     $Row['access'] = '&mdash;';
     if ($Row['user_id'] == $OwnerId) $Row['full_name'] .= ' <b>(Вы)</b>';

     $Row['access'] = EmployeeSharedWith($RecsShared, $Row['id']); // список с кем поделился данным отчетом

     $S .= $WebApp->Render($TmplEmployeeItem, $Row);
    }

  $t1 = '';
  $t2 = '';
  if ($_SERVER['REMOTE_ADDR'] == '95.158.59.135')
    {
     $t1 = $WebApp->GetTemplate('empoloyee_invite');
     $t2 = $WebApp->GetTemplate('empoloyee_share');
    }

  if (!count($Recs))
    { // нет приглашенных сотрудников
     $t2 = '';
    }




  $S = $WebApp->Render($WebApp->GetTemplate('empoloyee_list'), [
    '_qty' => count($Recs),
    'view_employees_msg' => (count($Recs) ? '<p>Выберите сотрудника для просмотра отчета</p>' : ''),
    'items' => $S,
    'invite_form' => $t1,
    'share_form' => $t2
    ]);

  return $S;
 }


 /**
  * Список - раздел "команда"
  */
 function EmployeeSharedWith(&$Shared, $IdReport)
 {
  global $WebApp;

  $S = '';

  foreach ($Shared as $Item)
    {
     foreach ($Item['shared']['personal_reports']['shared'] as $Id => $Access)
       {
        //$S .= TRC($Item['personal_reports']['shared']);
        if ($Id == $IdReport)
          {
           $S = $WebApp->Render($WebApp->GetTemplate('empoloyee_list_item_access'), [
             'id_share' => $Item['id'],
             'id_report' => $IdReport,
             'user_id' => $Item['user_id'],
             'full_name' => $Item['last_name'] .' '. $Item['first_name']
             ]);
          }
       }
    }
  
  return $S;
 }

/*
 [5] => Array
        (
            [id] => 5
            [owner_id] => 89
            [email] => oleg@potapov.com.ua
            [user_id] => 69
            [last_name] => Potapov
            [first_name] => Oleg
            [shared] => {
  "personal_reports": {
    "handler": "?",
    "table": "id_report",
    "key_field": "id",
    "shared": {
      "1051": "ro"
    }
  }

{
  "personal_reports": {
    "handler": "?",
    "table": "id_report",
    "key_field": "id",
    "shared": {
      "1010": "ro",
      "1038": "ro",
      "1028": "ro"
    }
  }
}

SELECT
  sw.id, 
  sw.owner_id, 
  sw.email, 
  sw.user_id, 
  sw.shared,
  user.last_name,
  user.first_name,
  user.email
FROM `shared_with` AS `sw`
INNER JOIN user_profile AS `user` ON user.user_id = sw.user_id
WHERE sw.ts_del = 0
  AND sw.owner_id = 69
ORDER BY user.last_name, user.first_name

*/