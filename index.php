<?php
require_once('include/db.php');

require('include/init.php');

if (!isset($app_session_hash)) {
?>
<a href="lasso/login.php">Login</a>
<?php
} else {
?>
<a href="lasso/logout.php">Logout</a>

<?php
}
echo "<hr />";

$data = mysql_fetch_array(db_execute('SELECT users.id,users.login
    FROM sessions JOIN users ON sessions.user_id = users.id
    WHERE sessions.hash=?',
  array($app_session_hash)), MYSQL_ASSOC);
if ($data)
{
  echo "<h1>The application itself</h1>";

  $user_id = $data['id'];
  $login = $data['login'];
  echo "Hello, you're user <em>$login</em> (ID#$user_id).<br />";
  echo "Your current application session is <code>$app_session_hash<code>.<br />";

  echo "<h1>Lasso layer</h1>";
#la_nameid,la_iddump,la_sessiondump,
#$lassoIdentityDump
  echo "<h2>Current Lasso session</h2>";
  echo "Your session matches this lasso identity and session:";
  
  $result = db_execute('SELECT users_to_liberty.liberty_name_id, users_to_liberty.liberty_id_dump,
      sessions_liberty.liberty_session_dump
    FROM users_to_liberty JOIN sessions_liberty
      ON users_to_liberty.liberty_name_id = sessions_liberty.liberty_name_id
    WHERE sessions_liberty.session_hash = ?', array($app_session_hash));
  while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
    echo "<pre>";
    echo "nameid = " . htmlspecialchars($row['liberty_name_id']) . "<br />";
    echo "<hr />";
    echo "iddump = " . htmlspecialchars($row['liberty_id_dump']) . "<br />";
    echo "<hr />";
    echo "sessiondump = " . htmlspecialchars($row['liberty_session_dump']) . "<br />";
    echo "</pre>";
  }
  
  echo "<h2>All Lasso identities</h2>";
  $result = db_execute('SELECT users_to_liberty.liberty_name_id, users_to_liberty.liberty_id_dump
    FROM users_to_liberty WHERE user_id=?',
		       array($user_id));
  while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
    echo "<pre>";
    echo "nameid = " . htmlspecialchars($row['liberty_name_id']) . "<br />";
    echo "<hr />";
    echo "iddump = " . htmlspecialchars($row['liberty_id_dump']) . "<br />";
    echo "</pre>";
  }
  // TODO: associated sessions

}
else
{
  echo "You are not logged in.";
}
