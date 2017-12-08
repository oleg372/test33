<?php
/*----------
 * WebApp. Backend/frontend core
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
 * @name IDentity Personal area user control
 * @desc Web application IDentity. Personal area
 * @version 1.05, 08.09.2017 (begins 20.07.2017)
 * @author Potapov Oleg
 * @copyright 2017, Potapov development
 */

require_once('_mailgun.php');

/*
define('?',  0);
*/
class TIDentity
{
 /**
  * Private vars
  */
 //private $L; // Language phases
 public $DB; // TDB class
 //private $DBSet; // Database settings
 //private $DefCookie; // Default cookie name
 private $Response; // Response to frontend
 public $User; // current signed in user

 /**
  * Public vars
  */
 public $Profile; // Current authorized user profile


 /**
  * Constructor
  * @param TDB $DB  - Resource to TDB
  * @param string $DBSet - Database structure settings
  * @param string $sLang - Language support (ru/en/ua)
  */
 public function __construct($In)
 {
  if (!isset($In['lang'])) { $In['lang'] = 'en'; }

  $this->DB = $In['db'];
  $this->Path = './';
  if ($In['path']) { $this->Path = $In['path']; }

  // Default response
  $this->Response = [ 
    'status' => 200,
    'error' => [
      'code' => 0,
      'message' => ''
      ]
    ];

  $this->User = new TUsersCtrl([
   'db' => $In['db']
  ,'path' =>  /*$_SERVER['DOCUMENT_ROOT'].*/'core/mods/SiteUsers/'
  ,'sid' => $_COOKIE['WebAppAuthSID']
  ]); 
 }


 /**
  * Set status, for rise an error
  * 
  * @return void
  */
 private function SetStatus($Status = 200, $Code = 0, $Message = '')
 {
  $this->Response['status'] = $Status;
  $this->Response['error']['code'] = $Code;
  $this->Response['error']['message'] = $Message;
 }


 /**
  * Set status, for rise an error
  * 
  * @return void
  */
 private function MergeResponse($NewResponse)
 {
  $this->Response = array_merge($this->Response, $NewResponse);
 }


 /**
  * Web application router
  *
  * @param string $Cmd - Command, request to method in class
  */
 public function Route($Cmd)
 {
  if ($this->User->IsAuth() && !$this->User->Profile['is_new_one']) // Authorized and commit own profile data
    { // Authorized area only!
     switch ($Cmd) 
       {
        case 'user-profile':   { $this->MergeResponse($this->UserProfile($_POST)); break; }
        case 'pg': { $this->MergeResponse($this->Pg($_POST)); break; } // TODO: DO WE USER THIS FUNC IN FUTURE?

        // This is heart of all registred user personal area
        case 'stage-handler': { $this->MergeResponse($this->StageHandler($_POST)); break; }

        // in case page loading
        case 'login-page':     { $this->MergeResponse($this->LoginPage($_POST)); break; }
        //default: { $this->SetStatus(400, 1, 'Bad Request'); break; }
        default: { $this->SetStatus(404, 1, 'Not found'); break; }
       }
    }
  else 
    { // Area that doesn't require to be authorized
     switch ($Cmd)
       {
        // Process webhooks from 3rd party addons
        case '3rd-party':      { $this->MergeResponse($this->ThirdParty($_POST)); break; }

        case 'login-page':     { $this->MergeResponse($this->LoginPage($_POST)); break; }
        case 'signin':         { $this->MergeResponse($this->User->Signin($_POST)); break; }
        case 'signin-approve': 
          {
           $_POST['is_new_one'] = 0; 
           $_POST['sid'] = $this->User->SID;
           $Tmp = $this->User->UpdateProfile($_POST);
           $this->MergeResponse($Tmp); 

           // Notify about new registered user
           $retMG = FastMail([
             'to' => 'to@id-entity.net', // , oleg372@gmail.com
             'template' => 'data/mail_templates/new_user_registered.html',
             'data' => [
               'user_id'    => $Tmp['user']['user_id'],
               'first_name' => $Tmp['user']['first_name'],
               'last_name'  => $Tmp['user']['last_name'],
               'email'      => $Tmp['user']['email'],
               'country'    => $Tmp['user']['country'],
               'city'       => $Tmp['user']['country'],
               'photo'      => $Tmp['user']['photo'],
               'identity'   => $Tmp['user']['sn']['identity'],
               'network'    => $Tmp['user']['sn']['network'],
               'sn'         => print_r($Tmp['user']['sn'], true)
               ]
             ]);
           $this->Response['_mg'] = $retMG;

           // set default road map (custom data)
           $this->Response['is_set_user_data'] = 
             $this->User->SetData(
               self::CleanComments(
                 json_decode(file_get_contents($this->Path.'/_biz_owner.json'), true)
                 )
               );

           break; 
          }
        //case 'user-profile':   { $this->MergeResponse($this->UserProfile($_POST)); break; }
        //case 'pg': { $this->MergeResponse($this->Pg($_POST)); break; }
        //default: { $this->SetStatus(400, 1, 'Bad Request'); break; }
        default: { $this->SetStatus(404, 1, 'Not found'); break; }
       }

    }


/* OLD VER! WORK!
  switch ($Cmd) 
    {
     case 'start':  { $this->MergeResponse($this->Start($_POST)); break; }

     case 'signin':         { $this->MergeResponse($this->User->Signin($_POST)); break; }
     case 'signin-approve': { $_POST['is_new_one'] = 0; 
                              $_POST['sid'] = $this->User->SID; 
                              $this->MergeResponse($this->User->UpdateProfile($_POST)); 
                              // set default road map (custom data)
                              $this->Response['is_set_user_data'] = $this->User->SetData(json_decode(file_get_contents($this->Path.'/_biz_owner.json'), true));
                              break; }
     case 'user-profile':   { $this->MergeResponse($this->UserProfile($_POST)); break; }

     case 'pg': { $this->MergeResponse($this->Pg($_POST)); break; } // TODO: DO WE USER THIS FUNC IN FUTURE?

     // This is heart of all registred user personal area
     case 'stage-handler': { $this->MergeResponse($this->StageHandler($_POST)); break; }

     //default: { $this->SetStatus(400, 1, 'Bad Request'); break; }
     default: { $this->SetStatus(404, 1, 'Not found'); break; }
    }
*/
  http_response_code($this->Response['status']);
  return json_encode($this->Response);
 }


 /**
  * Login page
  *
  */
 private function LoginPage($In = [])
 {
  $Out = ['html' => '', 'is_already_auth' => false];

  // Check for auth state
  if ($this->User->IsAuth() && !$this->User->Profile['is_new_one'])
    { // user signed in, return this state to frontend
     $Out['is_already_auth'] = true;
    }
  else
    { // else, render authorization form 
     $Out['html'] = self::GetTemplate('login_page');
    }
  return $Out;
 }





