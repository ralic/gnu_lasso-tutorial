<?php
# Application session
# Copyright (C) 2008  Cliss XXI
# 
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

require_once(dirname(__FILE__).'/config.php'); // $sys_url_topdir

function app_session_gen_hash()
{
  return md5(microtime() . rand() . $_SERVER['REMOTE_ADDR']);
}

function app_session_exists($hash)
{
  $result = db_execute('SELECT liberty_session_dump, liberty_name_id FROM sessions_liberty
                          WHERE session_hash=?',
		       array($hash));
  return $result and mysql_num_rows($result) > 0;
}

function app_session_new($user_id)
{
  $hash = app_session_gen_hash();
  $expiration = time() + 60*60*24*7; // for a week
  db_autoexecute('sessions',
    array(
      'hash' => $hash,
      'user_id' => $user_id,
      'expires' => $expiration
    ),
  DB_AUTOQUERY_INSERT);

  // Clean-up expired sessions (in case the DB doesn't support ON DELETE CASCADE)
  db_execute('DELETE FROM sessions WHERE NOW() > FROM_UNIXTIME(expires)');
  
  // Set session cookie
  setcookie('app_session_hash', $hash, $expiration, $GLOBALS['sys_url_topdir']);
  return $hash;
}

function app_session_delete($app_session_hash)
{
  db_execute('DELETE FROM sessions WHERE hash=?',
	     array($app_session_hash));
  setcookie('app_session_hash', null, null, $GLOBALS['sys_url_topdir']);
}
