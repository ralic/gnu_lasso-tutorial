<?php
# Display Service Provider metadata, for use by the Identity Provider
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

require_once(dirname(__FILE__)).'/../config/la-config.inc';

function myself()
{
  $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false;
  $myself = ($is_https ? 'https://' : 'http://')
    . $_SERVER['SERVER_NAME']
    . (((!$is_https && $_SERVER['SERVER_PORT'] == 80)
	|| ($is_https && $_SERVER['SERVER_PORT'] == 443))
       ? '' : $_SERVER['SERVER_PORT'])
    . $_SERVER['REQUEST_URI'];
  return $myself;
}

if (empty($lasso_SPPublicKeyFilename))
{
  if (empty($lasso_SPPrivateKeyFilename))
    die('Error, $lasso_SPPrivateKeyFilename not configured.');
  $pubkey = shell_exec("openssl rsa -in $lasso_SPPrivateKeyFilename -pubout 2>/dev/null");
}
else
{
  $pubkey = file_get_contents($lasso_SPPublicKeyFilename);
}

$dom = new DomDocument();
$dom->load($lasso_SPMetadataFilename);

$entity_descriptor = $dom->firstChild;
$entity_descriptor->setAttribute('entityID', myself());

$sp_sso_descriptor = $dom->getElementsByTagName('SPSSODescriptor')->item(0);

$old_key_descriptor = $sp_sso_descriptor->getElementsByTagName('KeyDescriptor')->item(0);
if (!empty($old_key_descriptor))
     $sp_sso_descriptor->removeChild($old_key_descriptor);

$key_descriptor = $dom->createElement('KeyDescriptor');
$key_descriptor->setAttribute('use', 'signing');
$key_info = $dom->createElement('ds:KeyInfo');
$key_value = $dom->createElement('ds:KeyValue');
$key_text = $dom->createTextNode($pubkey);
$key_value->appendChild($key_text);
$key_info->appendChild($key_value);
$key_descriptor->appendChild($key_info);

$sp_sso_descriptor->insertBefore($key_descriptor, $sp_sso_descriptor->firstChild);
header('Content-type: text/xml');
print $dom->saveXML();
