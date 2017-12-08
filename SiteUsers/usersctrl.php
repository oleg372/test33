<?php
/*----------
 * op!CMS. Backend/frontend core
 * Все права защищены (c) 2017 "Potapov development" http://potapovdev.com
 * Полное или частичное копирование исходных кодов программы запрещено, 
 * согласование использования исходных кодов программы производится с их авторами.
 * Не выполнение авторских прав карается законодательством Украины!
 * 
 * Copyright (c) 2017 "Potapov development" http://potapovdev.com
 * Full or partial copying of program source code is prohibited,
 * Approval of the program source code is made by the authors.
 * No copyright is punishable by execution of the laws of Ukraine!
 */ 

/**
 * @name Users control
 * @desc Users control, sign in thru social networks
 * @version 1.04, 02.08.2017 (begins 02.08.2017)
 * @author Potapov Oleg
 * @copyright 2017, Potapov studio
 * @link http://potapovdev.com/cms
 */

/*
<script type="text/javascript" src="/core/mods/catalog/usersctrl.js"></script>
<link  href="/core/mods/catalog/usersctrl.css" type="text/css" rel="stylesheet" />
<script src="//ulogin.ru/js/ulogin.js"></script>


define('REVIEW_DELETE',  0);
define('REVIEW_APPROVE', 1);

define('REVIEW_LIKE',  2);
define('REVIEW_DISLIKE',  3);
*/
class TUsersCtrl
{
 /**
  * Private vars
  */
 private $L; // Language phases
 public  $DB; // TDB class
 private $DBSet; // Database settings
 private $DefCookie; // Default cookie name
 private $Data; // Additional (custom) data depends from project (DB::user_data.data)
                // Private only. Use ::GetData(), ::SetData()

 /**
  * Public vars
  */
 public $Profile; // Current authorized user profile
 public $ID; // Current authorized user ID
 public $SID; // Current Session ID (SID)

 /**
  * Constructor
  * @param TDB $DB  - Resource to TDB
  * @param string $DBSet - Database structure settings
  * @param string $sLang - Language support (ru/en/ua)
  */
 public function __construct($In/*$DB, $Path = '', $DBSet = [], $sLang = 'en'*/)
 {
  if (!isset($In['lang'])) { $In['lang'] = 'en'; }

  // Default data base structure settings
  $this->DBSet = [
   'table' => 'user_profile'
  ,'user_id' => 'user_id'
  ,'sid' => 'sid'
  ,'ts_last' => 'ts_last'
  ,'network_id' => 'network_id'
  ,'uid' => 'uid'
  ,'first_name' => 'first_name'
  ,'last_name' => 'last_name'
  ,'nickname' => 'nickname'
  ,'email' => 'email'
  ,'photo' => 'photo'
  ,'city' => 'city'
  ,'country' => 'country'
  ,'sn' => 'sn'
  ,'is_new_one' => 'is_new_one'
  ];

  $this->Profile = null;
  $this->ID = 0;
  $this->DB = $In['db'];
  if (!is_array($In['db_set'])) { $In['db_set'] = array(); }
  $this->DBSet = array_merge($this->DBSet, $In['db_set']);
  $this->Path = './';
  if ($In['path']) { $this->Path = $In['path']; }

  if (strlen($In['sid']))
    { // try to auth user by SID, on success load's profile in $this->Profile
     $this->AuthCheck(['sid' => $In['sid']]);
    }
 }


 /**
  *
  *
  */
 public function AjaxRoute($Cmd)
 {
  $Out = [];
  switch ($Cmd)
    {
     case 'popup':  { $Out = $this->GetPopup($_POST); break; }
     case 'signin': { $Out = $this->Signin($_POST);   break; }
    }

  return json_encode($Out);
 }


 /**
  * Return HTML popup for signin
  *
  */
 public function GetPopup($In = [])
 {
  $Out = ['err' => 0, 'html' => ''];

  $Out['html'] = file_get_contents($this->Path .'usersctrl_t_popup.html');

  return $Out;
 }