 /**
  * Start. Init GUI
  * 
  */
 /*public function Start($In = [])
 {
  $Out = ['html' => ''];

  // Check for auth state
  if ($this->User->IsAuth() && !$this->User->Profile['is_new_one'])
    { // if user signed in, render personal area
     //$Out['html'] = self::GetTemplate('personal_area');

 //public function test1($In=[]) { return 'Good: '.$this->L.' --- '.print_r($In,true); }
     //{"c":"$this->User->GetAuthState"}
     //Dynamicly call to $this->User->GetAuthState()['html']
     //$method = 'User';
     //$s = call_user_func(array($this->{$method}, 'GetAuthState'))['html'];

     $UserData = $this->User->GetData(); // Custom user data

     // Main (side-left) navigation
     $MainNav = self::GetTemplate('main_nav');
     $MainNavItem = self::GetTemplate('main_nav_item');

     $S = '';
     $Select = 's';
     foreach ($UserData['stages'][$UserData['current_stage']]['navigation'] as $Location => $Text)
       {
        $S .= self::Render($MainNavItem, ['location' => $Location, 'text' => $Text, 'select' => $Select]);
        $Select = '';
       }
     $MainNav = self::Render($MainNav, ['items' => $S]);

     $Out['html'] = self::Render(self::GetTemplate('personal_area'), [
       '$this->User->GetAuthState' => $this->User->GetAuthState()['html'],
       'main_nav' => $MainNav,
       'content' => $this->Pg([ 'pg' => 'pg_home' ])['html'] //self::GetTemplate('pg_home')
       ]);
    }
  else
    { // else, render authorization form 
     $Out['html'] = self::GetTemplate('login');
    }

  return $Out;
 }*/


 /**
  * User profile page
  * View registred user profile
  */
 public function UserProfile($In = [])
 {
  $Out = ['html' => ''];
  if (!$this->User->IsAuth())
    {
     $this->SetStatus(200, 401, 'Unauthorized');
     return $Out; // TODO:MOVE TO ROUTER
    }

  $Data = $this->User->Profile;
  $Data['dt_ins'] = date('d.m.Y', $Data['ts_ins']);
  $Data['network'] = $Data['sn']['network'];
  $Data['bdate'] = $Data['sn']['bdate'];

  $Out['html'] = self::Render(self::GetTemplate('user_profile'), $Data);

  return $Out;
 }



 /**
  * Get page
  * ~TODO: DO WE USE THIS FUNCTION IN FUTURE?
  */
 public function Pg($In = [])
 {
  $Out = ['html' => ''];
  if (!$this->User->IsAuth()) 
    {
     $this->SetStatus(200, 401, 'Unauthorized');
     return $Out; // TODO:MOVE TO ROUTER
    }
  /*
  $Data = [];

  $Data['ud'] = print_r($this->User->GetData(),true);

  $Data['User->first_name'] = $this->User->Profile['first_name'];
  $Data['User->last_name'] = $this->User->Profile['last_name'];
  $Data['User->email'] = $this->User->Profile['email'];
  $Data['User->uniq'] = $this->EncodeStr($this->User->Profile['id'].';'.$this->User->Profile['email'].';'.$this->User->Profile['first_name'].';'.$this->User->Profile['last_name']);
  //$Data['User->uniq'] = $this->EncodeStr('1241;oleg.potapov@gmail.com;Олег;Потапов');
  //$Data['User->uniq0'] = $this->DecodeStr($Data['User->uniq']);
  //$Data['User'] = $this->array2flat($this->User->Profile);
  $Out['html'] = self::Render(self::GetTemplate($In['pg']), $Data);
  */

  //$Out['html'] = $In['pg'];
  /*
  $Data = $this->User->Profile;
  $Data['dt_ins'] = date('d.m.Y', $Data['ts_ins']);
  $Data['network'] = $Data['sn']['network'];
  $Data['bdate'] = $Data['sn']['bdate'];

  $Out['html'] = self::Render(self::GetTemplate('user_profile'), $Data);
  */
/*
  $UD = $this->User->GetData();
  $Stage = $UD['current_stage'];
  $Handler = $UD['stages'][$Stage]['handler'];
  $IsComplete = self::IsStageComplete($UD['stages'][$Stage]['condition_complete']);
  $Out['html'] .= $Stage.'<br>'.$Handler.'<br>is complete: '.sprintf('%d',$IsComplete);
*/
  $Ret = $this->StageHandler(['_pg_part' => 'content'] + $In);
  return array_merge($Out, $Ret);
 }

 


 /**
  * Stage handler (pre-processor)
  */
 public function StageHandler($In = [])
 {
  $Out = ['html' => ''];

  $Out['title'] = 'IDentity.';

  if (!$this->User->IsAuth()) 
    {
     $this->SetStatus(200, 401, 'Unauthorized');
     return $Out; // TODO:MOVE TO ROUTER
    }

  $UD = $this->User->GetData();
  $Stage = $UD['current_stage'];
  $Handler = $UD['stages'][$Stage]['handler'];
  $IsComplete = self::IsStageComplete($UD['stages'][$Stage]['condition_complete']);

  if ($In['_pg_part'] == 'content')
    { // Requested to render only 'content' part (not whole page)
     $Ret = call_user_func(array($this, $Handler), ($UD['stages'][$Stage] + $In) );//['html'];
     //$Out['html'] = $Ret['html'];
     return array_merge($Out, $Ret);
    }

  if ($IsComplete)
    { // Stage is completed, step by roadmap to next stage
     // TODO: this step
     // Set new stage
     $UD['stages'][$Stage]['condition_complete']['ts_complete'] = time(); // Timestamp, when completed

     // Find next stage on roadmap
     $NextStage = '';
     for ($i = 0; $i < count($UD['road_map']); $i ++)
       {
        if ($UD['road_map'][$i] == $Stage)
          {
           $NextStage = $UD['road_map'][$i+1];
          }
       }

     // Set next stage on roadmap if available
     if (strlen($NextStage))
       {
        $UD['current_stage'] = $NextStage;
       }

     // Save user data
     $this->User->SetData($UD);

     // Run this func again: 
     return $this->StageHandler(); // Full page re-run (render)
    }

  $Ret = call_user_func(array($this, $Handler), ($UD['stages'][$Stage] + ['form'=>$In]));//['html'];

  //$Out['html'] = $Ret['html'];

  return array_merge($Out, $Ret);
 }


