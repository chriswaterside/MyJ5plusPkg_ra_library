<?php

/*
 * to return indicator if symbol exists
 */

$gets = array();

foreach ($_GET as $key => $value) {
    $gets[$key] = htmlspecialchars($value);
}
$filename = $gets["file"];

if (file_exists($filename)) {
    echo "true";
} else {
    echo "false";
}