<?php
# Single LogOut from the Identity Provider (method: HTTP redirect)
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

require_once('include/la-soap.inc'); // soap_call
require('include/la-init.inc');

// DRAFT

// Grab information from this IdP request
$lassoLogout = new LassoLogout($server);
#if ($lasso_protocol == LASSO_PROTOCOL_SAML_2_0)
#{
  $lassoLogout->processRequestMsg($_SERVER['QUERY_STRING']);
#}

// Define identity and session to logout
$result = db_execute('SELECT liberty_id_dump FROM users_to_liberty WHERE liberty_name_id=?',
		     array($_SESSION['liberty_name_id']));

$liberty_data = mysql_fetch_array($result);
$lassoLogout->setIdentityFromDump($liberty_data['liberty_id_dump']);

$lassoLogout->setSessionFromDump($_SESSION['liberty_session_dump']);

$lassoLogout->validateRequest();
$lassoLogout->buildResponseMsg($_SERVER['QUERY_STRING']);
$redirect_url = $lassoLogout->msgUrl;

// Delete application and liberty sessions at once
session_destroy();

header("Location: $redirect_url");
