<?
# Application initialization
# Copyright (C) 2005,2006,2007 Cliss XXI (GCourrier)
# Copyright (C) 2007 Sylvain Beucler (Savane)
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

require_once(dirname(__FILE__) . '/db.php');
require_once(dirname(__FILE__) . '/sane.php');
require_once(dirname(__FILE__) . '/debug.php');

// Everything in UTF-8
setlocale(LC_ALL, 'fr_FR.UTF-8');
header('Content-Type: text/html;charset=UTF-8');


// Detect where we are, unless it's explicitely specified in the
// configuration file:
$sys_www_topdir = getcwd();
$sys_url_topdir = dirname($_SERVER['SCRIPT_NAME']);
while ($sys_www_topdir != '/' && !file_exists("$sys_www_topdir/.topdir"))
{
  // cd ..
  $sys_www_topdir = dirname($sys_www_topdir);
  $sys_url_topdir = dirname($sys_url_topdir);
}
if (!file_exists("$sys_www_topdir/.topdir"))
     die("Could not find the top directory (missing .topdir file)");


// Load configuration file
if (!file_exists(dirname(__FILE__).'/config.php')) {
  echo "<code>config.php</code> not found!
Please create <code>config.php</code> using <code>config.php.dist</code>
as model.";
  exit(1);
} else {
  require_once(dirname(__FILE__).'/config.php');
}


// MySQL
db_connect();


// Session
extract(sane_import('cookie', array('app_session_hash')));
// Get user information
$result = db_execute('SELECT users.id,users.login
    FROM sessions JOIN users ON sessions.user_id = users.id
    WHERE sessions.hash=?',
		     array($app_session_hash));
$user_data = mysql_fetch_array($result);

if (empty($user_data))
{
  $user_id = null;
}
else
{
  $user_id = $user_data['id'];
}
