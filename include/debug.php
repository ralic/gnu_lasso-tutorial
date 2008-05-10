<?php
# Debug utility functions
# Copyright (C) 2008  Sylvain Beucler
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

$sys_debug_on = false;
$GLOBALS['debug_query_count'] = 0;
$GLOBALS['debug_queries'] = array();

// Debug initialization
if ($sys_debug_on == true) {
  // Initialize the variable (avoid later warnings)
  $GLOBALS['debug'] = '';
  $GLOBALS['debug_query_count'] = 0;
  $GLOBALS['debug_queries'] = array();

  // Save the input arrays in case they are emptied
  // (eg. trackers_run/index.php)
  $GLOBALS['INPUT_SAVE'] = array('get' => $_GET,
				 'post' => $_POST,
				 'cookie' => $_COOKIE,
				 'files' => $_FILES);

  function debug_dump() {
    global $INPUT_SAVE;

    print '<pre>';
    print '<hr />';
    print utils_size_readable(memory_get_usage(false)) . '/' . utils_size_readable(memory_get_peak_usage(false))
      . ' now/peak memory usage<br />';
    print utils_size_readable(memory_get_usage(true))  . '/' . utils_size_readable(memory_get_peak_usage(true))
      . ' now/peak real memory usage<br />';
    print '<hr />';

    // SQL queries counter:
    print "{$GLOBALS['debug_query_count']} database queries used:<br/>";
    foreach($GLOBALS['debug_queries'] as $query_data) {
      list($query, $location) = $query_data;
      print "$query [$location]<br />";
    }

    print '<hr />';
    print 'GET:<br />';
    print_r($INPUT_SAVE['get']);

    print '<hr />';
    print 'POST:<br />';
    print_r($INPUT_SAVE['post']);

    print '<hr />';
    print 'COOKIE:<br />';
    print_r($INPUT_SAVE['cookie']);

    print '<hr />';
    print 'FILES:<br />';
    print_r($INPUT_SAVE['files']);

# Useless, only prints 'debug_dump()'...
# Check util_die() maybe.
    #print '<hr />';
    #print 'Stacktrace:<br />';
    #print_r(debug_backtrace());

    print '<hr />';

    // All debug messages:
    if ($GLOBALS['debug'])
      print 'DEBUG information:<br />'.$GLOBALS['debug'];

    print '</pre>';
  }
  register_shutdown_function("debug_dump");

  // alternate PHP error handler that prints a backtrace
  function btErrorHandler($errno, $errstr, $errfile, $errline, $context)
  {
    print '<strong>';
    switch ($errno) {
    case E_ERROR:             print "Error";                  break;
    case E_WARNING:           print "Warning";                break;
    case E_PARSE:             print "Parse Error";            break;
    case E_NOTICE:            print "Notice";                 break;
    case E_CORE_ERROR:        print "Core Error";             break;
    case E_CORE_WARNING:      print "Core Warning";           break;
    case E_COMPILE_ERROR:     print "Compile Error";          break;
    case E_COMPILE_WARNING:   print "Compile Warning";        break;
    case E_USER_ERROR:        print "User Error";             break;
    case E_USER_WARNING:      print "User Warning";           break;
    case E_USER_NOTICE:       print "User Notice";            break;
    case E_STRICT:            print "Strict Notice";          break;
    case E_RECOVERABLE_ERROR: print "Recoverable Error";      break;
    default:                  print "Unknown error ($errno)"; break;
    }
    print '</strong>';
    print ": $errstr in <strong>$errfile</strong> on line <strong>$errline</strong><br />\n";
    print '<pre>';

    // Write my own backtrace function to avoid printing
    // btErrorHandler() in the stack trace
    $bt = debug_backtrace();
    array_shift($bt); // remove this very function

    utils_debug_print_mybacktrace($bt);
    print '</pre>';
      
    /* Don't execute PHP internal error handler */
    return true;
  }
  
  // set to the user defined error handler
  $old_error_handler = set_error_handler("btErrorHandler");
}

/**
 * Return human readable sizes
 * This is public domain, original version from:
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.1.0
 * @link        http://aidanlister.com/repos/v/function.size_readable.php
 * @param       int    $size        Size
 * @param       int    $unit        The maximum unit
 * @param       int    $retstring   The return string format
 * @param       int    $si          Whether to use SI prefixes
 */
function utils_size_readable($size, $unit = null, $retstring = null, $si = false)
{
    // Units
    if ($si === true) {
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
        $mod   = 1000;
    } else {
        $sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $mod   = 1024;
    }
    $ii = count($sizes) - 1;
 
    // Max unit
    $unit = array_search((string) $unit, $sizes);
    if ($unit === null || $unit === false) {
        $unit = $ii;
    }
 
    // Return string
    if ($retstring === null) {
        $retstring = '%01.2f%s';
    }
 
    // Loop
    $i = 0;
    while ($unit != $i && $size >= 1024 && $i < $ii) {
        $size /= $mod;
        $i++;
    }
 
    return sprintf($retstring, $size, $sizes[$i]);
}

/**
 * Modified to print any given backtrace.
 * Original comments:
 * Replace debug_print_backtrace()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/function.debug_print_backtrace
 * @author      Laurent Laville <pear@laurent-laville.org>
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.6 $
 * @since       PHP 5
 * @require     PHP 4.3.0 (debug_backtrace)
 */
function utils_debug_print_mybacktrace($backtrace=null)
{
  // Get backtrace
  if ($backtrace === null)
    {
      $backtrace = debug_backtrace();
      // Unset call to debug_print_backtrace
      array_shift($backtrace);
    }
  
  if (empty($backtrace)) {
    return '';
  }
  
  // Iterate backtrace
  $calls = array();
  foreach ($backtrace as $i => $call) {
    if (!isset($call['file'])) {
      $call['file'] = '(null)';
    }
    if (!isset($call['line'])) {
      $call['line'] = '0';
    }
    $location = $call['file'] . ':' . $call['line'];
    $function = (isset($call['class'])) ?
      $call['class'] . (isset($call['type']) ? $call['type'] : '.') . $call['function'] :
      $call['function'];
    
    $params = '';
    if (isset($call['args'])) {
      $args = array();
            foreach ($call['args'] as $arg) {
	      if (is_array($arg)) {
		$args[] = print_r($arg, true);
	      } elseif (is_object($arg)) {
		$args[] = get_class($arg);
	      } else {
		$args[] = $arg;
	      }
            }
            $params = implode(', ', $args);
    }
    
    $calls[] = sprintf('#%d  %s(%s) called at [%s]',
		       $i,
		       $function,
		       $params,
		       $location);
  }
  
  echo implode("\n", $calls), "\n";
}