 /**
  * [stages]::0_payment
  * Custom handler
  */
 public function UserFirstAccess($In = []) 
 {
  $Out = ['error' => ['code' => 0, 'message' => ''], 'html' => ''];

  $Out['js'] = self::GetScript('pg_'.$In['handler']); // Script


  // Обработать отправленную форму
  if ($In['form']['f_formName'] == 'promocodeForm')
    {
     // Check promo code
     $PC = new TPromoCodes(['db' => $this->DB]);
     $Ret = $PC->Activate($In['form']['promo_code'], $this->User->ID);
     sleep(2); // pause - anti brute force
     if ($Ret == PC_OK)
       {
        // Set stage 0_payment is compleded
        $UD = $this->User->GetData();
        $UD['stages']['0_payment']['condition_complete']['is_complete'] = true; // step is completed
        $UD['stages']['0_payment']['promo_code_used'] = $In['form']['promo_code']; // Save promo code
        $Out['_User_SetData'] = $this->User->SetData($UD);

        $Out['html'] = 'О! Нашелся промо код! Активируем...';
        $Out['reload_full'] = true; // Reload all, because move to next stage (by roadmap)
       }
     elseif ($Ret == PC_NOT_FOUND)
       {
        $Out['html'] = 'Промо код, не найден.';
       }
     elseif ($Ret == PC_ALREADY_USED)
       {
        $Out['html'] = 'Указанный промо код, уже использован.';
       }
     elseif ($Ret == PC_EXPIRED)
       {
        $Out['html'] = 'Ваш промо код устарел и не может быть использован.';
       }
     elseif ($Ret == DB_ERROR)
       {
        $Out['html'] = 'Произошла ошибка при проверке. Пожалуйста попробуйте позже.';
       }

     return $Out;
    }

  $Data = [];

  //$Data['ud'] = print_r($this->User->GetData(),true);
  $Data['User->first_name'] = $this->User->Profile['first_name'];
  $Data['User->last_name'] = $this->User->Profile['last_name'];
  $Data['User->email'] = $this->User->Profile['email'];
  //$Data['User->uniq'] = $this->EncodeStr($this->User->Profile['id'].';'.$this->User->Profile['email'].';'.$this->User->Profile['first_name'].';'.$this->User->Profile['last_name']);
  //$UserData = $this->User->GetData(); // Custom user data

  /**
  // TODO: Запросить данные с epay.pdev.co.ua по метке: id_persnl_and_test - находится в $In['pay_campaign']
  // http://epay.pdev.co.ua/form/id_persnl_and_test
  // Онлайн тест Leader IDentity и личная консультация
  // 3340.00 UAH
  */
  $Data['amout'] = 3340;
  $Data['pay_link'] = 'http://epay.pdev.co.ua/form/id_persnl_and_test?'.
    'uid='.$this->User->ID.
    '&name='. trim($this->User->Profile['first_name'].' '.$this->User->Profile['last_name']).
    '&email='.$this->User->Profile['email'].
    '&s='.$UD['current_stage'].
    '&redirect='.urlencode('//personal.id-entity.net/')
    ; // TODO: фиксировать факт оплаты автоматически. доработать!


  $UD = $this->User->GetData();

  if ($_SERVER['REMOTE_ADDR'] == '109.86.119.30'){ //!!!!!!!!!!!LOCK
  $Data['pay_link'] = 'http://epay.pdev.co.ua/form/test_1906?'.
    'uid='.$this->User->ID.
    '&name='. trim($this->User->Profile['first_name'].' '.$this->User->Profile['last_name']).
    '&email='.$this->User->Profile['email'].
    '&s='.$UD['current_stage'].
    '&redirect='.urlencode('//personal.id-entity.net/')
    ; // TODO: фиксировать факт оплаты автоматически. доработать!
  }//!!!!!!!!!!!!!!LOCK


  if ($In['_pg_part'] == 'content')
    { // Render only 'content' area
     $Out['html'] = self::Render(self::GetTemplate('pg_'.$In['handler']), $Data);
     return $Out;
    }

  // Prepare for render other items for full page render

  // Main (side-left) navigation
  $MainNav = self::GetTemplate('nav_home_only');

  // Full page render
  $Out['html'] = self::Render(self::GetTemplate('personal_area'), [
    '$this->User->GetAuthState' => $this->User->GetAuthState()['html'],
    'main_nav' => $MainNav,
    'content' => self::Render(self::GetTemplate('pg_'.$In['handler']), $Data)//.TRC($In)
    ]);

  return $Out;
 }


 /**
  * [stages]::1_pass_test
  * Custom handler
  * Первый вход, после регистрации. Необходимо пройти тест
  */
 public function UserFirstTest($In = []) 
 {
  $Out = ['ret' => 0, 'html' => ''];

  /// Init vars in UserData is required ///
  $IsNewUD = false;
  $UD = $this->User->GetData();

  if (!strlen($UD['stages']['1_pass_test']['uniq_sess']))
    { $UD['stages']['1_pass_test']['uniq_sess'] = $this->EncodeStr($this->User->Profile['id'].';'.$this->User->Profile['email'].';'.$this->User->Profile['first_name'].';'.$this->User->Profile['last_name']); 
      $IsNewUD = true; }

  /// Save UserData if needed ///
  if ($IsNewUD)
    {
     $Out['_User_SetData'] = $this->User->SetData($UD);
     $In = array_merge($In, $UD['stages']['1_pass_test']); // Update local "In" var
    }


  /// Process forms ///


  /// Prepare data for placeholders ///
  $Data = [];
  $Data['User->first_name'] = $this->User->Profile['first_name'];
  $Data['User->last_name'] = $this->User->Profile['last_name'];
  $Data['User->email'] = $this->User->Profile['email'];
  $Data['uniq_sess'] = $In['uniq_sess'];
  $Data['test'] = trim($In['test']);


  /// Render only a part page (not whole) ///
  if ($In['_pg_part'] == 'content')
    { // Render only 'content' area
     $Out['html'] = self::Render(self::GetTemplate('pg_UserFirstTest'), $Data);
     return $Out;
    }

  /// Prepare placeholder for render other items for full page render ///

  // Main (side-left) navigation
  $MainNav = self::GetTemplate('nav_home_only');

  // Full page render
  $Out['html'] = self::Render(self::GetTemplate('personal_area'), [
    '$this->User->GetAuthState' => $this->User->GetAuthState()['html'],
    'main_nav' => $MainNav,
    'content' => self::Render(self::GetTemplate('pg_UserFirstTest'), $Data) //.TRC($In)
    ]);

  /// That's all folks ///
  return $Out;
 }


