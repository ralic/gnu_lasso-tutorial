<?php
# Link for logout from application via Lasso
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

// SingleLogout

// This URL is defined in the SingleLogoutServiceURL element of the
// Service Provider metadata file?

// The IdP redirects here when the user performs a single logout.

require_once('../include/db.php');
require_once('../include/app_session.php');
require('../include/init.php');

require_once('include/la-soap.inc');
require('include/la-init.inc');

$lassoLogout = new LassoLogout($lasso_server);

$result = db_execute('SELECT liberty_session_dump, users_to_liberty.liberty_name_id, liberty_id_dump
  FROM users_to_liberty JOIN sessions_liberty
    ON users_to_liberty.liberty_name_id = sessions_liberty.liberty_name_id
  WHERE sessions_liberty.session_hash = ?', array($app_session_hash));
if (db_numrows($result) >= 1)
{
  $data = mysql_fetch_array($result, MYSQL_ASSOC);
    $lassoLogout->setSessionFromDump($data['liberty_session_dump']);
  $lassoLogout->setIdentityFromDump($data['liberty_id_dump']);

  if ($lasso_protocol == LASSO_PROTOCOL_SAML_2_0)
    $lasso_idpProviderId = $lasso_idpProviderId_SAML20;
  else
    $lasso_idpProviderId = $lasso_idpProviderId_IDFF12;
  $lassoLogout->initRequest($lasso_idpProviderId, LASSO_HTTP_METHOD_SOAP);
  $lassoLogout->buildRequestMsg();

  // The service provider must then make a SOAP request to the identity
  // provider; $msgUrl and $msgBody.
  $answer = soap_call($lassoLogout->msgUrl, $lassoLogout->msgBody);

  // You should then pass the answer to Lasso:
  try
    {
      $lassoLogout->processResponseMsg($answer);
    }
  catch (Exception $e)
    {
      die('Lasso error: ' . $e->getMessage() . " (is your public key correctly configured?).<br />"
	  . "Message received from the IdP was: "
	  . "<pre>" . htmlentities($answer) . "</pre>");
    }
}


// Delete application session
app_session_delete($app_session_hash);

// Delete Liberty session
db_execute('DELETE FROM sessions_liberty WHERE session_hash = ?',
	   array($app_session_hash));

// "And save back session and user dump; the process is similar as the
// one at the end of the single sign on profile."

// I don't understand why I should save back a session I just
// removed. I can update the identity dump though.

echo "<a href='..'>Index</a> - <a href='login.php'>Login</a>";
echo "<hr />";
echo "You are logged out!";

print '<pre>';
print(htmlspecialchars($lassoLogout->dump()));
print '</pre>';
