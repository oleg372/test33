<?php
/**
 * IDendity
 * v1.0, 20.09.2017
 * User report render
 */

 function RenderReport00($In)
 {
  $S = '';

  $HumanTypePic = '<img src="/'.$In['human_type']['pic'].'">';
  $HumanType = $In['human_type']['human_type'];

  $Str = /*trim*/($In['report_profile']);

  $i = 0; $Len = strlen($Str);
  $is = false;
  $S2 = '';
  for ($i = 0; $i < $Len; $i ++)
     {
      if ($Str[$i] == '#') { $is = true; $S2 .= '<a class="pseudo js-hash-tag">#'; continue; }

      if ($is)
        {
         if ($Str[$i] == ';'/* || $Str[$i] == ','*/) { $is = false; $S2 .= '</a>'; continue; }
         elseif ($Str[$i] == "\r" || $Str[$i] == "\n") { $S2 .= ' '; continue; }
        }

      $S2 .= $Str[$i];

     }
  $Str = $S2;

  $Str = str_replace([
    '[+',
    '[!',
    '[~',
    ']',
    '/*', '*/',
    '//+V', '//+', '//',
    "\r\n- ", "\r\n* ",
    "\r\n\r\n",
    '****'
    ], [
    '<span class="b">',
    '<span class="hot">',
    '<span class="i">',
    '</span>',
    '<tr><td>', '</td></tr>',
    '</td><td rowspan="2">', '</td><td colspan="2">', '</td><td>',
    "<br>- ", "<br>* ",
    "<br><br>",
    '****'
    ], $Str);

  $Str = str_replace([
    "<td><br><br><br><br>",
    "<td><br><br><br>",
    "<td><br><br>",
    "<td><br>",
    '<td rowspan="2"><br><br><br><br>',
    '<td rowspan="2"><br><br><br>',
    '<td rowspan="2"><br><br>',
    '<td rowspan="2"><br>',
    '<td colspan="2"><br><br><br><br>',
    '<td colspan="2"><br><br><br>',
    '<td colspan="2"><br><br>',
    '<td colspan="2"><br>',
    '<br><br><br><br></td>',
    '<br><br><br></td>',
    '<br><br></td>',
    '<br></td>',
    '{human_type}',
    '{human_type_s}'
    ], [
    "<td>",
    "<td>",
    "<td>",
    "<td>",
    '<td rowspan="2">',
    '<td rowspan="2">',
    '<td rowspan="2">',
    '<td rowspan="2">',
    '<td cowspan="2">',
    '<td cowspan="2">',
    '<td cowspan="2">',
    '<td cowspan="2">',
    '</td>',
    '</td>',
    '</td>',
    '</td>',
    $HumanTypePic,
    $HumanType
    ], $Str);

  /*
  $re = '/#(.*)(;|,)/m';
  $subst = '<a class="pseudo js-hash-tag">#$1</a>';
  $Str = preg_replace($re, $subst, $Str);
  */
  //$Str = '<table>'.nl2br(trim($Str)).'</table>';
  $Str = '<table>'.(trim($Str)).'</table>';

  $S = $Str;

  return $S;
 }

/* ------------
Написание хэш тегов:
  #бла бла бла;
  в конце хеш тэга обязательно знак ";"

Жирный текст:
  [+в таких скобках вписать жирный текст]
  Хеш теги НЕ нужно выделять жирным!

Жирный красный текст:
  [!в таких скобках вписать жирный текст]
  Хеш теги НЕ нужно выделять жирным!

Italic текст:
  [~в таких скобках вписать жирный текст]
  Хеш теги НЕ нужно выделять жирным!
------------ */

 function RenderReport($In)
 {
  $HumanTypePic = '<img src="/'.$In['human_type']['pic'].'">';
  $HumanType = $In['human_type']['human_type'];

  $Src = trim($In['report_profile']); // Source to render

  $Len = strlen($Src);
  $is = [];
  $Out = ''; // Out
  $Line = 0;
  for ($i = 0; $i < $Len; $i ++)
     {
      if ($Src[$i] == '#') 
        { // Начало хеш тега
         $Out .= '<a class="hash-tag js-hash-tag">#'; 
         $is['hash'] = true; 
         continue; 
        }
      if ($is['hash'])
        {
         if ($Src[$i] == ';') 
           {  // Конец хеш тега
            $Out .= '</a>'; 
            $is['hash'] = false; 
            continue; 
           }
         elseif ($Src[$i] == "\r" || 
                 $Src[$i] == "\n") 
           { // в хеш теге не может быть переноса на след строку
            //$Out .= ' '; 
            continue; 
           }
        }

      if ($Src[$i  ] == '/' &&
          $Src[$i+1] == '*')
        { // начало строки и ячейки
         if ($Line == 0) { $Out .= '<thead>'; }
         $Out .= '<tr><td>';
         $is['tr'] = true;
         $i += 1; continue;
        }

      if ($is['tr'] &&
          $Src[$i  ] == '*' &&
          $Src[$i+1] == '/')
        { // Конце ячейки и строки
         $Out .= '</td></tr>';
         $is['tr'] = false;
         if ($Line == 0) { $Out .= '</thead><tbody>'; }
         $Line ++;
         $i += 1; continue;
        }

      if ($Src[$i  ] == '/' &&
          $Src[$i+1] == '/' &&
          $Src[$i+2] == '+' &&
         ($Src[$i+3] == 'V' || $Src[$i+3] == 'v'))
        { // Конце ячейки и начало следующей спаренной по вертикале ячейкой
         $Out .= '</td><td rowspan="2">';
         $i += 3; continue;
        }
      elseif ($Src[$i  ] == '/' &&
              $Src[$i+1] == '/' &&
              $Src[$i+2] == '+')
        { // Конце ячейки и начало следующей спаренной по горизонтале ячейкой
         $Out .= '</td><td colspan="2">';
         $i += 2; continue;
        }
      elseif ($Src[$i  ] == '/' &&
              $Src[$i+1] == '/')
        { // Конце ячейки и начало следующей
         $Out .= '</td><td>';
         $i += 1; continue;
        }

      // Форматирование строк
      if ($Src[$i  ] == '-' &&
         ($Src[$i-1] == "\r" || $Src[$i-1] == "\n"))
        {
         $Out .= '<br>';
        }
      if ((ord($Src[$i]) >= ord('0') && ord($Src[$i]) <= ord('9')) &&
          $Src[$i+1] == '.' &&
         ($Src[$i-1] == "\r" || $Src[$i-1] == "\n"))
        {
         $Out .= '<br>';
        }
      /*if ($Src[$i  ] == "\r" &&
          $Src[$i+1] == "\n" &&
          $Src[$i+2] == "\r" &&
          $Src[$i+3] == "\n")
        {
         $Out .= '<br><br>';
         $i += 3; continue;
        }*/


      $Out .= $Src[$i];
     }

  $Out = '<table class="full-w report-profile">'.$Out.'</tbody></table>'; // Обрамить в таблицу

  // Форматирование текста: жирный / жаркий / италик 
  $Out = str_replace([
    '[+',
    '[!',
    '[~',
    ']',
    '{human_type}',
    '{human_type_s}'
    ], [
    '<span class="b">',
    '<span class="hot">',
    '<span class="i">',
    '</span>',
    $HumanTypePic,
    $HumanType
    ], $Out);

  //file_put_contents('core/mods/IDentity/~~~.html', $Out);
  return $Out;
 }
