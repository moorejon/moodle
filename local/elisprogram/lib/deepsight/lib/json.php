<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2013 Remote Learner.net Inc http://www.remote-learner.net
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

/**
 * Encodes an array as faux-JSON, while preserving strings that look like functions, as functions.
 *
 * JSON normally does not allow for functions, but since we sometimes have to provide functions in options objects, this function
 * allows us to still format a PHP array to a javascript object literal and preserve functions.
 *
 * Inspired by: http://solutoire.com/2008/06/12/sending-javascript-functions-over-json/
 *
 * @param array $obj The array to encode.
 * @return string The resulting faux-JSON.
 */
function json_encode_with_functions(array $obj) {
    list ($obj, $functions)  = json_encode_with_functions_functionextractor($obj);
    $json = json_encode($obj);
    $json = str_replace(array_keys($functions), $functions, $json);
    return $json;
}

/**
 * Recursive function to be used with json_encode_with_functions.
 *
 * @param array $obj The array to encode.
 * @return array An array containing the original array with functions removed as index 0, and the associated functions as index 1
 */
function json_encode_with_functions_functionextractor(array $obj) {
    $functions = array();
    foreach ($obj as $k => $v) {
        if (is_string($v) && strpos($v, 'function(') === 0) {
            $placeholder = '@@'.uniqid().'@@';
            $functions['"'.$placeholder.'"'] = $v;
            $obj[$k] = $placeholder;
        } else if (is_array($v)) {
            list ($vobj, $vfuncs)  = json_encode_with_functions_functionextractor($v);
            $obj[$k] = $vobj;
            $functions = array_merge($functions, $vfuncs);
        }
    }
    return array($obj, $functions);
}

/**
 * Encodes an array as json, and prefixes "throw 1;" to protect against JSONP XSSI hijackings.
 *
 * @param array $data The array to be encoded.
 * @return string The encoded JSON, prepended with "throw 1;" to protect against XSSI attacks.
 */
function safe_json_encode(array $data) {
    return 'throw 1;'.json_encode($data);
}

/**
 * Decodes XSSI-safe JSON.
 *
 * @param string $data The encoded string to be decoded.
 * @return array The decoded array.
 */
function safe_json_decode($data) {
    $data = substr($data, 8);
    return json_decode($data, true);
}