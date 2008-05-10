<?php
# Lasso initialization
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

// Load configuration file
if (!file_exists(dirname(__FILE__).'/../config/la-config.inc')) {
  echo "<code>la-config.php</code> not found!
Please create <code>la-config.php</code> using <code>la-config.inc.dist</code>
as model.";
  exit(1);
} else {
  require_once(dirname(__FILE__).'/../config/la-config.inc');
}


// Lasso
if (!extension_loaded('lasso')) {
  $prefix = (PHP_SHLIB_SUFFIX == 'dll') ? 'php_' : '';
  dl($prefix . 'lasso.' . PHP_SHLIB_SUFFIX);
}

if (!extension_loaded('lasso')) {
  echo "Please install the <strong>Lasso</strong> extension for PHP (check the
        <a href='http://lasso.entrouvert.org/download/'>Lasso download
        page</a> for more information).<br />";
  echo "Once the extension is installed, please restart Apache.";
  exit;
}

require_once(ini_get("extension_dir") . "/lasso.php");

if (!file_exists($lasso_SPMetadataFilename))
     die("Cannot open Service Provider metadata '$lasso_SPMetadataFilename'.");
if (!file_exists($lasso_SPPrivateKeyFilename))
     die("Cannot open Service Provider private key '$lasso_SPPrivateKeyFilename'.");
$lasso_server = new LassoServer($lasso_SPMetadataFilename, $lasso_SPPrivateKeyFilename, NULL, NULL);
try {
  $lasso_server->addProvider(LASSO_PROVIDER_ROLE_IDP,
		       $lasso_IdPMetadataFilename, $lasso_idpPublicKeyFilename, NULL);
} catch (Exception $e) {
      die('Error: ' . $e->getMessage() . "<br />"
	  . "Check that <code>$lasso_IdPMetadataFilename</code> and"
	  . " <code>$lasso_idpPublicKeyFilename</code> exist and can be read by the web server.");
}

libxml_use_internal_errors(false); // don't print XML warnings on screen
#print "<pre>";
#$dump = $lasso_server->dump();
#print(htmlspecialchars($dump));
#print "</pre>";