 /**
  * Return HTML auth state with sign out or buttons for sign in
  * for current auth profile
  */
 public function GetAuthState($In = [])
 {
  $Out = ['err' => 0, 'html' => ''];

  if ($this->Profile[$this->DBSet['user_id']] <= 0)
    { // Not authorized 
     $Out['html'] = file_get_contents($this->Path .'usersctrl_t_state_0.html');
    }
  else
    { // Authorized user
     $Out['html'] = file_get_contents($this->Path .'usersctrl_t_state_1.html');
     $Out['html'] = str_replace(array(
       '{photo}'
      ,'{first_name}'
      ,'{last_name}'), array(
       $this->Profile[$this->DBSet['photo']]
      ,$this->Profile[$this->DBSet['first_name']]
      ,$this->Profile[$this->DBSet['last_name']]), $Out['html']);
    }

  return $Out;
 }


 /**
  * Check for authorized user by SID in cookie
  * If SID correct, load user profile as current authorized
  *
  * @param string [sid] - Session ID
  * @return array On success [err] = 0. In [user], return user profile, otherwise [user] = null - mean: no currenct actived user by "in" SID.
  */
 public function AuthCheck($In)
 {
  $Out = ['err' => 0, 'user' => null];

  // Check for SID in DB
  if (!strlen($In['sid'])) { return ['err' => 21, 'error' => 'SID is not set.', 'user' => null]; }

  // SID found
  $User = $this->DB->GetRec($this->DBSet['table'], $this->DBSet['sid'].'="'. $In['sid'] .'"');

  // IMITATE USER!
  //if ($_SERVER['REMOTE_ADDR'] == '95.158.59.135') { 
  //$User = $this->DB->GetRec($this->DBSet['table'], 'user_id=124'); }

  if ($User[$this->DBSet['user_id']] <= 0)
    { // SID not found
     return ['err' => 22, 'error' => 'SID isn\'t found.', 'user' => null];
    }

  $User[$this->DBSet['sn']] = json_decode($User[$this->DBSet['sn']], true);
  if (!is_array($User[$this->DBSet['sn']])) { $User[$this->DBSet['sn']] = []; }

  $this->Profile = $User;
  $this->Profile['id'] = $User[$this->DBSet['user_id']]; // put ID in id
  $this->ID            = $User[$this->DBSet['user_id']]; // put ID in class var
  $this->SID           = $User[$this->DBSet['sid']]; // current SID

  // Load custom user data
  $this->Data = $this->GetData(0, true);

  // Update timestamp of last activity
  $TS = time();
  //$User[$this->DBSet['ts_last']] = $TS;
  $this->DB->Exec('UPDATE '. $this->DBSet['table'] .' SET '. $this->DBSet['ts_last'] .' = '. $TS .' WHERE '. $this->DBSet['user_id'] .' = '. $this->ID);

  return $Out;
 }


