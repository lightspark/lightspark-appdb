<?php
$aClean = array();
filter_gpc();

/*
 * Make all get/post/cookies variable clean based on their names.
 */
function filter_gpc()
{
    global $aClean;
    $aKeys = array_keys($_REQUEST);
    for($i=0;$i<sizeof($aKeys);$i++)
    {
        switch($aKeys[$i][0])
        {
            case "i": // integer
            case "f": // float
                if(is_numeric($_REQUEST[$aKeys[$i]]))
                    $aClean[$aKeys[$i]] = $_REQUEST[$aKeys[$i]];
                else
                    util_show_error_page_and_exit("Fatal error: ".$aKeys[$i]." should be a numeric value.");
            break;
            case "b": // boolean
                if($_REQUEST[$aKeys[$i]]=="true" || $_REQUEST[$aKeys[$i]]=="false")
                    $aClean[$aKeys[$i]] = $_REQUEST[$aKeys[$i]];
                else
                    util_show_error_page_and_exit("Fatal error: ".$aKeys[$i]." should be a boolean value.");
            break;
            case "s": // string
                switch($aKeys[$i][1])
                {
                     case "h": // HTML string
                         $aClean[$aKeys[$i]] = htmlspecialchars($_REQUEST[$aKeys[$i]]);
                     break;
                     default: // normal string (no HTML)
                          $aClean[$aKeys[$i]] = strip_tags($_REQUEST[$aKeys[$i]]);
                     break;
                }
            break;
            case "a": // array
                 if(!is_array($_REQUEST[$aKeys[$i]]))
                    util_show_error_page_and_exit("Fatal error: ".$aKeys[$i]." should be an array.");
            break;
            default:
                if($aKeys[$i]!="whq_appdb" && // don't filter the appdb session cookie

                   // or any bugzilla cookies
                   $aKeys[$i]!="BUGLIST" &&
                   $aKeys[$i]!="DEFAULTFORMAT" &&
                   $aKeys[$i]!="Bugzilla_login" &&
                   $aKeys[$i]!="LASTORDER" &&
                   $aKeys[$i]!="Bugzilla_logincookie" &&
                   $aKeys[$i]!="DEFAULTFORMAT" &&
                   $aKeys[$i]!="MAX_FILE_SIZE")
                { 
                    util_show_error_page_and_exit("Fatal error: type of variable ".$aKeys[$i]." is not recognized.");
                }
            break;
        }
    }
    
    /* null out all input data so we can be assured that */
    /* no unfiltered values are being used */
    $_REQUEST = array();
    $_POST = array();
    $_GET = array();
    $_COOKIES = array();
}
?>
