<?php
/**
 * @name SiteData
 * @copyright 2015, Potapov studio
 * @link http://potapov.com.ua/cms/SiteData/
 */


 /**
  * Сгенерировать HEAD 
  *
  * @return string Возвращает заголовок для страницы
  */
 function sitedataGenHead()
 {
  global $CMS;
  return '<script type="text/javascript" src="http://'. $CMS->BP .'/core/mods/SiteData/f_SiteData.js"></script>'."\r\n";
  /*$Cod = '
<script type="text/javascript">
'.str_replace('{aj}', 'aj.php?m=sitedata&act=', file_get_contents('core/mods/sitedata/f_'.$ModNameL.'.js')).'
</script>
  ';
  $Cod = str_replace('{adm}', 'http://'.$CMS->BP.'/in?msg=tskdel', $Cod);

  return $Cod;*/
 }


 /**
  * Управление аякс запросами, имя функции должно быть строго идентичну названию модуля
  * 
  * @return cod Возвращает HTML-код
  */
  function sitedataAjaxContent()
  {
   global $CMS;
   // $CMS->ModAttach('siteusers'); // !!!!!!!!!!! этого не должно быть тут
   // SiteUsersCheckAuth();   // !!!!!!!!!!! этого не должно быть тут
   $Cod = '';
   switch ($_GET['act'])
     {
      case 'popupform':  { $Cod .= PopupForm(); break; }
      case 'popupformp': { $Cod .= PopupFormPost(); break; }
      case 'addMessage': { $Cod .= json_encode(AddFeedback($_POST)); break; }
      default:           { if ($_GET['j'] == '1') { $Cod .= json_encode(array('data' => 'Invalid request!+')); } else { $Cod .= 'Invalid request!-'; } break; }
     }
   echo $Cod;
  }


 /**
  * Popup форма!
  *
  * @return string Возвращает HTML-код
  */
 function PopupForm()
 {
  global $CMS;
  $S = '';

  $S = FormRender($_POST['form']);

  return $S;
 }


 /**
  * Popup форма! POST
  *
  * @return string Возвращает HTML-код
  */
 function PopupFormPost()
 {
  global $CMS, $SiteUser;

  $Ret = array(
   'code' => 200 // 200 - no error
  ,'msg' => '' // message to show
  ,'goto' => '' // redirect if needed
  );

  //$Ret['code'] = 1;
  //$Ret['msg'] = 'this is a message';


  //$CMS->ModAttach('siteusers'); // !!!!!!!!!!! этого не должно быть тут

  $S = '';

  $pre = trim($_GET['f']);
  if (strlen($pre) < 3) $pre = '';

  $_POST['ts_ins'] = time();

  $In = array_merge($_GET, $_POST);

  switch ($_POST['f_formName'])
    {
     case 'SendEmail2Company': { $Ret = array_merge($Ret, Form_SendEmail($In)); break; }
     default: { break; }
    }



    { // Не найдено среди описаний форма, скинуть в файл!
     $_POST['_ret'] = $Ret;
     foreach($_POST as $Key => $Val)
       {
        // Преобразовать TS в дату время
        if ($Key[0] == 't' && $Key[1] == 's' && $Key[2] == '_')
          {
           $Key[0] = 'd';
           $Key[1] = 't';
           $_POST[$Key] = date('d.m.Y H:i:s', $Val);
          }
       }

     $s = json_encode($_POST);
     $f = 'formfill/'.$pre.'_'.date('Y-m-d_H.i.s', time()).'.json';
     file_put_contents($f,$s);
    }

  return json_encode($Ret);
 }


 /**
  * Send e-mail message to company
  *
  * @return string Возвращает HTML-код
  */
 function Form_SendEmail($In)
 {
  global $CMS, $SiteUser;

  $Ret = array(
   'code' => 200 // 200 - no error
  ,'msg' => '' // message to show
  ,'goto' => '' // redirect if needed
  );

  $In['id_company'] = sprintf('%d', $In['id_company']);

  $Company = $CMS->DB->Exec1st('SELECT id, company, company_url, is_top10, email, phones, addr, zip, offers FROM cat_company WHERE id='.$In['id_company']);

  $Ret2 = SaveMail2Comp([
    'id_company' => $Company['id']
   ,'name' => $In['name']
   ,'phone' => $In['tel']
   ,'email' => $In['email']
   ,'message' => $In['message']
  ]);

  // send rendered mail
  $Subj = 'Message from '. $In['name'] .'. SuperiorBuyersReport.org';
  $Body = '<p>Hello, <b>'. $Company['company'] .'</b></p>
<p>You got a request from <b>'. $In['name'] .'</b>.</p>
<p>Message: - - - - - - - - - - <br><b>'. nl2br($In['message']) .'</b><br>- - - - - - - - - - - - - - -</p>
<p>E-mail for reply: <b>'. $In['email'] .'</b></p>
<p>Phone (if avail.): '. $In['tel'] .'</p>
<p>--<br>&nbsp;<b>Superior<b style="color:#48c7ec">Buyers</b>Report</p>';


  $mgAPI = array('ApiKey' => 'key-110df1b16bb07285ef4e7251454f0ad9','Domain' => 'm.superiorbuyersreport.org');

  $toEmail = $Company['email'];
  require '_mailgun.php'; // Library
  $ret1 = MaingunSend($toEmail,  'SuperiorBuyersReport postmaster@m.superiorbuyersreport.org', $Subj, $Body, $mgAPI);


  $Ret['_a'] = $In;
  $Ret['_c'] = $Company;
  $Ret['_save2comp'] = $Ret2;
  //$Ret['_mg'] = $ret1;


  return $Ret;
 }


 /**
  * Save sent message to company
  *
  * @return bool Returns true on success, otherwise - false
  */
 function SaveMail2Comp($In)
 {
  global $CMS;

  $CMS->DB->SetFV([
    'id_company' => $In['id_company']
   ,'name' => $In['name']
   ,'phone' => $In['phone']
   ,'email' => $In['email']
   ,'message' => $In['message']
  ]);
  $CMS->DB->MakeInsert('cat_mail');
  if ($CMS->DB->ErrNo) { return false; }
  return true;
 }


 /**
  * Render form from template to HTML
  *
  * @return string Возвращает HTML-код
  */
 function FormRender($Form)
 {
  global $CMS;
  $S = $CMS->GetBlock($Form);

  $Data = array();
  $Data['_form'] = str_replace('Form_', '', $Form);

  if ($_GET['id_company'] > 0)//$Data['_form'] == 'SendEmail2Company')
    { // SendEmail2Company
     $Company = $CMS->DB->Exec1st('SELECT id, company, company_url, is_top10, email, phones, addr, zip, offers FROM cat_company WHERE id='.sprintf('%d', $_GET['id_company']));
     $Data = array_merge($Data, $Company);
    }

  /*
  $Data['region'] = 
   GenCB_x('f_id_region', 'SELECT id, region as `item` FROM x_user_region ORDER BY item', $IdSel, 'js-req', true, 'data-err="Виберіть область"');
  $Data['disq_scheduler'] = 
   GenCB_x('f_id_scheduler', 'SELECT id, CONCAT(city, ", ", data)  as `item` FROM disq_scheduler WHERE is_pub=1 ORDER BY item', $IdSel, 'js-req', true, 'data-err="Виберіть місто"');
  */

  $S = $CMS->Gen($S, $Data);

  return $S;
 }


 /**
  * Save feedback to Data base
  */
 function AddFeedback($In)
 {
  global $CMS, $SiteUser;

  $Ret = array(
   'code' => 200 // 200 - no error
  ,'msg' => '' // message to show
  ,'goto' => '' // redirect if needed
  );

  //$Company = $CMS->DB->Exec1st('SELECT id, company, company_url, is_top10, email, phones, addr, zip, offers FROM cat_company WHERE id='.sprintf('%d', $_GET['id_company']));
  $CMS->DB->SetFV([
    'is_pub' => 0
   ,'ts_pub' => time()
   ,'rating' => sprintf('%d', $In['rating'])
   ,'fl_name' => $In['f_l_name']
   ,'msgs' => $In['msg']
   ,'email' => $In['email']
  ]);
  $CMS->DB->MakeInsert('msgs');

  if ($CMS->DB->ErrNo)
    {
     $Ret['code'] = 401;
     $Ret['msg'] = $CMS->DB->Error;
    }

  return $Ret;
 }


 /**
  * Сгенерировать фиксированый выпадающий список
  *
  * @return string Возвращает HTML-код
  */
 function GenCB_x($IdName, $Scpt, $IdSel = 0, $Class = '', $isEmpty = true, $DE = '', $isItemsOnly = false)
 {
  global $CMS;
  $Recs = $CMS->DB->Exec($Scpt);
  if ($CMS->DB->ErrNo) return '';

  if (!$isItemsOnly) { $Cod = '<select name="'.$IdName.'" id="'.$IdName.'" class="'.$Class.'" '.$DE.'>'; }
  if ($isEmpty)
    {
     //$Cod .= '<option value="">&mdash;</option>';
     $Cod .= '<option value=""></option>';
    }
  while (($Row = mysql_fetch_assoc($Recs)))
    {
     if ($IdSel == $Row['id']) { $Sel = ' selected="selected"'; } else { $Sel = ''; }
     $Cod .= '<option value="'.$Row['id'].'" '.$Sel.'>'.$Row['item'].'</option>';
    }
  if (!$isItemsOnly) { $Cod .= '</select>'; }
  return $Cod;
 }


 // LOGOSTRIP -----------------------------------------------------------------


 /**
  * Render logostrip
  *
  * @return string Возвращает HTML-код
  */
 function logostripRun($IdLogostrip = 0, $Qty = 0)
 {
  global $CMS, $SiteUser;
  
  if ($IdLogostrip <= 0) return '';
  $IdSlider = sprintf('%d', $IdLogostrip);

  $TmplList = file_get_contents('core/mods/SiteData/logostrip_tmpl_list.html');
  $TmplItem = file_get_contents('core/mods/SiteData/logostrip_tmpl_item.html');

  if ($Qty > 0) { $Limit = sprintf('LIMIT 0,%d', $Qty); }

  $html = '';
  $Recs = $CMS->DB->Exec2Arr('SELECT * FROM logostrip_item WHERE ts_del=0 AND is_pub=1 AND id_list='.$IdLogostrip.' ORDER BY pos '.$Limit);
  if (count($Recs) < 0) { return ''; }
  foreach ($Recs as $Row)
    {
     $Row['i_pic'] = json_decode($Row['i_pic'], true);
     if (!is_array($Row['i_pic'])) continue;
     $Row['i_orig']  = 'data/'.$Row['i_pic']['orig'];
     //$Row['i_thumb'] = 'data/'.$Row['i_pic']['small'];
     $html .= $CMS->Gen($TmplItem, $Row);
    }

  $S .= $CMS->Gen($TmplList, array('items' => $html));

  return $S;
 }


 // FAQ -----------------------------------------------------------------------


 /**
  * Render FAQ
  *
  * @return string Возвращает HTML-код
  */
 function FAQ($IdGroup = 0, $Qty = 0)
 {
  global $CMS, $SiteUser;
  
  if ($IdGroup <= 0) return '';
  $IdGroup = sprintf('%d', $IdGroup);

  $TmplList = file_get_contents('core/mods/SiteData/faq_tmpl_list.html');
  $TmplItem = file_get_contents('core/mods/SiteData/faq_tmpl_item.html');

  if ($Qty > 0) { $Limit = sprintf('LIMIT 0,%d', $Qty); }

  $html = '';
  $Recs = $CMS->DB->Exec2Arr('SELECT * FROM faq_item WHERE ts_del=0 AND is_pub=1 AND id_list='.$IdGroup.' ORDER BY pos '.$Limit);
  if (count($Recs) < 0) { return ''; }
  foreach ($Recs as $Row)
    {
     //$Row['i_pic'] = json_decode($Row['i_pic'], true);
     //if (!is_array($Row['i_pic'])) continue;
     //$Row['i_orig']  = 'data/'.$Row['i_pic']['orig'];
     $html .= $CMS->Gen($TmplItem, $Row);
    }

  $S .= $CMS->Gen($TmplList, array('items' => $html));

  return $S;
 }


 // Our Team ------------------------------------------------------------------


 /**
  * Render 
  *
  * @return string Return's HTML-код
  */
 function OurTeam()
 {
  global $CMS, $SiteUser;
  
  $SocFields = [
   'link_fb' => '<i style="color:#3b5998" class="fa fa-facebook-square" aria-hidden="true"></i>', 
   'link_in' => '<i style="color:#0077b5" class="fa fa-linkedin-square" aria-hidden="true"></i>', 
   'link_gp' => '<i style="color:#d34836" class="fa fa-google-plus-square" aria-hidden="true"></i>', 
   'link_tw' => '<i style="color:#4099FF" class="fa fa-twitter-square" aria-hidden="true"></i>'];

  $TmplList = file_get_contents('core/mods/SiteData/ourteam_tmpl_list.html');
  $TmplItem = file_get_contents('core/mods/SiteData/ourteam_tmpl_item.html');
  $TmplSoc  = file_get_contents('core/mods/SiteData/ourteam_tmpl_item_social.html');

  $Recs = $CMS->DB->Exec2Arr('SELECT * FROM our_team WHERE ts_del=0 AND is_pub=1 ORDER BY pos');
  if (count($Recs) < 0) { return ''; }

  $html = '';
  foreach ($Recs as $Row)
    {
     $Row['i_pic'] = json_decode($Row['i_pic'], true);
     if (!is_array($Row['i_pic'])) { $Row['i_pic'] = array(); }
     $Row['i_pic'] = 'data/'.$Row['i_pic']['small'];

     foreach ($SocFields as $Field => $ico)
       {
        if (!strlen(trim($Row[$Field]))) continue;
        $Arr = [ 'link' => $Row[$Field], 'ico' => $ico];
        $Row['social_links'] .= $CMS->Gen($TmplSoc, $Arr);
       }

     $html .= $CMS->Gen($TmplItem, $Row);
    }

  $S .= $CMS->Gen($TmplList, array('items' => $html));

  return $S;
 }


 // Career ------------------------------------------------------------------


 /**
  * Render 
  *
  * @return string Return's HTML-код
  */
 function Career()
 {
  global $CMS, $SiteUser;

  $TmplList = file_get_contents('core/mods/SiteData/career_tmpl_list.html');
  $TmplItem = file_get_contents('core/mods/SiteData/career_tmpl_item.html');

  $Id = sprintf('%d', $CMS->URL[1]);
  if ($Id > 0) { $Excl = ' AND id NOT IN ('.$Id.') '; }

  $Recs = $CMS->DB->Exec2Arr('SELECT * FROM career WHERE ts_del=0 AND is_pub=1 '.$Excl.' ORDER BY pos');
  if (count($Recs) < 0) { return ''; }

  $html = '';
  foreach ($Recs as $Row)
    {
     $html .= $CMS->Gen($TmplItem, $Row);
    }

  $S .= $CMS->Gen($TmplList, array('items' => $html));

  return $S;
 }


 /**
  * Render detail 
  *
  * @return string Return's HTML-код
  */
 function CareerDetail()
 {
  global $CMS, $SiteUser;

  $Id = sprintf('%d', $CMS->URL[1]);
  if ($Id <= 0) return '';

  $TmplList = file_get_contents('core/mods/SiteData/career_tmpl_detail.html');

  $Row = $CMS->DB->Exec1st('SELECT * FROM career WHERE ts_del=0 AND is_pub=1 AND id='.$Id);
  if ($Row['id'] < 0) { return ''; }

  $S .= $CMS->Gen($TmplList, $Row);

  $CMS->SetTitle('We’re Hiring &quot;'.$Row['vacancy'].'&quot; in Superior Buyers Report.');
  $CMS->SetDescr($Row['abstr']);

  return $S;
 }