 /**
  * Authorize user, return user profile
  *
  * @param string [token] - Token for ulogin.ru
  * @return array On success [err] = 0
  */
 public function Signin($In)
 {
  $Out = ['err' => 0];

  if (!strlen($In['token'])) 
    { 
     // Check for cookie 
//     if cookie set

     return ['err'=>1, 'error'=>'Invalid token']; // no token
    } 

  $S = file_get_contents('http://ulogin.ru/token.php?token='. $In['token'] .'&host='. $_SERVER['HTTP_HOST']);
  $UserInfo = json_decode($S, true);
  // Detail: https://ulogin.ru/help.php#fields
  // $UserInfo['network'] - соц. сеть, через которую авторизовался пользователь
  // $UserInfo['identity'] - уникальная строка определяющая конкретного пользователя соц. сети
  // $UserInfo['uid'] - уникальный идентификатор пользователя в рамках соцсети
  // $UserInfo['first_name'] - имя пользователя
  // $UserInfo['last_name'] - фамилия пользователя

  $NetworkId = $this->GetSetNetwork($UserInfo['network']); // Get network id in any case!

  if (!strlen($UserInfo['uid'])) $UserInfo['uid'] = md5($UserInfo['email'].'-'.$NetworkId);

  // Check user exists (by email)
  $User = $this->DB->GetRec($this->DBSet['table'], $this->DBSet['uid'].'="'. $UserInfo['uid'] .'" AND '.$this->DBSet['network_id'].'="'. $NetworkId .'"');
  if ($User[$this->DBSet['user_id']] > 0)
    { // User exists, sign in, it
     $Out['is_new_one'] = false; // user exists in DB
     if ($User['is_new_one'] > 0) $Out['is_new_one'] = true; // Unfiniched registration
    }
  else
    { // New user, create profile
     $this->DB->SetFV([
      $this->DBSet['network_id'] => $NetworkId
     ,$this->DBSet['uid'] => $UserInfo['uid']
     ,$this->DBSet['first_name'] => $UserInfo['first_name']
     ,$this->DBSet['last_name'] => $UserInfo['last_name']
     ,$this->DBSet['nickname'] => $UserInfo['nickname']
     ,$this->DBSet['email'] => $UserInfo['email']
     ,$this->DBSet['photo'] => $UserInfo['photo']
     ,$this->DBSet['city'] => $UserInfo['city']
     ,$this->DBSet['country'] => $UserInfo['country']
     ,$this->DBSet['sn'] => json_encode($UserInfo)
     ]);

     $UserId = $this->DB->MakeInsert($this->DBSet['table']);
     if ($this->DB->ErrNo) { return ['err'=>$this->DB->ErrNo, 'error'=>$this->DB->Error]; }
     $User = $this->DB->GetRec($this->DBSet['table'], $this->DBSet['user_id'].'="'. $UserId .'"');
     $Out['is_new_one'] = true; // new user registred
    }

  // Generate a SID
  $SID = $this->GenUStr(32); //sha1($User[$this->DBSet['user_id']].'25081981');
  $this->SID = $SID; // current SID

  // Save SID
  $this->DB->SetFV([
   $this->DBSet['sid'] => $SID
  ]);
  $this->DB->MakeUpdate($this->DBSet['table'], $this->DBSet['user_id'].'='. $User[$this->DBSet['user_id']], 0, true, true);
  if ($this->DB->ErrNo) { return ['err'=>$this->DB->ErrNo, 'error'=>$this->DB->Error]; }

  // Return SID
  $Out['sid'] = $SID;
  $Out['uid'] = $User[$this->DBSet['user_id']];

  // Return user profile
  $Out['profile']['first_name'] = $User['first_name'];
  $Out['profile']['last_name'] = $User['last_name'];
  $Out['profile']['nickname'] = $User['nickname'];
  $Out['profile']['email'] = $User['email'];
  $Out['profile']['photo'] = $User['photo'];
  $Out['profile']['city'] = $User['city'];
  $Out['profile']['country'] = $User['country'];

  $this->Profile = $User;
  $this->Profile['id'] = $User[$this->DBSet['user_id']];
  $this->ID            = $User[$this->DBSet['user_id']];

  $Out['html_state'] = $this->GetAuthState();
  $Out['html_state'] = $Out['html_state']['html'];

  return $Out;
 }


 /**
  * Get detailed user profile by SID of ID
  *
  * @param string [sid] - Session ID
  * @param string [id] - User ID in database
  * @return int Return's ID of network. Return's 0 if no network
  */
 private function GetSetNetwork($Network = 'local')
 {
  $Id = 0;

  $Row = $this->DB->GetRec('user_network', 'network="'. mysql_real_escape_string($Network) .'"');
  if ($Row['network_id'] <= 0)
    {
     $this->DB->SetFV([
       'network' => $Network
       ]);

     $Id = $this->DB->MakeInsert('user_network');
    }
  else 
    {
     $Id = $Row['network_id']; 
    }

  return $Id;
 }


 /**
  * Get detailed user profile by SID of ID
  *
  * @param string [sid] - Session ID
  * @param string [id] - User ID in database
  * @return array On success [err] = 0. In [user], return user profile, otherwise [user] = null
  *               [user][sn] - contains data (array) from social network
  */
 public function GetProfile($In)
 {
  $Out = ['err' => 0, 'user' => null];

  if ($In['id'] > 0)
    { // Find by user id
     $User = $this->DB->GetRec($this->DBSet['table'], $this->DBSet['user_id'].'="'. $In['id'] .'"');
    }
  elseif (strlen($In['sid']))
    { // Check for SID in DB
     $User = $this->DB->GetRec($this->DBSet['table'], $this->DBSet['sid'].'="'. $In['sid'] .'"');
    }
  else
    {
     return $Out;
    }

  if ($User[$this->DBSet['user_id']] <= 0) { return ['err' => 31, 'User not found', 'user' => null]; }

  $User['sn'] = json_decode($User['sn'], true);
  if (!is_array($User['sn'])) { $User['sn'] = []; }

  return $Out;
 }


