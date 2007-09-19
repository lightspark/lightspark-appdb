<?php
/****************************************************/
/* code to delete versions, families and categories */
/****************************************************/

/*
 * application environment
 */
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/category.php");
require_once(BASE."include/application.php");
require_once(BASE."include/monitor.php");
require_once(BASE."include/testData.php");

if($aClean['sConfirmed'] != "yes")
{
    // ask for confirmation
    // could do some Real Damage if someone accidently hits the delete button on the main category :)
    //
    // perhaps we can do this with some javascript, popup

    util_show_error_page_and_exit("Not confirmed");
}

if(isset($aClean['sWhat']) && $aClean['sWhat'] ==  "category")
{
        // delete category and the apps in it
        $oCategory = new Category($aClean['iCatId']);
        if(!$oCategory->delete())
            util_show_error_page_and_exit();
        else
            util_redirect_and_exit(BASE."appbrowse.php");
}

?>
