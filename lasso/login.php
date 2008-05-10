<?php
# Link for login from application via Lasso
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

// When libertization is enabled, login should redirect to or include
// this file
require(dirname(__FILE__) . '/../include/init.php');
require_once(dirname(__FILE__) . '/include/la-init.inc');

// Select metadata depending on protocol
$lassoLogin = new LassoLogin($lasso_server);
if ($lasso_protocol == LASSO_PROTOCOL_SAML_2_0)
     $lasso_idpProviderId = $lasso_idpProviderId_SAML20;
else
     $lasso_idpProviderId = $lasso_idpProviderId_IDFF15;
try {
  $lassoLogin->initAuthnRequest($lasso_idpProviderId, LASSO_HTTP_METHOD_REDIRECT);
} catch (LassoProviderNotFoundError $e) {
  die("Lasso error: the Identity Provider (IdP) rejected IdP identifier '$lasso_idpProviderId': "
      . $e->getMessage());
} catch (Exception $e) {
  die('Lasso error: ' . $e->getMessage());
}

// Build request to IdP
$lassoRequest = $lassoLogin->request;
if ($lasso_protocol == LASSO_PROTOCOL_SAML_2_0)
{
  $lassoRequest->nameIDPolicy->format = LASSO_SAML2_NAME_IDENTIFIER_FORMAT_PERSISTENT;
  $lassoRequest->nameIDPolicy->allowCreate = TRUE;
}
else
{
  $lassoRequest->NameIDPolicy = LASSO_LIB_NAMEID_POLICY_TYPE_FEDERATED;
}
$lassoRequest->consent = LASSO_LIB_CONSENT_OBTAINED;
$lassoRequest->ForceAuthn = 0;
$lassoRequest->IsPassive = 0;
// unused here:
$lassoRequest->relayState = "relay state";

// Send request via HTTP redirection
try {
  $lassoLogin->buildAuthnRequestMsg();
} catch (LassoProfileBuildingQueryFailedError $e) {
  die("Lasso error: cannot create the authentication request: "
      . $e->getMessage());
} catch (Exception $e) {
  die('Lasso error: ' . $e->getMessage());
}
$redirect_url = $lassoLogin->msgUrl;
header("Location: $redirect_url");
exit;