 /**
  * Update user profile by SID or ID
  *
  * @param string [sid] - Session ID (optional)
  * @param string [id] - User ID in database  (optional)
  * @return array On success [err] = 0. In [user], return updated user profile, otherwise [user] = null
  *               [user][sn] - contains data (array) from social network
  */
 public function UpdateProfile($In)
 {
  $Out = ['err' => 0, 'user' => null];

  if ($In['id'] > 0)
    { // Find by user id
     $User = $this->DB->GetRec($this->DBSet['table'], $this->DBSet['user_id'].'="'. $In['id'] .'"');
    }
  elseif (strlen($In['sid']))
    { // Check for SID in DB
     $User = $this->DB->GetRec($this->DBSet['table'], $this->DBSet['sid'].'="'. $In['sid'] .'"');
    }
  else
    {
     return ['err' => 404, 'msg' => 'User not found', 'user' => null];
    }

  $FV = [];
  foreach ($this->DBSet as $Key => $Field)
    {
     if (!strlen(trim($In[$Key]))) continue;
     $FV[$Field] = trim($In[$Key]);
    }

  // select fields to update
  $this->DB->SetFV($FV);

  // update user profile
  $this->DB->MakeUpdate($this->DBSet['table'], $this->DBSet['user_id'].'='. $User[$this->DBSet['user_id']], 0, true, true);
  if ($this->DB->ErrNo) { return ['err' => $this->DB->ErrNo, 'msg' => $this->DB->Error]; }

  // reload new user profile
  $User = $this->DB->GetRec($this->DBSet['table'], $this->DBSet['user_id'].'="'. $User[$this->DBSet['user_id']] .'"');
  if ($User[$this->DBSet['user_id']] <= 0) { return ['err' => 404, 'msg' => 'User not found after update', 'user' => null]; }

  $User['sn'] = json_decode($User['sn'], true);
  if (!is_array($User['sn'])) { $User['sn'] = []; }

  $Out['user'] = $User;

  return $Out;
 }


 /**
  * Return current authorization status
  *
  * @return bool If user signed in, return true. Otherwise false
  */
 public function IsAuth()
 {
  if ($this->Profile['id'] > 0) { return true; }
  else { return false; }
 }


 /**
  * Get(load) additional data from DB
  *
  * @param int $Id - User ID in DB. If == 0: return current auth user data
  * @param bool $isForceLoad - If == true: force loading user data from DB
  * @return array On success return additional data array, otherwise return false
  */
 public function GetData($Id = 0, $isForceLoad = false)
 {
  if ($Id > 0)
    { // Find data by user id
     $Data = $this->DB->GetRec('user_data', 'user_id='. $Id, 'data');
     $Data = json_decode($Data, true);
     if (!is_array($Data)) { return false; }
    }
  else
    { // current user data
     if ($isForceLoad)
       { // Force load data from DB
        $Id = $this->Profile[$this->DBSet['user_id']];
        $Data = $this->DB->GetRec('user_data', 'user_id='. $Id, 'data');
        $Data = json_decode($Data, true);
        if (!is_array($Data)) { return false; }
       }
     else 
       {
        $Data = '$this->Data';
        if (!is_array($Data)) 
          { // For current user data is empty. Try to load it from DB
           $Id = $this->Profile[$this->DBSet['user_id']];
           $Data = $this->DB->GetRec('user_data', 'user_id='. $Id, 'data');
           $Data = json_decode($Data, true);
           if (!is_array($Data)) { return false; }
          }
       }
    }

  return $Data;
 }