 /**
  * [stages]::2_set_meeting
  * Custom handler
  * После тестирования, назначение встречи. После назначения is_meeting_set становится TRUE, 
  * но этап не заканчивается, пока не проихойдет встреча: is_complete задается вручную после встречи.
  */
 public function UserFirstMeeting($In = [])
 {
  $Out = ['ret' => 0, 'html' => ''];
  $Stage = '2_set_meeting';
  $TmplContent = 'pg_UserFirstMeeting';

  /// Init vars in UserData is required ///
  $IsNewUD = false;
  $UD = $this->User->GetData();
  /*
  if (!strlen($UD['stages']['1_pass_test']['uniq_sess']))
    { $UD['stages']['1_pass_test']['uniq_sess'] = $this->EncodeStr($this->User->Profile['id'].';'.$this->User->Profile['email'].';'.$this->User->Profile['first_name'].';'.$this->User->Profile['last_name']); 
      $IsNewUD = true; }
  */

  /// Save UserData if needed ///
  if ($IsNewUD)
    {
     $Out['_User_SetData'] = $this->User->SetData($UD);
     $In = array_merge($In, $UD['stages']['2_set_meeting']); // Update local "In" var
    }


  /// Process forms ///
  if ($In['form']['f_formName'] == 'setMeetingForm')
    { // запись на мероприятие (если еще место не заняли)
     // Получить запись из id_meeting_day
     $MeetingDay = $this->DB->GetRec('id_meeting_day', 'id='.sprintf('%d', $In['form']['id_meeting_day']));
     if ($MeetingDay['id'] <= 0)
       { // Not found.
        $Out['html'] = 'ОШИБКА: ID не найден. Обновите страницу и повторите действия.';
        return $Out;
       }

     // Проверить, свободно ли еще время?, если нет - выдать уведомление.
     if ($MeetingDay[ $In['form']['time_field'] ] > 0)
       { // Day is already taken by some one else.
        $Out['html'] = 'К сожалению, данное время уже занял кто-то другой. Пожалуйста выберите другое время.';
        return $Out;
       }

     // Записать на запрошенное время.
     $S = 
       'who: '. trim($this->User->Profile['first_name'] .' '. $this->User->Profile['last_name']) ."\r\n".
       'place: '. $In['form']['meet-place'] .':'. $In['form']['addr'] ."\r\n".
       'skype: '. $In['form']['skype'] ."\r\n".
       'user_id: '. $this->User->ID ."\r\n".
       'stage: '. $Stage ."\r\n";

     // Сохранить
     if ($In['form']['meet-place'] == 'my') { $Confirmed = 0; } // встреча требует подтверждения
     else                                   { $Confirmed = 1; } // не требует подтверждения

     $this->DB->SetFV([
       $In['form']['time_field'] => $S,
       'is_' .$In['form']['time_field'] => 1,
       'is_confirmed_' .$In['form']['time_field'] => $Confirmed
       ]);

     $this->DB->MakeUpdate('id_meeting_day', 'id='.sprintf('%d', $In['form']['id_meeting_day']), 0, true, true);
     if ($this->DB->ErrNo)
       { 
        $Out['html'] = 'ERROR #'. $this->DB->ErrNo .'-'. __LINE__ .': '. $this->DB->Error;
        return $Out;
       }

     // Set stage 2_set_meeting, is_meeting_set = true
     $UD = $this->User->GetData();
     $UD['stages'][$Stage]['condition_complete']['is_meeting_set'] = true; // step is completed (not whole stage!)
     $UD['stages'][$Stage]['meeting_at']            = $In['form']['day'] .' '. $In['form']['time'];
     $UD['stages'][$Stage]['meeting_skype']         = $In['form']['skype'];
     $UD['stages'][$Stage]['meeting_addr']          = $In['form']['addr'];
     $UD['stages'][$Stage]['meeting_meet_place']    = $In['form']['meet-place'];
     $UD['stages'][$Stage]['meeting_consultant']    = $In['form']['consultant'];
     $UD['stages'][$Stage]['meeting_id_consultant'] = $In['form']['id_consultant'];
     $UD['stages'][$Stage]['meeting_id']            = $In['form']['id_meeting_day'];
     $UD['stages'][$Stage]['meeting_table']         = 'id_meeting_day';

     if ($In['form']['meet-place'] == 'my')
       { // Место встречи указано пользователем
        $UD['stages'][$Stage]['condition_complete']['is_meeting_confirmed'] = false; // Добавить флаг подтверждения встречи нашим консультантом
       }

     $Out['_User_SetData'] = $this->User->SetData($UD);

     $Out['html'] = 'Принято!';
     $Out['reload_full'] = true; // Reload all, because move to next stage (by roadmap)

     // Отправить уведомление по почте
     //require_once('_mailgun.php');
     $toEmail = 'to@id-entity.net, tt@id-entity.net, im@id-entity.net'; // , oleg372@gmail.com

     $MailBody = 
       'Когда: <b><big>'. $In['form']['day'] .' '. $In['form']['time'] .'</big></b><br>'.
       'С кем: <b>'. trim($this->User->Profile['first_name'] .' '. $this->User->Profile['last_name']) .'</b><br>'.
       'Где: <b><big>'. $In['form']['meet-place'] .'</big></b><br>'.
       'Место: <b>'. $In['form']['addr'] .'</b><br>'.
       'Скайп: <b>'. $In['form']['skype'] .'</b><br><br><br><br>'.

       '<span style="color:#bebebe">USER_ID: '. $this->User->ID .'<br>'.
       'STAGE: '. $Stage 
       .'</span>'
       ;

     $mgAPI = array('ApiKey' => 'key-edd35b09fd68cba8df37605c296242c9','Domain' => 'm.id-entity.net');
     $Subj = 'Презентация: '. trim($this->User->Profile['first_name'] .' '. $this->User->Profile['last_name']);
     $ret = MaingunSend($toEmail,  'IDentity postmaster@m.id-entity.net', $Subj, $MailBody, $mgAPI);
     $Out['_ret_mg'] = $ret;

     return $Out;
    }

  /// Prepare data for placeholders ///
  $Data = [];
  $Data['User->first_name'] = $this->User->Profile['first_name'];
  $Data['User->last_name'] = $this->User->Profile['last_name'];
  $Data['User->email'] = $this->User->Profile['email'];
  $Data['office'] = self::GetTemplate('_office_addr');

  //$Data['uniq_sess'] = $In['uniq_sess'];
  //$Data['test'] = trim($In['test']);

  if (/*$UD['stages'][$Stage]['meeting_id'] > 0 &&
      $UD['stages'][$Stage]['meeting_table'] == 'id_meeting_day'*/
      $UD['stages'][$Stage]['condition_complete']['is_meeting_set'] )
    { // Встреча назначена, дополнить информацией о встрече

     // Дата и время встречи
     $Data['meeting_at'] = $UD['stages'][$Stage]['meeting_at'];

     // место провидения
     $Data['not_comfirmed'] = '';
     if ($UD['stages'][$Stage]['meeting_meet_place'] == 'office')
       { $Data['meeting_addr'] = $Data['office']; }
     elseif ($UD['stages'][$Stage]['meeting_meet_place'] == 'my')
       { $Data['meeting_addr'] = $UD['stages'][$Stage]['meeting_addr']; 
         if (!$UD['stages'][$Stage]['condition_complete']['is_meeting_confirmed'])
           { $Data['not_comfirmed'] = '&mdash; не подтверждено консультантом'; }
       }
     elseif ($UD['stages'][$Stage]['meeting_meet_place'] == 'skype')
       { $Data['meeting_addr'] = 'По скайпу (наш консультант, добавит Вас заранее)'; }

     // Консультант
     $Consultant = $this->DB->GetRec('id_consultant', 'id='. $UD['stages'][$Stage]['meeting_id_consultant']);
     $Data['meeting_consultant'] = $Consultant['consultant'];

     $TmplContent = 'pg_UserFirstMeetingAwait';
    }

  $Out['js'] = self::GetScript('pg_'.$In['handler']); // Script

  // Часы для записи на консультацию (финкированный набор, такой же набор полей)
  $Times = [
    '9:30' => 't09_30',
    '11:30' => 't11_30',
    '13:30' => 't13_30',
    '15:30' => 't15_30',
    '18:30' => 't18_30'
    ];

  $WDay = [
    0 => 'Вс',
    1 => 'Пн',
    2 => 'Вт',
    3 => 'Ср',
    4 => 'Чт',
    5 => 'Пт',
    6 => 'Сб'
    ];
  $WeekDay = [
    0 => 'Воскресенье',
    1 => 'Понедельник',
    2 => 'Вторник',
    3 => 'Среда',
    4 => 'Четверг',
    5 => 'Пятница',
    6 => 'Суббота'
    ];

  $Mon = [
    '01' => 'янв',
    '02' => 'фев',
    '03' => 'мар',
    '04' => 'апр',
    '05' => 'май',
    '06' => 'июн',
    '07' => 'июл',
    '08' => 'авг',
    '09' => 'сен',
    '10' => 'окт',
    '11' => 'ноя',
    '12' => 'дек'
    ];

  $Month = [
    '01' => 'января',
    '02' => 'февраля',
    '03' => 'марта',
    '04' => 'апреля',
    '05' => 'мая',
    '06' => 'июня',
    '07' => 'июля',
    '08' => 'августа',
    '09' => 'сентября',
    '10' => 'октября',
    '11' => 'ноября',
    '12' => 'декабря'
    ];

  // Собрать свободные дни в часы для записи на консультацию

  // Получить список по ОДНОМУ консультанту, рассписание свободных/занятых дней, по записям (ключ: дата в DD.MM.YYYY)
  $Scpt = 'SELECT * 
 ,FROM_UNIXTIME(meeting_day.ts_day, "%d.%m.%Y") AS `dt_day`
 ,(5 - (is_t09_30 + is_t11_30 + is_t13_30 + is_t15_30 + is_t18_30)) AS `is_free_time`
FROM `id_meeting_day` AS meeting_day
WHERE meeting_day.ts_del = 0 
  AND meeting_day.id_consultant = 1
  AND meeting_day.ts_day >= UNIX_TIMESTAMP(STR_TO_DATE("'. date('d.m.Y') .'", "%d.%m.%Y"))
ORDER BY
  meeting_day.ts_day -- DESC';
  $Recs = $this->DB->Exec2Arr($Scpt, 'dt_day');

  // Шаблоны для рендера

  $Tmpl = self::GetTemplate('set_meeting_data_list_item');
  $TmplTime = self::GetTemplate('set_meeting_day_times');
  $TmplTimeItem = self::GetTemplate('set_meeting_day_times_item');

  $S = '';
  foreach ($Recs as $Date => $Row)
    {
     if (date('d.m.Y') == $Date)
       { // СЕГОДНЯ ВСЕГДА ЗАНЯТО
        $Row['is_free_time'] = 0;
       }

     // Проверить день на наличии свободных часов
     if ($Row['is_free_time'] > 0)
       { // Есть свободное время
        $Row['item_unavail'] = '';
        $Row['unavail'] = '&nbsp;';
       }
     else
       { // Нет свободного времени в этот день
        $Row['item_unavail'] = 'item-unavail';
        $Row['unavail'] = 'Недоступно';
       }

     $Row['today'] = '&nbsp;';
     if (date('d.m.Y') == $Date)
       { // - Сегодня -
        $Row['today'] = '&ndash; Сегодня &ndash;'; 
       }

     $Row['w_day'] = $WDay[ date('w', strtotime($Date)) ];
     $Row['week_day'] = $WeekDay[ date('w', strtotime($Date)) ];
     $Row['day_month'] = date('d', strtotime($Date)) .' '. $Mon[ date('m', strtotime($Date)) ];
     $Row['d_month_y'] = date('d', strtotime($Date)) .' '. $Month[ date('m', strtotime($Date)) ] .' '. date('Y', strtotime($Date));

     $S .= self::Render($Tmpl, $Row);

     // Выбор времени (рендер только свободного
     if (!$Row['is_free_time']) continue;
     $S2 = '';
     foreach ($Times as $Time => $Field)
       {
        $Row['field_time'] = $Field;
        $Row['time'] = $Time;
        $Row['date'] = $Date;// .' '. $Time;
        if ($Row['is_'. $Field] == 0)
          { // Слот времени свободен
           $S2 .= self::Render($TmplTimeItem, $Row);
          }
       }

     $Data['day_times'] .= self::Render($TmplTime, $Row + ['time_list' => $S2]);
    }

  // Даты
  $Data['data_list'] = $S;

  // Рендер 
  $Data['set_meeting'] = self::Render(self::GetTemplate('set_meeting'), [
    'data_list' => $Data['data_list'],
    'day_times' => $Data['day_times'],
    'office'    => $Data['office']
    ]);


  /// Render only a part page (not whole) ///
  if ($In['_pg_part'] == 'content')
    { // Render only 'content' area
     $Out['html'] = self::Render(self::GetTemplate($TmplContent), $Data);
     return $Out;
    }

  /// Prepare placeholder for render other items for full page render ///

  // Main (side-left) navigation
  $MainNav = self::GetTemplate('nav_home_only');

  // Full page render
  $Out['html'] = self::Render(self::GetTemplate('personal_area'), [
    '$this->User->GetAuthState' => $this->User->GetAuthState()['html'],
    'main_nav' => $MainNav,
    'content' => self::Render(self::GetTemplate($TmplContent), $Data) //.$S.TRC($In)
    ]);

  /// That's all folks ///
  return $Out;
 }


