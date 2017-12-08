<?php
/**
 * @name SiteUsers
 * @copyright 2017, p.dev
 * @link http://potapov.com.ua/cms/SiteUsers/
 */

 require_once('usersctrl.php'); // core/mods/SiteUsers/  

 /**
  * Render HEAD 
  *
  * @return string HTML
  */
 function SiteUsersGenHead()
 {
  global $CMS;
  return '<script type="text/javascript" src="http://'. $CMS->BP .'/core/mods/SiteUsers/usersctrl.js"></script>';
 }


 /**
  * Ajax router, thros to IDentity webapp
  * 
  * @return string JSON
  */
 function SiteUsersAjaxContent()
 {
  /*global $CMS;
  $WebApp = new TIDentity([ 
    'db' => $CMS->DB,
    'path' => $_SERVER['DOCUMENT_ROOT'] . '/core/mods/IDentity/'
    ]);

  echo $WebApp->Route($_GET['cmd']);*/
 }
