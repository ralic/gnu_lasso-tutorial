<?php
# Bootstrap application session, specific for each application
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

require_once(dirname(__FILE__) . '/../../include/app_session.php');

// Code shared in assertion_consumer.php and finish_user_creation.php
function la_bootstrap_app_session($user_id, $liberty_name_id, $liberty_session_dump)
{
  // Is there an opened and valid application session?
  extract(sane_import('cookie', array('app_session_hash')));
  if (!app_session_exists($app_session_hash))
    {
      // Bootstrap a new application session
      $app_session_hash = app_session_new($user_id);

      // Save current Liberty session. We'll need it on logout.
      db_autoexecute('sessions_liberty',
		     array(
			   'session_hash' => $app_session_hash,
			   'liberty_session_dump' => $liberty_session_dump,
			   'liberty_name_id' => $liberty_name_id,
			   ),
		     DB_AUTOQUERY_INSERT);
    }
  else
    {
      // Update session
      db_autoexecute('sessions_liberty',
		     array(
			   'session_hash' => $app_session_hash,
			   'liberty_session_dump' => $liberty_session_dump,
			   ),
		     DB_AUTOQUERY_UPDATE,
		     'liberty_name_id=?', array($liberty_name_id));
    }

  // Clean-up expired sessions (in case the DB doesn't support ON DELETE CASCADE)
  db_execute('DELETE sessions_liberty FROM sessions_liberty
    LEFT JOIN sessions ON sessions.hash = sessions_liberty.session_hash
    WHERE sessions.hash IS NULL');
}