 /**
  * [stages]::3_get_test_results
  * Custom handler
  * После личной консультации (презентации личного отчета).
  * Личный кабинет пользователя
  */
 public function UserBizOwner($In = [])
 {
  $Out = ['ret' => 0, 'html' => ''];
  $Stage = '3_get_test_results';

       if ($In['pg'] == 'pg_employees')      { $TmplContent = 'pg_UserBizOwner_employees'; }
  else if ($In['pg'] == 'pg_shared_with_me') { $TmplContent = 'pg_SharedWithMe'; }
  else                                       { $TmplContent = 'pg_UserBizOwner'; }

  /// Init vars in UserData is required ///
  $IsNewUD = false;
  $UD = $this->User->GetData();
  /*
  if (!strlen($UD['stages'][$Stage]['uniq_sess']))
    { $UD['stages']['1_pass_test']['uniq_sess'] = $this->EncodeStr($this->User->Profile['id'].';'.$this->User->Profile['email'].';'.$this->User->Profile['first_name'].';'.$this->User->Profile['last_name']); 
      $IsNewUD = true; }
  */

  /// Save UserData if needed ///
  if ($IsNewUD)
    {
     $Out['_User_SetData'] = $this->User->SetData($UD);
     $In = array_merge($In, $UD['stages'][$Stage]); // Update local "In" var
    }

  $OwnerId = $this->User->ID;
  //$OwnerId = 81; /// TODO : REMOVE
  //$OwnerId = 88; /// TODO : REMOVE

  require_once('./core/mods/IDentity/render_report.php');

  /// Process forms ///
  if ($In['form']['f_formName'] == 'get-report')
    {

     /*if ($MeetingDay['id'] <= 0)
       { // Not found.
        $Out['html'] = 'ОШИБКА: ID не найден. Обновите страницу и повторите действия.';
        return $Out;
       }
     // Сохранить
     $this->DB->SetFV([
       $In['form']['time_field'] => $S,
       'is_' .$In['form']['time_field'] => 1
       ]);

     $this->DB->MakeUpdate('id_meeting_day', 'id='.sprintf('%d', $In['form']['id_meeting_day']), 0, true, true);
     if ($this->DB->ErrNo)
       { 
        $Out['html'] = 'ERROR #'. $this->DB->ErrNo .'-'. __LINE__ .': '. $this->DB->Error;
        return $Out;
       }
     $Out['html'] = 'Принято!';
     $Out['reload_full'] = true; // Reload all, because move to next stage (by roadmap)

     $Out['_form'] = $In['form'];

     return $Out;
     */
     $Report = $this->DB->Exec1st('SELECT * FROM id_report WHERE id='. sprintf('%d', $In['form']['id_report']) .' LIMIT 1');
     //$Report = $this->DB->Exec1st('SELECT * FROM id_report WHERE owner_id='. $OwnerId .' AND id='. sprintf('%d', $In['form']['id_report']) .' LIMIT 1');
     $Report['human_type'] = $this->DB->GetRec('id_human_type', 'id='.$Report['id_human_type']);
     $Out['html'] = "<h1>$Report[full_name]</h1>". RenderReport($Report); 
     //$Data['report_user_name'] = $Report['full_name'];
     //$Out['html'] .= TRC($In['form']);
     return $Out;
    }
  elseif ($In['form']['f_formName'] == 'share-report')
    {
/*
// В каждой записи в БД содержится JSON в поле shared_with.shared
{
  "personal_reports": {   // Тип "чем поделились"
    "handler": "?",       // Название обработчика
    "table": "id_report", // Имя таблицы
    "key_field": "id",    // поле в таблице
    "shared": {           // массив [key_field] : [тип доступа]
      "1": "ro",
      "2": "ro",
      "3": "ro"
    }
  }
}
//
// Типы "чеи поделились"
// "personal_reports" - персональные отчеты. Для них обработчик "Shared_Reports"
//
// Типы доступов:
// "ro" - Read Only - только чтение
// "rw" - Read/write - чтение и запись
*/
     // Найти по e-mail пользователя в user_profile.email 

     // Если пользователей несколько под одним e-mail, тогда предоставим им всем доступ

     // Сообщение об успехе

// TODO: Написание функции вынести в отдельный файл. require его только тут

     $Out['html'] = '...sharing...'; 



     return $Out;
    }
  elseif ($In['form']['f_formName'] == 'unshare-report')
    {
     // Найти выделенный доступ по e-mail

     // Если пользователей несколько под одним e-mail, тогда удалить доступ от всех пользователей

     // Сообщение об успехе

     $Out['html'] = '...processing...'; 
     return $Out;
    }


  /// Prepare data for placeholders ///
  $Data = [];
  $Data['User->first_name'] = $this->User->Profile['first_name'];
  $Data['User->last_name'] = $this->User->Profile['last_name'];
  $Data['User->email'] = $this->User->Profile['email'];

  if ($In['pg'] == 'pg_home' || !$In['pg']) 
    {
     $Report = $this->DB->Exec1st('SELECT * FROM id_report WHERE owner_id='. $OwnerId .' AND user_id='. $OwnerId .' LIMIT 1');
     $Report['human_type'] = $this->DB->GetRec('id_human_type', 'id='.$Report['id_human_type']);
     $Data['own_report'] = RenderReport($Report); 
     $Data['report_user_name'] = $Report['full_name'];
    }
  elseif ($In['pg'] == 'pg_employees')
    {
     require('./core/mods/IDentity/src_employee.php');
     $Data['employee_list'] = Employee($In);
     /*$Recs = $this->DB->Exec2Arr('SELECT id, owner_id, user_id, full_name, email, id_human_type FROM id_report WHERE user_id != '. $OwnerId .' AND owner_id='. $OwnerId .' ORDER BY full_name', 'id');
     $S = '';
     foreach($Recs as $Row)
       {
        $S .= '<li><a href="javascript:;" class="js-btn-employee-report" data-id-report="'. $Row['id'] .'">'. $Row['full_name'] .'</a></li>';
       }
     $Data['employee_list'] = $S;
     $Data['employee_list_content'] = '';

     if ($_SERVER['REMOTE_ADDR'] == '95.158.59.135') {
     require('./core/mods/IDentity/src_employee.php');
     $Data['employee_list_content'] = Employee($In);

     }*/
    }
  elseif ($In['pg'] == 'pg_shared_with_me')
    {
     $Data = array_merge($Data, $this->SharedWithMe());
    }

  $Out['js'] = self::GetScript('pg_'.$In['handler']); // Script

  /// Render only a part page (not whole) ///
  if ($In['_pg_part'] == 'content')
    { // Render only 'content' area
     $Out['html'] = self::Render(self::GetTemplate($TmplContent), $Data); //.TRC($In);
     return $Out;
    }

  /// Prepare placeholder for render other items for full page render ///

  // Main (side-left) navigation
  $MainNav = self::GetTemplate('pg_UserBizOwner_nav');

  // Full page render
  $Out['html'] = self::Render(self::GetTemplate('personal_area'), [
    '$this->User->GetAuthState' => $this->User->GetAuthState()['html'],
    'main_nav' => $MainNav,
    'content' => self::Render(self::GetTemplate($TmplContent), $Data) //.TRC($In)
    ]);

  /// That's all folks ///
  return $Out;
 }