 /**
  * Set(save) additional data from DB
  *
  * @param int $Id - User ID in DB. If == 0: save to current auth user data
  * @return bool On success return true, otherwise return false
  */
 public function SetData($Data, $Id = 0)
 {
  if (!is_array($Data)) { return false; }

  if ($Id <= 0)
    { // Current user
     $Id = $this->Profile[$this->DBSet['user_id']];
     $this->Data = $Data;  // Update data for current user
    }

  if ($Id <= 0) return false; // No auth user

  // Save user data to DB
  $Row = $this->DB->Exec1st('SELECT `user_id` FROM `user_data` WHERE `user_id`='.sprintf('%d', $Id));
  if ($Row['user_id'] > 0)
    { // Update
     $Scpt = 'UPDATE `user_data` SET
       `ts_upd` = '. time() .',
       `data` = "'. mysql_real_escape_string(json_encode($Data, JSON_UNESCAPED_UNICODE)) .'"
       WHERE `user_id` = '. sprintf('%d', $Id) .'
       LIMIT 1';
     $this->DB->Exec($Scpt);
    }
  else
    { // Insert new
     $Scpt = 'INSERT INTO `user_data` (`ts_upd`, `user_id`, `data`) 
       VALUES('. time() .', '. $Id .', "'. mysql_real_escape_string(json_encode($Data, JSON_UNESCAPED_UNICODE)) .'")';
     $this->DB->Exec($Scpt);
    }
  /* DON'T WORK PROPERLY UNTIL SET ALL FIELDS
  $Scpt = 'INSERT INTO `user_data` (`ts_upd`, `user_id`, `data`) 
    VALUES('. time() .', '. $Id .', "'. mysql_real_escape_string(json_encode($Data, JSON_UNESCAPED_UNICODE)) .'")
    ON DUPLICATE KEY UPDATE `user_id`='.$Id;
  $this->DB->Exec($Scpt);
  */
  if ($this->DB->ErrNo) { return false; }

  return true;
 }


 /**
  * Update field in table user_profile.sn and local ::Profile['sn']
  *
  * @param int $Id - User ID in DB. If == 0: save to current auth user data
  * @return bool On success return 0, otherwise return > 0
  */
 public function UpdateSN($SN, $Id = 0)
 {
  if (!is_array($SN)) { return 1; }

  $isNeedLoadFirst = true;
  if ($Id <= 0)
    { // Current user
     $Id = $this->Profile[$this->DBSet['user_id']];
     $this->Profile[$this->DBSet['sn']] = array_merge($this->Profile[$this->DBSet['sn']], $SN);  // Update SN for current user
     $SN = $this->Profile[$this->DBSet['sn']];
     $isNeedLoadFirst = false;
    }

  if ($Id <= 0) return 2; // No auth user

  // Update SN to DB
  if ($isNeedLoadFirst)
    {
     $Row = $this->DB->Exec1st('SELECT `'. $this->DBSet['user_id'] .'`, `'. $this->DBSet['sn'] .'` FROM `'. $this->DBSet['table'] .'` WHERE `'. $this->DBSet['user_id'] .'`='.sprintf('%d', $Id));
     if ($Row[$this->DBSet['user_id']] > 0)
       {
        $Arr = json_decode($Row[$this->DBSet['sn']], true);
        if (!is_array($Arr)) { $Arr = []; }

        $SN = array_merge($Arr, $SN);  // Update SN for requested user
       }
     else 
       {
        return 3;
       }
    }

  if ($Id > 0)
    { // Update
     $Scpt = 'UPDATE `'. $this->DBSet['table'] .'` SET
       `ts_upd` = '. time() .',
       `'. $this->DBSet['sn'] .'` = "'. mysql_real_escape_string(json_encode($SN, JSON_UNESCAPED_UNICODE)) .'"
       WHERE `'. $this->DBSet['user_id'] .'` = '. sprintf('%d', $Id) .'
       LIMIT 1';
     $this->DB->Exec($Scpt);
    }

  if ($this->DB->ErrNo) { return $this->DB->ErrNo; }

  return 0;
 }


 // Misc. functions


 /**
  * Функция генерирует уникальную строку заданной длинны. Можно использовать как "соль", "SessionID"
  *
  * @param int $Len - Длинна строки, по умолчанию 16 знаков
  * @return string Возвращает уникальную строку
  */
 public function GenUStr($Len = 16)
 {
  $Symb = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $SymbLen = strlen($Symb);

  $US = '';
  for ($i = 0; $i < $Len; $i ++)
     {
      $US .= $Symb[mt_rand(0, $SymbLen - 1)];
     }

  return $US;
 }


}
