<?php
# Input sanitizing
# Copyright (C) 2007  Sylvain Beucler
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

// Return the input as-is, without unwanted magic_quotes_gpc effect
function stripslashesgpc($val)
{
  if (get_magic_quotes_gpc())
    return stripslashes($val);
  return $val;
}

// Check the existence of a series of input parameters, then return an
// array suitable for extract()
// Ex: extract(sane_import('post',
//       array('insert_group_name', 'rand_hash',
//             'form_full_name', 'form_unix_name')));
function sane_import($method, $names) {
  if ($method == 'get')
    $input_array =& $_GET;
  else if ($method == 'post')
    $input_array =& $_POST;
  else if ($method == 'cookie')
    $input_array =& $_COOKIE;
  else
    $input_array =& $_REQUEST;

  $values = array();
  foreach ($names as $input_name) {
    if (isset($input_array[$input_name])) {
      $values[$input_name] = stripslashesgpc($input_array[$input_name]);
    } else {
      $values[$input_name] = null;
    }
  }

  return $values;
}