 /**
  * Process webhooks from 3rd party addons
  * as example, from epay.pdev.co.ua
  */
 public function ThirdParty($In = [])
 {
  $Out = ['ret' => 0, 'html' => ''];

  $Raw = file_get_contents("php://input"); // Get raw data from php input
  $Data = json_decode($Raw, true);
  if (isset($Data['data']))
    { // Decode 'data' if it is json
     $Arr = json_decode($Data['data'], true);
     if (is_array($Arr)) { $Data['data'] = $Arr; }
    }

  if ($Data['source'] == 'EPay' &&  // Webhook from payment
      ($Data['campaign'] == 'id_persnl_and_test' ||  // accepted company
       $Data['campaign'] == 'test_1906')
     )
    {
     $IdUser    = $Data['data']['get']['uid']; // User ID, who made payment
     $CurrStage = $Data['data']['get']['s']; // current stage
     //$Name      = $Data['data']['get']['name'];
     //$Email     = $Data['data']['get']['email'];

     if ($IdUser > 0 && $CurrStage == '0_payment')
       {
        $UD = $this->User->GetData($IdUser);
        $UD['stages'][$CurrStage]['condition_complete']['is_complete'] = true; // step is completed
        $UD['stages'][$CurrStage]['payment'] = $Data;
        unset($UD['stages'][$CurrStage]['payment']['data']);
        /*$tmp = */$this->User->SetData($UD, $IdUser);
        //$S .= "\r\n\r\n\r\n".print_r($tmp,true);
       }
    }
  elseif ($Data['source'] == 'NPS') // Webhook from NPS form http://id-entity.net/f/nps/?in=...
    {
     if ($Data['in']['user_id'] <= 0) { $Data['in']['user_id'] = 0; }
     if ($Data['nps']           <= 0) { $Data['nps'] = 0; }

     $Data['email'] = trim($Data['email']);
     $Data['note'] = trim($Data['note']);

     $this->DB->SetFV([
       'ts'       => time(),
       'nps'      => $Data['nps'],
       'campaign' => $Data['in']['campaign'],
       'email'    => $Data['in']['email'],
       'user_id'  => $Data['in']['user_id'],
       'notes'    => $Data['note']
       ]);

     $this->DB->MakeInsert('nps', false);
    }

/*
Decoded Array
(
    [source] => EPay
    [campaign] => test_1906
    [name] => Oleg Potapov
    [tel] => 
    [amount] => 4.00
    [currency] => UAH
    [qty] => 2
    [purpose_payment] => TEST API-тест и веб-хук
    [transact_no] => 100279
    [order_id] => 100279
    [dt] => 2017-11-19 16:22
    [data] => Array
        (
            [get] => Array
                (
                    [uid] => 69
                    [s] => 0_payment
                    [name] => Oleg Potapov
                    [email] => oleg@potapov.com.ua
                )

        )

)
*//*
  $S  = "\r\n\r\n";
  $S .= '_GET:'.print_r($_GET, true);
  $S .= "\r\n\r\n";
  $S .= '_In:'.print_r($In, true);
  $S .= "\r\n\r\n";
  $S .= '_raw:'.file_get_contents("php://input");
  $S .= "\r\n\r\n";
  $S .= 'Decoded '.print_r($Data, true);
  file_put_contents('core/mods/IDentity/__ts_'.time(), $S);
*/
  return $Out;
 }





 
 // Misc. functions

