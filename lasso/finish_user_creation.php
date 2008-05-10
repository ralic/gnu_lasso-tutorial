<?php
# Ask for further user information after first Lasso login
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

require_once('../include/sane.php');
require_once('../include/db.php');
require('../include/init.php');

require_once('include/la-session.inc'); // la_bootstrap_app_session



extract(sane_import('post', array('login')));
extract(sane_import('cookie', array('session_hash')));

session_start();
$liberty_name_id = $_SESSION['liberty_name_id'];

if (!isset($liberty_name_id))
{
  die("You are not authenticated");
}

// Note: in this case we ask the user to enter a login. But consider
// this: we actually do not really need a login name. Login name is
// mostly useful for people to state who they are at when typing
// login/password. Since that's already done at the IdP, we can do
// everything with an user_id only. wcs generates a login like
// "anonymous-$nameid". It's only necessary to ask for a login name if
// that's a crucial part of the SP (for example, if it's used to
// create an homonymous Unix user).

if (!isset($login))
{
  echo "<h1>Additional information needed</h1>";
  echo "This is the first time you log in this application. Please chose a user name.";
  echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
  echo "<input type='text' name='login'>";
  echo "<input type='submit'>";
  echo "</form>";
}
else
{
  if (empty($login) or !ctype_alnum($login))
    {
      die("Invalid username: please choose an alphanumeric username");
    }
  else
    {
      $result = db_execute("
        SELECT user_id FROM users_to_liberty
          WHERE liberty_name_id=?",
	array($liberty_name_id));
      if (mysql_num_rows($result) == 0)
	die("Cannot find the user matching your session. Maybe it is too old and was deleted.");
      
      $data = mysql_fetch_array($result, MYSQL_ASSOC);
      $user_id = $data['user_id'];
      // TODO: check if login is unique
      db_execute("UPDATE users SET login=? WHERE id=?",
		 array($login, $user_id));
      
      // we don't need to temporary Lasso-specific session anymore
      session_destroy();
      setcookie(session_name(), null, null, '/');

      la_bootstrap_app_session($user_id, $liberty_name_id, $_SESSION['liberty_session_dump']);

      // "A success web page can be displayed."
      header('Location: ../..');
    }
}
