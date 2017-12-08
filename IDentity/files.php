<?php
/*----------
 * Files controller
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
 * @name Files controller
 * @desc Manage upload/download files for users
 * @version 1.00, 21.11.2017 (begins 21.11.2017)
 * @author Potapov Oleg
 * @copyright 2017, Potapov development
 */

define('FILES_INVALID_USER_ID', 1);
define('FILES_INVALID_KEY',     2);
define('FILES_NO_FILES',        3);
define('FILES_INVALID_INPUT',   4);

class FilesCtrl
{
 private $DB; // TDB class
 private $DBSet; // Database settings
 private $UserId; // current signed in user
 private $Path;


 /**
  * Constructor
  *
  * @param TDB [db]  - Resource to TDB
  * @param int [user_id] - Current USER ID for processing
  * @param string [lang] - Language support (ru/en/ua)
  * @param string [path] - Path where stored user files
  */
 public function __construct($In)
 {
  //if (!isset($In['lang'])) { $In['lang'] = 'en'; }

  $this->DB = $In['db']; // Database object

  $this->Path = '';
  if (isset($In['path'])) 
    {
     $this->Path = $In['path']; 
     if ($this->Path[strlen($this->Path)-1] != '/') 
         $this->Path .= '/'; // Slash at end of path is important
    }

  $DBSet = [
    'table' => 'id_files'
    ];

  if (isset($In['user_id'])) 
       { $this->SetUserId($In['user_id']); }
  else { $this->SetUserId(0); } // No user
 }


 /**
  * Get available files to download
  * Return's array with file names and information about it
  *
  * @param int [user_id] - Specified USER ID (optional). If not set, use USER ID preset from constructor
  * @return array
  */
 public function GetFiles($In = [])
 {
  if (isset($In['user_id']))
     $OwnerId = sprintf('%d', $In['user_id']);
  else
     $OwnerId = $this->UserId;

  if ($OwnerId <= 0) return ['err' => FILES_INVALID_USER_ID];

  $Out = [
    'total' => [
      'count' => 0,
      'size' => 0
      ],
    'data' => [
      ]
    ];

  // Get all files descriptors from DB
  $Recs = $this->DB->Exec2Arr('SELECT file_id, file_name, show_name FROM `'. $this->DBSet['table'] .'` WHERE owner_id='.$OwnerId.' ORDER BY show_name', '');
  foreach ($Recs as $Row)
    {
     $File = $this->Path.$Row['file_name'];
     $Info = pathinfo($File);
     $Size = filesize($File);
     if ($Size < 0) { $Size = 0; }

     // Add file information to out array
     $Out['data'][] = [
       'show' => $Row['show_name'],
       'file' => $File,
       'ext' => $Info['extension'],
       'size' => $Size,
       'owner_id' => $OwnerId
       ];

     // Increase total's
     $Out['total']['count'] ++;
     $Out['total']['size'] += $Size;
    }

  return $Out;
 }


 /**
  * Upload files from $_FILES
  * Return's array with processed files
  *
  * @param int [user_id] - Specified USER ID (optional). If not set, use USER ID preset from constructor
  * @param string [key] - Key name in $_FILES
  * @return array
  */
 public function GetFiles($In = [])
 {
  if (isset($In['user_id']))
     $OwnerId = sprintf('%d', $In['user_id']);
  else
     $OwnerId = $this->UserId;

  if ($OwnerId <= 0) 
     return ['err' => FILES_INVALID_USER_ID];

  $Key = 'upload';
  if (isset($In['key'])) 
     $Key = trim($In['key']);

  if (!strlen($Key)) 
     return ['err' => FILES_INVALID_KEY];

  if (!$_FILES[$Key]) 
     return ['err' => FILES_NO_FILES];

  $Files = $this->reArrayFiles($_FILES[$Key]);

  if (!count($Files))
     return ['err' => FILES_INVALID_INPUT];

  $Out = [
    'success' => [ // Only successfully uploaded files
      'count' => 0,
      'size' => 0
      ],
    'data' => [
      ]
    ];

  foreach ($Files as $Item)
    {
     if ($Item['error'])
       { // Error happen's at upload
       }
     else
       { // No error
        //$Ext = pathinfo($Item['name'])['extension'];

        // Create a record in DB and use it ID + file extension
        $FileName = '';//basename($Item['name']); // Get file name

        // Move uploaded file in folder name owner's ID
        $Result = move_uploaded_file($Item['tmp_name'], $this->Path.$Name);

        // If error happen's remove record from DB
        if ($Result == false)
          { // Remove record
           $Item['error'] = -1;
          }
        else
          { // Success
           $Out['success']['count'] ++;
           $Out['success']['size'] += $Item['size'];
          }
       }

     $Out['data'][] = [
       'file' => $Item['name'],
       'size' => $Item['size'],
       'error' => $Item['error'],
       'owner_id' => $OwnerId
       ];

//       $Item
//          [name] => foo.txt
//          [type] => text/plain
//          [tmp_name] => /tmp/phpYzdqkD
//          [error] => 0
//          [size] => 123
//        More here: http://php.net/manual/ru/features.file-upload.post-method.php
    }

  return $Out;
 }


 /**
  * Get current USER ID
  *
  * @return integer
  */
 public function GetUserId()
 {
  return $this->UserId;
 }


 /**
  * Set USER ID
  *
  * @param int $Id
  * @return void
  */
 public function SetUserId($Id)
 {
  $this->UserId = sprintf('%d', $Id);
 }


 /**
  * http://php.net/manual/ru/features.file-upload.multiple.php#53240
  */
 private function reArrayFiles(&$file_post) 
 {
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }

    return $file_ary;
 }


}