 private function SharedWithMe()
 {
  $Out = [];

  $Out['content'] = 'На данный момент, никто не поделился документами с Вами.';

  //if ($_SERVER['REMOTE_ADDR'] != '95.158.59.135') return $Out; /// TODO: REMOVE THIS LINE

  $S = '';
 
  $Recs = $this->DB->Exec2Arr('SELECT 
  sw.id,
  sw.ts_upd,
  sw.owner_id,  -- Владелец (предоставляет доступ)
  sw.email,     -- E-mail пользователя - получатель доступа
  sw.user_id,   -- ID пользователя, получателя доступа; ->user_profile.user_id	
  sw.shared,    -- Чем поделились и с каким доступом (JSON) http://numl.org/dsc	
  users.last_name,
  users.first_name,
  users.email
FROM `shared_with` AS `sw` 
INNER JOIN `user_profile` AS `users` ON users.user_id = sw.owner_id
WHERE sw.ts_del = 0
  AND sw.user_id = '. $this->User->ID .'
ORDER BY sw.ts_upd DESC', 'id');

  if (!count($Recs)) { $Out['content'] = 'На данный момент, никто не поделился документами с Вами.'; return $Out; }

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
// В каждой записи в БД содержится JSON в поле shared_with.shared
{
  "personal_reports": {   // Тип "чем поделились"
    "handler": "?",       // Название обработчика
    "table": "id_report", // Имя таблицы
    "key_field": "id",    // поле в таблице
    "shared": {           // массив [key_field] : [тип доступа]
      "1": "ro",
      "2": "ro",
      "3": "ro"
    }
  }
}
/
// Типы "чеи поделились"
// "personal_reports" - персональные отчеты. Для них обработчик "Shared_Reports"
//
// Типы доступов:
// "ro" - Read Only - только чтение
// "rw" - Read/write - чтение и запись
*/
  $Out['content'] = $S;
  return $Out;
 }


 /**
  * Shared reports
  */
 public function Shared_Reports($In)
 {
  $Table    = $In['table'];
  $KeyField = $In['key_field'];
  $Shared   = $In['shared'];

  $Ids = implode(array_keys($Shared), ','); // список ID отчетов которыми поделились
  $Recs = $this->DB->Exec2Arr('SELECT id, owner_id, user_id, full_name, email, id_human_type 
  FROM id_report WHERE id IN ('. $Ids .') ORDER BY full_name', 'id');
  if (!count($Recs)) return '<div class="no-recs"> нет записей </div>';

  $S .= '<ul>';
  foreach ($Recs as $Row)
    {
     $S .= '<li><a href="javascript:;" class="js-btn-employee-report2" data-id="'. $Row['id'] .'">'. $Row['full_name'] .'</a></li>';
    }
  $S .= '</ul>';

  return $S;
 }


 /**
  * Check for stage is complete
  * Check in array only key names started from is_*
  * 
  */
 public function IsStageComplete($In)
 {
  $IsComplete = true;
  $Count = 0;
  foreach ($In as $Key => $Flag)
    {
     if ($Key[0] == 'i' &&
         $Key[1] == 's' &&
         $Key[2] == '_')
       {
        if (!$Flag) { $IsComplete = false; break; }
        $Count ++;
       }
    }
  if (!$Count) $IsComplete = false; // If stage have none of flags (is_...), then that stage can't be completed
  return $IsComplete;
 }


 /**
  * Check for stage is complete
  * Check in array only key names started from is_*
  * 
  */
/* public function SetStageComplete(&$In)
 {
     $UD['stages'][$Stage]['condition_complete']['ts_complete'] = time(); // Timestamp, when complete step
  $IsComplete = self::IsStageComplete($UD['stages'][$Stage]['condition_complete']);



  $IsComplete = true;
  $Count = 0;
  foreach ($In as $Key => $Flag)
    {
     if ($Key[0] == 'i' &&
         $Key[1] == 's' &&
         $Key[2] == '_')
       {
        if (!$Flag) { $IsComplete = false; break; }
        $Count ++;
       }
    }
  if (!$Count) $IsComplete = false; // If stage have none of flags (is_...), then that stage can't be completed
 return $In;
 }*/


 /**
  * Load template from file
  *
  * @param string $TmplName - Template name (wo/ "tmpl_" and ".html")
  * @return string Return's template with placeholders
  */
 public function GetTemplate($TmplName)
 {
  $File = $this->Path .'tmpl_'.$TmplName.'.html';
  if (!file_exists($File)) return false;
  return file_get_contents($File);
 }


 /**
  * Load script from file
  *
  * @param string $TmplName - Template name (wo/ "tmpl_" and ".js")
  * @return string Return's (js) script, otherwise return empty string
  */
 public function GetScript($TmplName)
 {
  $File = $this->Path .'tmpl_'.$TmplName.'.js';
  if (!file_exists($File)) return '';
  return file_get_contents($File);
 }


 /**
  * Render template
  *
  * @param string $TmplName - Template
  * @param array $Data - Array with {placeholder} => "value"
  * @return string Return rendered HTML
  */
 public function Render($Tmpl, $Data)
 {
  $From = []; $To = [];
  foreach ($Data as $Key => $Val)
    {
     $From[] = '{'. $Key .'}';
     $To[] = $Val;
    }

  $Tmpl = str_replace($From, $To, $Tmpl);

     //{"c":"$this->User->GetAuthState"}
     //Dynamicly call to $this->User->GetAuthState()['html']
     //$method = 'User';
     //$s = call_user_func(array($this->{$method}, 'GetAuthState')/*, ['prm1' => 'a', 'prm2' => 'b']*/)['html'];

  return $Tmpl;
 }


 /**
  * Очистка комментариев "_comment*" в многоуровневом массиве
  * Рекурсивное выполнение
  * 
  * @param array $arr - Входной массив данных
  * @return array Возвращает очиценный от комментариев массив
  */
 function CleanComments($arr)
 {
  foreach($arr as $key => $val)
    {
     if ($key[0] == '_')
       {
        if ($key[1] == 'c' && 
            $key[2] == 'o' &&
            $key[3] == 'm' &&
            $key[4] == 'm' &&
            $key[5] == 'e' &&
            $key[6] == 'n' &&
            $key[7] == 't')
          { unset($arr[$key]); continue; }
        elseif (
            $key[1] == 'c' && 
            $key[2] == 'o' &&
            $key[3] == 'm' &&
            $key[4] == 'e' &&
            $key[5] == 'n' &&
            $key[6] == 't')
          { unset($arr[$key]); continue; }
       }

     if (is_array($val))
       {
        $arr[$key] = self::cleanComments($val);
       }
    }
  return $arr;
 }


 /**
  * Преобразует многоуровневый массив в плоский с ключами - цепочками.
  * Многоуровневый массив:
  * [item0] => home
  * [nearest] => Array
  *     (
  *         [proc] => kiev
  *         [dt] => Array
  *             (
  *                 [begin] => 100
  *                 [duration] => 30
  *             )
  *
  *     )
  * в поский массив такого вида:
  * [item0] => home
  * [nearest->proc] => kiev
  * [nearest->dt->begin] => 100
  * [nearest->dt->duration] => 30
  * 
  * !Примечание: результат сохраняет в глобальном массиве $dv_fastArr, который необходимо инициализировать перед вывозом
  * 
  * @param string $arr - многоуровневый массив. Обрабатівается рекурсивно.
  * @param string $keyPath - путь из ключей.
  * @param string $sep - разделитель между ключами, по умолчанию "->".
  */
 public static function array2flat($arr, $keyPath = '', $sep = '->')
 {
  global $dv_fastArr; // Для быстрой работы, создается гобальный массив который заполняется плоскими ключами из многомерных и их значений. Такой подход быстрее работает нежели передавить массив в параметрах и возвращаеть его return'ом.
  foreach ($arr as $key => $val)
    {
     if (is_array($val)) { self::array2flat($val, $keyPath . $key . $sep); } // Обнаружен вложенный массив, выполнить рекурсию
     else { $dv_fastArr[$keyPath . $key] = $val; } // Сохранить элемент с ключем
    }
  return $dv_fastArr;
 }


 /**
  * Закодировать
  * 
  * @return string Возвращает закодированую строку
  */
 public static function EncodeStr($Data)
 {
  $encodedData = base64_encode($Data); // base64 1й проход
  //  $encodedData = str_replace(array('+','/','=','1','2','3','4','5','6','7','8','9','0'),
  //                             array('_','-','~',')','(','*','&','^','%','$','#','@','!'), $encodedData); // шифруем подменой знаков
  $encodedData = str_replace(array('+','/','='),
                             array('_','-','.'), $encodedData); // шифруем подменой знаков
  $encodedData = base64_encode($encodedData); // base64 2й проход
  return urlencode($encodedData); // $encodedData - готовая зашифрованная строка
 }

 
 /**
  * Раскодировать
  * 
  * @return array При успехе возвращает раскодированный массив, иначе возвращает false
  */
 public static function DecodeStr($encodedData)
 {
  $encodedData = base64_decode($encodedData); // base64 2й проход
  $encodedData = str_replace(array('_','-','.'),
                             array('+','/','='), $encodedData); // расшифруем с подменой знаков
  //  $encodedData = str_replace(array('_','-','~',')','(','*','&','^','%','$','#','@','!'),
  //                             array('+','/','=','1','2','3','4','5','6','7','8','9','0'), $encodedData); // расшифруем с подменой знаков
  $encodedData = base64_decode($encodedData); // base64 1й проход
  // urldecode не нужно
  return $encodedData;
 }


}
