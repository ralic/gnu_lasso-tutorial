<?php
# Bootstrap application session, specific for each application
# Copyright (C) 2006  Entr'ouvert
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

// Send given soap message over http
function soap_call($url, $msg)
{
    // Log request
    $fd = fopen(dirname(__FILE__).'/../tmp/soap.log', 'a');
    fwrite($fd, "REQUEST url = [$url]; msg = [$msg]\n");
    fclose($fd);

    if (strstr($url, 'http://') != $url)
	return "";
    $short_url = substr($url, 7);
    $url_tokens = explode("/", $short_url);
    $host = $url_tokens[0];
    unset($url_tokens[0]);
    $query = implode("/", $url_tokens);
    $query = "/" . $query;

    $response = PostToHost($host, $query, $msg);

    // Log response
    $fd = fopen(dirname(__FILE__).'/../tmp/soap.log', 'a');
    fwrite($fd, "RESPONSE url = [$url]; msg = [$msg]\n");
    fclose($fd);

    return $response;
}

function PostToHost($host, $path, $data_to_send, $ssl = false) {
    if ($ssl) {
	$fp = fsockopen('tls://' . $host, 443);
    } else {
	$fp = fsockopen($host, 80);
    }
    fputs($fp, "POST $path HTTP/1.1\n");
    fputs($fp, "Host: $host\n");
    fputs($fp, "Content-type: text/xml\n");
    fputs($fp, "Content-length: ".strlen($data_to_send)."\n");
    fputs($fp, "Connection: close\n\n");
    fputs($fp, "$data_to_send\n");
    $res = '';
    while(!feof($fp)) {
	$res .= fgets($fp, 128);
    }
    fclose($fp);

    $res = substr(strstr($res, "\r\n\r\n"), 4);

    return $res;
}
