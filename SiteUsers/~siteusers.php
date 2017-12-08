<?php

 define("NEW_USER", 0);
 define("SEC_OBJ_BASE", false);
 define("NO_SOC_ID", '');

 /**
  * Добавить нового пользователя
  * 
  * @param int $IdUser - ID пользователя (x_user.id) или NEW_USER для добавления нового
  * @param string $SocId - Глобально уникальный идентификатор пользователя в социальных сетях. Если нету, тогда NO_SOC_ID
  * @param string $Login - Логин пользователя. Рекомендуется использовать e-mail
  * @param array $Pass - массив с двумя элементами - паролями. Пример: array($_POST['psw1'], $_POST['psw2'])
  * @param string $Nikname - Псевдоним/имя пользователя (x_user.user)
  * @param array $SecObj - можно передать целиком $_POST, автоматически спарсит по ключу начинающемуся на "so_"
  * @param array $PV - ассоциативный массив param=value
  * @return array Возвращает массив
  */
 function siteusersSet($IdUser, $SocId, $Login, $Pass, $Nikname, $SecObj = SEC_OBJ_BASE, $PV = false)
 {
  global $CMS, $CMSA;

       if ($CMSA) { $localCMS = $CMSA; }
  else if ($CMS)  { $localCMS = $CMS;  }

  $Ret = array();
  $Ret['errno'] = 0;
  $Ret['error'] = '';
 
  settype($IdUser, 'integer');
  $Login = mysql_real_escape_string(trim($Login));
  $Nikname   = mysql_real_escape_string(trim($Nikname));
  $Pass[0]   = mysql_real_escape_string($Pass[0]);
  $Pass[1]   = mysql_real_escape_string($Pass[1]);
  
  // Объекты доступа
  if (is_array($SecObj))
    {
     $SO = array();
     foreach ($SecObj as $Key => $Val)
       { // !!!!!!!! ПРИДУМАТЬ ЧЕ-ТО !!!!
        //if ($Key[0] == 's' || $Key[1] == 'o' || $Key[2] == '_') { ; } else { continue; }
        //$SO[$Val] = '1'; // ключ = 1
        
        if ($Key[0] == 's' || $Key[1] == 'o' || $Key[2] == '_') { ; } else { continue; }
        $SO[$Val] = '1'; // ключ=1: есть доступ, в другм случаем объект не добавляется
       }
    }
  else
    { // минимум один доступ
     $SO['Base'] = 1;
    }

  $sSO = mysql_real_escape_string(json_encode($SO)); // Объекты доступа в JSON
    
  // Дополнительные параметры
  $sPV = '';
  if (is_array($PV))
    {
     $sPV = mysql_real_escape_string(json_encode($PV));
    }
  
  $isCheckPass = false;
  if ($IdUser)
    {
     $isCheckPass = false;     
     if (strlen($Pass[0]) || strlen($Pass[1]))
       {
        $isCheckPass = true;
        $Salt = $localCMS->GenUStr(); // генерация соли
        $fldSaltPass = ' ,salt="'.$Salt.'", apass_hash="'.sha1($Pass[0].$Salt).'" '; // генерация пароля
       }

     $Scpt = 'UPDATE x_user SET ts_upd='. time() .'
     ,alogin = "'.$Login.'"
     ,user = "'.$Nikname.'"
     ,pv = "'.$sPV.'"
     ,access = "'.$sSO.'"
     '. $fldSaltPass .'
     WHERE id='.$IdUser;
    }
  else
    {
     if (!strlen($SocId))
       { // новый пользователь, не через социальную сеть
        $isCheckPass = true;
        $Salt = $localCMS->GenUStr(); // генерация соли
        $PassHash = sha1($Pass[0].$Salt); // генерация пароля
       }
     else
       { // сгенерировать пароль и соль (нужно для поддержки сесси из соц. сети авторизации)
        $Salt = $localCMS->GenUStr(); // генерация соли
        $Pass[0] = $localCMS->GenUStr(); // генерация пароля
        $PassHash = sha1($Pass[0].$Salt); // генерация пароля
       }
       
     $Scpt = 'INSERT INTO x_user(ts_ins, ts_upd, ts_last, alogin, user, apass_hash, salt, pv, access, soc_id) VALUES('.time().' ,'.time().'
     ,0
     ,"'.$Login.'"
     ,"'.$Nikname.'"
     ,"'.$PassHash.'"
     ,"'.$Salt.'"
     ,"'.$sPV.'"
     ,"'.$sSO.'"
     ,"'.$SocId.'"
     )';
    }

  if ($isCheckPass)
    {
     if (strlen($Pass[0]) < 6 ||
         strlen($Pass[1]) < 6)
       {
        $Ret['errno'] = 12;
        $Ret['error'] = 'Пароль повинен бути не коротше 6 символів.'; //'Пароль должен быть не короче 6 символов.';
        return $Ret;
       }

     if ($Pass[0] != $Pass[1])
       {
        $Ret['errno'] = 13;
        $Ret['error'] = 'Пароль необхідно вказати двічі однаково.'; //'Пароль необходимо указать дважды одинаково.';
        return $Ret;
       }
    }

  // Проверить логин на дубликат  
  $Row = $localCMS->DB->GetRec('x_user', 'alogin="'. $Login .'"');
  if ($Row['id'] && $IdUser != $Row['id'])
    { // дубликат
     $Ret['errno'] = 14;
     //$Ret['error'] = 'Логин '. htmlspecialchars($Login) .' логин уже занят, выберите другой.';
     $Ret['error'] = 'Логін '. htmlspecialchars($Login) .' вже зайнятий, виберіть інший.';
     $Ret['new_id'] = $Row['id']; // Вернем ID дубликата
     return $Ret;
    }

  $Ret['new_id'] = 0;

  $localCMS->DB->Exec($Scpt);
  if ($localCMS->DB->ErrNo)
    { // Ошибка в БД
     $Ret['errno'] = $localCMS->DB->ErrNo;
     $Ret['error'] = $localCMS->DB->Error;
    }
  else 
    { // порядок!
     $Ret['new_id'] = $localCMS->DB->InsertedId(); // Id нового пользователя или 0
    }

  return $Ret;
 }


 /**
  * Сохраняет запись 
  * 
  * @param int $IdParent - ID объекта (x_user_obj.id)
  * @return cod Возвращает HTML-код
  */
 /*public function SaveUsr($IdParent)
 {
  global $CMSA;
  $Ret = array();
  $Ret['err'] = '';
  $Ret['data'] = '';
  $Cod = '';

  settype($IdParent, 'integer');
  $_POST['alogin'] = mysql_real_escape_string($_POST['alogin']);
  $_POST['user'] = mysql_real_escape_string($_POST['user']);
  $_POST['psw1'] = mysql_real_escape_string($_POST['psw1']);
  $_POST['psw2'] = mysql_real_escape_string($_POST['psw2']);

  // Объекты доступа
  $SO = array($SO);
  foreach ($_POST as $Key => $Val)
    {
     if ($Key[0] == 's' || $Key[1] == 'o' || $Key[2] == '_') { ; } else { continue; }
     $SO[$Val] = '1'; // ключ = 1
    }
  $sSO = mysql_real_escape_string(json_encode($SO));
  
  // Дополнительные параметры
  $sPV = '';
  //$sPV = mysql_real_escape_string(json_encode($sPV));
  
  if ($IdParent)
    {
     $isCheckPass = false;     
     if (strlen($_POST['psw1']) || strlen($_POST['psw2']))
       {
        $isCheckPass = true;
        $Salt = $CMSA->GenUStr(); // генерация соли
        $_POST['salt_pass'] = ' ,salt="'.$Salt.'", apass_hash="'.sha1($_POST['psw1'].$Salt).'" '; // генерация пароля
       }
       
     $Scpt = 'UPDATE x_user SET ts_upd='.time().'
     ,alogin = "'.$_POST['alogin'].'"
     ,user = "'.$_POST['user'].'"
     ,pv = "'.$sPV.'"
     ,access = "'.$sSO.'"
     '.$_POST['salt_pass'].'
     WHERE id='.$IdParent;
    }
  else
    {
     $isCheckPass = true;
     $Salt = $CMSA->GenUStr(); // генерация соли
     $_POST['apass_hash'] = sha1($_POST['psw1'].$Salt); // генерация пароля

     $Scpt = 'INSERT INTO x_user(ts_ins, ts_upd, ts_last, alogin, user, apass_hash, salt, pv, access) VALUES('.time().' ,'.time().'
     ,0
     ,"'.$_POST['alogin'].'"
     ,"'.$_POST['user'].'"
     ,"'.$_POST['apass_hash'].'"
     ,"'.$Salt.'"
     ,"'.$_POST['pv'].'"
     ,"'.$sSO.'"
     )';
    }

  if ($isCheckPass)
    {
     if (strlen($_POST['psw1']) < 6 ||
         strlen($_POST['psw2']) < 6)
       {
        $Ret['err'] = '<div class="msgErr"><span>Пароль должен быть не короче 6 символов.</span></div>';
        return json_encode($Ret);
       }

     if ($_POST['psw1'] != $_POST['psw2'])
       {
        $Ret['err'] = '<div class="msgErr"><span>Пароль необходимо указать дважды одинаково.</span></div>';
        return json_encode($Ret);
       }
    }

  // Проверить логин на дубликат  
  $Row = $CMSA->DB->GetRec('x_user', 'alogin="'.$_POST['alogin'].'"');
  if ($Row['id'] && $IdParent != $Row['id'])
    {
     $Ret['err'] = '<div class="msgErr"><span>Логин <b>'.htmlspecialchars($_POST['alogin']).'</b> логин уже занят, выберите другой.</span></div>';
     return json_encode($Ret);
    }
  
  $CMSA->DB->Exec($Scpt);
  if ($CMSA->DB->ErrNo)
    {
     $Ret['err'] = '<div class="msgErr"><span>Ошибка: '.$CMSA->DB->ErrNo.': '.$CMSA->DB->Error.' </span></div>';
    }
    
  //$Ret['err'] = '<pre>'.print_r($_POST, true).'</pre>';
  return json_encode($Ret);
 }*/

 
 /**
  * Блокировать/разблокировать учетную запись пользователя
  * 
  * @param int $IdUser - ID пользователя (x_cms_user.id)
  * @return cod Возвращает HTML-код
  */
 /*public function UserLockUnlock($IdUser)
 {
  global $CMSA;
  $Ret = array();
  $Cod = '';

  settype($IdUser, 'integer');
  if ($_POST['state']) { $_POST['state'] = '1'; } else { $_POST['state'] = '0'; }

  if ($IdUser)
    {
     $Scpt = '
     UPDATE x_user
     SET ts_upd   = '.time().'
        ,is_lock  = '.$_POST['state'].'
     WHERE id = '.$IdUser;
    }

  $CMSA->DB->Exec($Scpt);
  if ($CMSA->DB->ErrNo)
    {
     $Ret['trc'] = 'Ошибка #'.$CMSA->DB->ErrNo.' при записи данных. '.$Scpt;
    }
  else
    {
     $Ret['data'] = $this->UserLockState($_POST['state']);
    }

  $Ret['ret'] = 'ok';
  return json_encode($Ret);
 } */

 
 /**
  * Удаляет учетную запись пользователя 
  * 
  * @param int $IdUser - ID пользователя (x_user.id)
  * @return array Возвращает массив
  */
 function siteusersDelete($IdUser)
 {
  global $CMS, $CMSA;
  
       if ($CMSA) { $localCMS = $CMSA; }
  else if ($CMS)  { $localCMS = $CMS;  }
  
  $Ret = array();
  $Ret['errno'] = 0;
  $Ret['error'] = '';

  settype($IdUser, 'integer');
  
  if ($IdUser > 0)
    {
     $Scpt = 'DELETE FROM x_user WHERE id='. $IdUser;
    }
  else
    {
     $Ret['errno'] = 1;
     $Ret['error'] = 'Запись не указана';
    }
    
  $localCMS->DB->Exec($Scpt);
  if ($localCMS->DB->ErrNo)
    {
     $Ret['errno'] = $localCMS->DB->ErrNo;
     $Ret['error'] = $localCMS->DB->Error;
    }
    
  return $Ret;
 } 

