<?php
/**
 * @name IDentity
 * @copyright 2017, p.dev
 * @link http://potapov.com.ua/cms/IDentity/
 */

 require_once('IDentity.php');
 require_once('core/mods/SiteUsers/usersctrl.php'); // User authorization
 require_once('core/mods/PromoCodes/PromoCodes.php'); // Promo codes

 /**
  * Render BODY
  *
  * @return string HTML
  */
 function WebAppRun()
 {
  $S = '<script> $(document).ready(function() { WebAppRun(); }); </script>';
  return $S;
 }


 /**
  * Render HEAD 
  *
  * @return string HTML
  */
 function IDentityGenHead()
 {
  global $CMS;
  return '<script> var apiHref = "aj-IDentity"; </script>
<script type="text/javascript" src="http://'. $CMS->BP .'/core/mods/IDentity/f_IDentity.js"></script>
';
 }


 /**
  * Ajax router, thros to IDentity webapp
  * 
  * @return string JSON
  */
 function IDentityAjaxContent()
 {
  global $CMS, $WebApp;
  $WebApp = new TIDentity([ 
    'db' => $CMS->DB,
    'path' => $_SERVER['DOCUMENT_ROOT'] . '/core/mods/IDentity/'
    ]);

  echo $WebApp->Route($_GET['cmd']);
 }

