<?php

/* Main test function.  To add new tests include_once() */
/* your test file here */

/* TODO: test the rest of the classes we have */

error_reporting(E_ALL);

// disable emailing
if(!defined("DISABLE_EMAIL"))
   define("DISABLE_EMAIL", true);

include_once("test_user.php");
echo "\n";
include_once("test_query.php");
echo "\n";
include_once("test_image.php");
echo "\n";
include_once("test_application.php");
echo "\n";
include_once("test_error_log.php");
echo "\n";
include_once("test_filter.php");
echo "\n";
include_once("test_url.php");
?>
