<?php
# Bootstrap application login after redirection from IdP
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

// This URL is defined in the AssertionConsumerServiceURL element of
// the Service Provider metadata file

// The IdP redirects here after the user is logged in at the IdP.

// We need to 1) create a new user if it's the first login and link it
// with the liberty name identifier, 2) if the user is already logged
// in, and asked to federate with another identity, attach a new
// liberty name identifier to the current user, 3) (optional) check if
// the application _requires_ additional information about the user
// (such as a user name), 4) create an application session and link it
// to the liberty session

require_once('../../include/app_session.php');
require('../../include/init.php');

require_once('../include/la-session.inc'); // la_bootstrap_app_session
require_once('../include/la-soap.inc');
require('../include/la-init.inc');

// Grab information from this IdP request
$lassoLogin = new LassoLogin($lasso_server);
if ($lasso_protocol == LASSO_PROTOCOL_SAML_2_0)
{
  $lassoLogin->initRequest($_SERVER['QUERY_STRING'], LASSO_HTTP_METHOD_ARTIFACT_GET);
}
else
{
  $lassoLogin->initRequest($_SERVER['QUERY_STRING'], LASSO_HTTP_METHOD_REDIRECT);
}
@$lassoLogin->buildRequestMsg();
$answer = soap_call($lassoLogin->msgUrl, $lassoLogin->msgBody);
try {
  $lassoLogin->processResponseMsg($answer);
} catch (Exception $e) {
  die('Lasso error: Failed to process login request ("' . $e->getMessage() . '")');
}

$liberty_name_id = $lassoLogin->nameIdentifier->content;
$user_id = null;
$liberty_id_dump = null;
$liberty_session_dump = null;

// ** We now know $liberty_name_id **


// 1) Does the user exist? (in 'users' AND 'users_to_liberty')
$result = db_execute('SELECT user_id, liberty_id_dump
  FROM users JOIN users_to_liberty ON users.id=users_to_liberty.user_id
  WHERE liberty_name_id=?',
		     array($liberty_name_id));
if (mysql_num_rows($result) == 0)
{
  // User does not already exist

  // "If the identity has not recognised by the service provider an
  // account will probably have to be created on the service provider;
  // this is a good opportunity to ask the user for more information."
  $lassoLogin->acceptSso(); // fill in $lassoLogin->identity

  // Create user, except for the information we don't have yet
  db_autoexecute('users', array('login' => ''), DB_AUTOQUERY_INSERT);
  $user_id = mysql_insert_id();
  $lassoIdentity = $lassoLogin->identity;
  $liberty_id_dump = $lassoIdentity->dump();
  db_autoexecute('users_to_liberty', array(
      'user_id' => $user_id,
      'liberty_name_id' => $liberty_name_id,
      'liberty_id_dump' => $liberty_id_dump),
    DB_AUTOQUERY_INSERT);
  $liberty_session_dump = $lassoLogin->session->dump();
}
else
{
  // User already exists
  $user_data = mysql_fetch_array($result);
  $user_id = $user_data['user_id'];

  // 2) Check if we already have a valid session here
  // "It is now time to get them out of the database and apply them to the login object."
  $result = false;
  extract(sane_import('cookie', array('app_session_hash')));
  if (app_session_exists($app_session_hash))
    {
      $result = db_execute('SELECT liberty_session_dump, liberty_name_id FROM sessions_liberty
                          WHERE session_hash=?',
			   array($app_session_hash));
    }
  if ($result and mysql_num_rows($result) > 0)
    {
      // The following is recommended; for example it will help detect
      // assertion replays
      // http://lists.labs.libre-entreprise.org/pipermail/lasso-devel/2008-January/001974.html
      $user_data = mysql_fetch_array($result, MYSQL_ASSOC);
      $liberty_session_dump = $user_data['liberty_session_dump'];
      $lassoLogin->setSessionFromDump($liberty_session_dump);
      $result = db_execute('SELECT liberty_id_dump FROM users_to_liberty WHERE liberty_name_id=?',
			   array($liberty_name_id));
      $user_data = mysql_fetch_array($result, MYSQL_ASSOC);
      $liberty_id_dump = $user_data['liberty_id_dump'];
      $lassoLogin->setIdentityFromDump($liberty_id_dump);

      // If we support multiple federated identities for a single login,
      // we could here, add another name_id if it's different from the
      // current one

      $lassoLogin->acceptSso();
    }
  else
    {
      $lassoLogin->acceptSso();
      $liberty_id_dump = $lassoLogin->identity->dump();
      $liberty_session_dump = $lassoLogin->session->dump();
    }
}

