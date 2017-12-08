<?php
/**
 * @name SiteData
 * @copyright 2015, Potapov studio
 * @link http://potapov.com.ua/cms/SiteData/
 */

class TAdmsitedata
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
  $this->Ver = '3.0';
 }
 
 
 /**
  * Получить заговолов страницы
  *
  * @return string Возвращает заголовок для страницы
  */
 public function GetTitle()
 {
  return 'Misc. site data';
 }


 /**
  * Сгенерировать HEAD 
  *
  * @return string Возвращает заголовок для страницы
  */
 public function GenHead()
 {
  global $CMSA;
  $ajl = 'aj-mm='.$_GET['mm'].'&sm='.$_GET['sm'].'&act='; // AJaxLink
 
  // С менеджером таблиц:
  $Cod = $CMSA->AttachTblMng(true);
  $Cod .= '
<script type="text/javascript">
'. str_replace('{aj}', $ajl, file_get_contents($this->WorkPath . 'adm_SiteData.js')) .'
</script>
';
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

  return $Cod;
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
     case 'msgs':      { $Cod = $this->PgMsgs(); break; }
     case 'faq':       { $Cod = $this->PgFAQ(); break; }
     case 'logostrip': { $Cod = $this->PgLogostrip(); break; }
     case 'sitelogo':  { $Cod = $this->PgSitelogo(); break; }
     case 'ourteam':   { $Cod = $this->PgOurTeam(); break; }
     case 'careers':   { $Cod = $this->PgCareers(); break; }
     default:          { $Cod = $this->PgHome(); break; }
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
     //case 'hideph': { $Cod .= $this->PhotoConDel($_GET['id']); break; }
     default:       { $Cod .= 'Invalid request!'; break; }
    }
  return $Cod;
 }


 /**
  * Генерирует главную страницу модуля
  * 
  * @return cod Возвращает HTML-код
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
  * Service list editor
  * 
  * @return cod Возвращает HTML-код
  */
 public function PgMsgs()
 {
  global $CMSA;
  $S = '';

  $TM = new TTableManager();
  $TM->Set(TM_ORDER, 'this.ts_ins DESC');
  $S .= $TM->Run('msgs');

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
  * Управление лентой логотипов
  * 
  * @return cod Возвращает HTML-код
  */
 public function PgLogostrip()
 {
  global $CMSA;
  $S = '';

  $S .= $this->InitIfNecessary('logostrip');

  $sParent = 'prntid';
  $IdParent = $_GET[$sParent];

  $TM = new TTableManager();

  if ($IdParent > 0)
    {
     $Parent = $CMSA->DB->Exec1st('SELECT logostrip_list.list_name as `item` FROM logostrip_list WHERE logostrip_list.id='.$IdParent);
     $S .= '<p>Images in group: <span style="color:maroon; font-weight:bold;">“'. $Parent['item'] .'”</span> <a href="?mm='.$_GET['mm'].'&sm='.$_GET['sm'].'" class="btn btn-small">Other groups &uarr;</a></p>';

     $TM->Set(TM_WHERE, 'this.id_list='.$IdParent);
     $TM->Set(TM_ORDER, 'this.pos,this.id');
     $TM->Set(TM_ORDERED_TABLE, 'pos');
     $TM->Set(TM_EVNT_BEFORE_LOAD, 'OnBeforeLoad_Crtf'); // Callback имя функции
     $TM->Set(TM_EVNT_BEFORE_SAVE, 'OnBeforeSave_Crtf'); // Callback имя функции
     $Head = 'Logotypes';//'Слайды в группе: <span style="color:yellow">“'. $Parent['item'] .'”</span>';
     $S .= $TM->Run('logostrip_item', $Head);
    }
  else 
    {
     $TM->Set(TM_ORDER, 'this.pos,this.id');
     $TM->Set(TM_ORDERED_TABLE, 'pos');
     $TM->Set(TM_SLAVE, $sParent);
     $S .= $TM->Run('logostrip_list');
    }

  $S .= '<p style="opacity:0.1">v1.00; templates in files</p>';

  return $S;

 }


 /**
  * Change site logotype
  * 
  * @return cod Возвращает HTML-код
  */
 public function PgSitelogo()
 {
  global $CMSA;

  // Accept files and replace current
  $Upl = array(
   'img_s' => '../../i/logo-s.png',
   'img_w' => '../../i/logo-witget.png',
   'img_b' => '../../i/logo-b.png'
   );
  $Orig = array(
   'img_s' => '../../i/logo-s-orig.png',
   'img_w' => '../../i/logo-witget-orig.png',
   'img_b' => '../../i/logo-b-orig.png'
   );

  foreach ($Upl as $Index => $File)  {
  if ($_FILES[$Index]['size'] > 0 &&
     !$_FILES[$Index]['error'])
    {
     chmod($File, 0777);
     $ret = move_uploaded_file($_FILES[$Index]['tmp_name'], $File);
     //$S .= sprintf('%d', $ret);
    }
  }

  if ($_GET['restore'] > 0)
    {
     $S .= '<div class="msgOk"><span>Default logo restored</span></div>';
     foreach ($Upl as $Index => $File)  
       {
        copy($Orig[$Index], $File);
       }
    }

  $S .= '<h3>Changing site logotype</h3>

  <form action="?mm=SiteData&sm=sitelogo" enctype="multipart/form-data" method="post">
    <p><strong>Select logotype images one or all and upload it, to change. Accept PNG format only.</strong></p>
    <p>90x90: <input type="file" name="img_s" accept="image/png"></p>
    <p>186x51: <input type="file" name="img_w" accept="image/png"></p>
    <p>988x988: <input type="file" name="img_b" accept="image/png"></p>
    <p>Notice: Upload images size exactly as shown under images. To get better image quality after resize use your desktop image editor like Photoshop / <a href="http://www.faststone.org/" target="_blank">FastStone Image Viewer</a> (free)</p>
    <p><input type="submit" value="Send" class="btn btn-primary"></p></p>
  </form>

  <hr>
  <p>Current logotypes:</p>
  <div style="float:left;clear:left;"><div style="border:1px solid #aaa;background:#fff;margin:20px 20px 0px 0;padding:2px;"><img src="/i/logo-s.png?rnd='.rand(1,1000).'"></div>90x90 logo in header</div>
  <div style="float:left;clear:left;"><div style="border:1px solid #aaa;background:#fff;margin:20px 20px 0px 0;padding:2px;"><img src="/i/logo-witget.png?rnd='.rand(1,1000).'"></div>186x51 logo in witget</div>
  <div style="float:left;clear:left;"><div style="border:1px solid #aaa;background:#fff;margin:20px 20px 0px 0;padding:2px;"><img src="/i/logo-b.png?rnd='.rand(1,1000).'"></div>988x988 big logo</div>
  <div class="clr"></div>

  <hr>
  <h3><a class="js-orig-logo pseudo" href="javascript:;">Original logotypes</a> &darr;</h3>
  <div class="js-orig-logo" style="display:none;">
  <div style="float:left;clear:left;"><div style="border:1px solid #aaa;background:#fff;margin:20px 20px 0px 0;padding:2px;"><img src="/i/logo-s-orig.png"></div>90x90 logo in header</div>
  <div style="float:left;clear:left;"><div style="border:1px solid #aaa;background:#fff;margin:20px 20px 0px 0;padding:2px;"><img src="/i/logo-witget-orig.png"></div>186x51 logo in witget</div>
  <div style="float:left;clear:left;"><div style="border:1px solid #aaa;background:#fff;margin:20px 20px 0px 0;padding:2px;"><img src="/i/logo-b-orig.png"></div>988x988 big logo</div>
  <div class="clr"></div>
  <hr>

  <p><a class="btn" href="?mm=SiteData&sm=sitelogo&restore=1">Restore default logotype</a></p>
  </div>
  ';

  return $S;
 }


 /**
  * Manage "Our team"
  * 
  * @return string return's HTML
  */
 public function PgOurTeam()
 {
  global $CMSA;
  $S = '';

  $TM = new TTableManager();
  $TM->Set(TM_ORDER, 'this.pos,this.id');
  $TM->Set(TM_ORDERED_TABLE, 'pos');
  //$TM->Set(TM_SLAVE, $sParent);
  $S .= $TM->Run('our_team');

  return $S;
 }


 /**
  * Manage "Careers" (for hire)
  * 
  * @return string return's HTML
  */
 public function PgCareers()
 {
  global $CMSA;
  $S = '';

  $TM = new TTableManager();
  $TM->Set(TM_ORDER, 'this.pos,this.id');
  $TM->Set(TM_ORDERED_TABLE, 'pos');
  //$TM->Set(TM_SLAVE, $sParent);
  $S .= $TM->Run('career', 'Careers');

  return $S;
 }






 /**
  * Service list editor
  * 
  * @return cod Возвращает HTML-код
  */
 /*public function PgBgImgTxt()
 {
  global $CMSA;
  $Cod = '';
 
  $TM = new TTableManager();
  $TM->Set(TM_ORDER, 'this.akey, this.pos, this.ts_pub DESC');
  $Cod .= $TM->Run('z_other');

  return $Cod;
 }*/

 /**
  * Testimonials editor
  * 
  * @return cod Возвращает HTML-код
  */
 /*public function PgTestimonials()
 {
  global $CMSA;
  $Cod = '';

  $TM = new TTableManager();
  $TM->Set(TM_ORDER, 'this.ts_pub DESC');
  $Cod .= $TM->Run('ec_testimonials');

  return $Cod;
 }            */


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


 /**
  * Выполняет инициализацию модуля (создает таблицы)
  *
  * @param string $Table - Имя таблицы для инициализации (одна шт)
  * @return string - Возвращает информацию об инициализации если была, иначе пусто.
  */
 private function InitIfNecessary($SubMod = '')
 {
  global $CMSA;
  /*
  _logostrip_item.sql
  _logostrip_list.sql
  _logostrip_list_dump.sql
  */

  if (!$SubMod) return false;

  if ($SubMod == 'logostrip') 
    {
     $S .= $this->InitDBTable('logostrip_list');
     $S .= $this->InitDBTable('logostrip_item');
    }
  else if ($SubMod == 'faq') 
    {
     $S .= $this->InitDBTable('faq_list');
     $S .= $this->InitDBTable('faq_item');
    }

  return $S;
 }


 /**
  * Выполняет инициализацию таблицы модуля
  *
  * @param string $Table - Имя таблицы для инициализации (одна шт)
  * @return string - Возвращает информацию об инициализации если была, иначе пусто.
  */
 private function InitDBTable($Table = '')
 {
  global $CMSA;
  $S = '';

  // Check table exists
  $iFound = $CMSA->DB->Exec1st('SELECT COUNT(*) as `qty` FROM information_schema.tables WHERE table_schema = "'. $CMSA->Init['dbname'] .'" AND table_name = "'. $Table .'"', 'qty');
  if (!$iFound) 
    { // Unexists
     $Scpt = file_get_contents($this->WorkPath .'_'.$Table.'.sql');
     $CMSA->DB->Exec($Scpt);
     if ($CMSA->DB->ErrNo)
       { return '<div class="msgErr"><span>Ошибка инициализации модуля</span></div><p>#'. $CMSA->DB->ErrNo .': '. $CMSA->DB->Error .'</p>'; }
     else if (file_exists($this->WorkPath .'_'.$Table.'_dump.sql'))
       {
        $Scpt = file_get_contents($this->WorkPath .'_'.$Table.'_dump.sql');
        $CMSA->DB->Exec($Scpt);
        if ($CMSA->DB->ErrNo)
          { return '<div class="msgErr"><span>Ошибка инициализации модуля (dump)</span></div><p>#'. $CMSA->DB->ErrNo .': '. $CMSA->DB->Error .'</p>'; }
       }

    }

  return $S;
 }

}


