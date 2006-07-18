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
                elseif(empty($_REQUEST[$aKeys[$i]]))
                    $aClean[$aKeys[$i]] = 0;
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
                         $aClean[$aKeys[$i]] = trim(htmlspecialchars($_REQUEST[$aKeys[$i]]));
                         // if there is no content and no image, make the variable empty
                         if(strip_tags($aClean[$aKeys[$i]],'<img>')=="")
                             $aClean[$aKeys[$i]] = "";
                     break;
                     default: // normal string (no HTML)
                          $aClean[$aKeys[$i]] = trim(strip_tags($_REQUEST[$aKeys[$i]]));
                     break;
                }
            break;
            case "a": // array
                 if(!is_array($_REQUEST[$aKeys[$i]]))
                    util_show_error_page_and_exit("Fatal error: ".$aKeys[$i]." should be an array.");
            break;
            default:
                // don't filter the AppDB session cookie and MAX_FILE_SIZE
                // and the DialogX values that xinha uses
                if($aKeys[$i]!="whq_appdb" && $aKeys[$i]!="MAX_FILE_SIZE" && $aKeys[$i]!="PHPSESSID"
                   && strpos($aKeys[$i], "Dialog") == 0)
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
    if(APPDB_DONT_CLEAR_COOKIES_VAR != "1")
        $_COOKIES = array();
}
?>