// ** We now know $user_id **
// ** We now know have $liberty_id_dump and $liberty_session_dump (v1) **


// "After lassoLogin->acceptSso() the session and the identity are
// updated (or created) and should then be saved."

// This most often happens when a federation is created for the
// identity (or removed, or modified through the Name Id Management
// profile).

if ($lassoLogin->isIdentityDirty) {
  $old_name_id = $liberty_name_id;
  $liberty_name_id = $lassoLogin->identity->content;
  $liberty_id_dump = $lassoLogin->identity->dump();
  if (!empty($old_name_id))
    db_execute("UPDATE users_to_liberty SET liberty_id_dump=?, liberty_name_id=? WHERE liberty_name_id=?",
	       array($liberty_id_dump, $liberty_name_id, $old_name_id));
}

if ($lassoLogin->isSessionDirty) {
  $old_session_dump = $liberty_session_dump;
  $liberty_session_dump = $lassoLogin->session->dump();
  if (!empty($old_session_id))
    db_execute("UPDATE sessions_liberty SET liberty_session_dump=? WHERE liberty_session_dump=?",
	       array($liberty_session_dump, $old_session_dump));

}
// ** We now know have $liberty_id_dump and $liberty_session_dump (v2) **


// Sample assertion parsing (doesn't do anything)
$assertion = $lassoLogin->response->assertion[0];
if (isset($assertion->attributeStatement[0]))
{
  foreach ($assertion->attributeStatement[0]->attribute as $attribute) {
    if ($attribute->name == LASSO_SAML2_ATTRIBUTE_NAME_EPR) {
      continue;
    }
    if ($attribute->name == 'username')
      $user_login = $attribute->attributeValue[0]->any[0]->content;
    if ($attribute->name == 'cn')
      $user_fullname = $attribute->attributeValue[0]->any[0]->content;
    if ($attribute->name == 'local-admin')
      if ($attribute->attributeValue[0]->any[0]->content == "true")
	$user_authlevel = 5; // admin
    if ($attribute->name == 'super-admin')
      if ($attribute->attributeValue[0]->any[0]->content == "true")
	$user_authlevel = 5; // admin
  }
#print '<pre>';
#print htmlspecialchars($assertion->dump());
#print '</pre>';
}

// We're done querying the IdP


$user_data = mysql_fetch_array(db_execute("SELECT login FROM users WHERE id=?",
					  array($user_id)));
if (empty($user_data['login']))
{
  // If login is required for the application, and cannot be
  // automatically generated (such as anonymous-$name_id), then ask
  // user still to provide additional information. A proper login may
  // be crucial if it's reused in other contexts such as e-mail
  // address or shell account creation. In other cases, it's best not
  // to require the user to enter personal data about him/herself.
  $login_is_mandatory = true;

  if ($login_is_mandatory)
    {
      // Setup a quick Lasso-specific session. We do not use the
      // application session system, because we consider the user account
      // is not complete yet and should not be allowed to log to the
      // application.
      session_start();
      $_SESSION['liberty_name_id'] = $liberty_name_id;
      $_SESSION['liberty_session_dump'] = $liberty_session_dump;

      // Redirect->finish_user_creation
      header('Location: ../finish_user_creation.php');
      exit;
    }
  else
    {
      db_execute("UPDATE users SET login=? WHERE id=?",
		 array("anon-$liberty_name_id", $user_id));
    }
}


la_bootstrap_app_session($user_id, $liberty_name_id, $liberty_session_dump);

// "A success web page can be displayed."
header('Location: ../..');
exit;