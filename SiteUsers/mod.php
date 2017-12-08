<?php
/**
 * @desc Зарегистрированные пользователи
 * @name SiteUsers
 * @item Пользователи
 * @version 2.02, 08.09.2017
 * @author Potapov Oleg
 * @copyright 2013-2017, Potapov development
 * @link http://potapov.com.ua/cms/siteusers/
 * @class TSiteUsers
 */

{{
17.09.2017  v2.02  Added function TUsersCtrl::UpdateSN($SN) it is update JSON data in users_profile.sn
08.09.2017  v2.01  Полное разделение модуля. Управляющий объект в usersctrl.php
++++++++
10.11.2016  v1.06  Все расширения заменены на .php
19.02.2015  v1.05  Авторизация через соц.сети, используя https://ulogin.ru
++++++++
Набор ф-кций для управления пользователями: добавить, изменить, блок/разблок, удалить в файле siteusers.scpt (подключается в f_site... и в adm_site///

20.03.2014  v1.04  Добавлена возможность показа эоектронной почты активного пользователя SiteUser.email в FrontEnd
06.03.2014  v1.03  Добавлена функция "запомнить меня". Срок действия 30 дней (не продлеваемая сессия).
                   Обычная сессия 10 часов (продлеваемая).
25.02.2014  v1.02  Изменена архитектура авторизации/проверки сессии. 
                   Удалены функции SiteUsersCtrl(), SiteUsersClientAdd().
                   Добавлен редирект после авторизации, на запрошенную страницу GET['r']
                   Добавлена функция восстановления пароля по логину SiteUsersPassRecovery(). Макет письма расположен в файле mail_pass_recovery.txt
04.04.2013  v1.01  Полное управление пользователями из backend'a
30.01.2013  v1.00  Начало.

Используемые макеты (неуправляемые):
[T]
[SigninAskRestore]	[Запрос на востановление пароля]
[SigninForm]		[Авторизация пользователя]
[SigninNotify]		[Авторизация/восстановление - уведомление]
[SigninReset]		[Сбросить пароль]
[/T]
* Рассмотреть возможность управления макетами через страницы, используя переменные в макетах {param}
}}