// FAQ ------------------------------------------------------------


/**
 * Callback: срабатывает перед началом редактированием/созданием записи
 * 
 * @param array $Row - Ассоциативный массив данных для обработки
 * @param int $IdRec - ID записи в таблице
 * @param array $GET - Содежимое $_GET. Так как работает на ajax, то напрямую $_GET не содержит нужных данных
 * @return array Возвращает ассоциативный массив с обработанными данными
 */
function OnBeforeLoad_FAQ($Row, $IdRec, $GET)
{
 if (!$IdRec)
   {
    $Row['id_list'] = $GET['prntid'];
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
function OnBeforeSave_FAQ($Row, $IdRec, $GET)
{
 return $Row;
}


// LOGOSTRIP ------------------------------------------------------------


/**
 * Callback: срабатывает перед началом редактированием/созданием записи
 * 
 * @param array $Row - Ассоциативный массив данных для обработки
 * @param int $IdRec - ID записи в таблице
 * @param array $GET - Содежимое $_GET. Так как работает на ajax, то напрямую $_GET не содержит нужных данных
 * @return array Возвращает ассоциативный массив с обработанными данными
 */
function OnBeforeLoad_Crtf($Row, $IdRec, $GET)
{
 if (!$IdRec)
   {
    $Row['id_list'] = $GET['prntid'];
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
function OnBeforeSave_Crtf($Row, $IdRec, $GET)
{
 //$Row['head'] = '|ура! живая и веселая!|'.$Row['head'];
 //file_put_contents('aaaa.txt', print_r($Row,true));
 return $Row;
}

