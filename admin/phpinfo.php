<?php
/********************************************/
/* Code to display the current state of php */
/********************************************/

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");
require(BASE."include/"."category.php");
require(BASE."include/"."maintainer.php");

//check for admin privs
if(!loggedin() || (!havepriv("admin")) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

apidb_header("Appdb PHP Info");
echo html_frame_start("","100%","",0);
echo "<table width='100%' border=1 cellpadding=3 cellspacing=0>\n\n";

/* show all php info */
phpinfo();

echo "</table>\n";

echo html_frame_end("&nbsp;");

echo "</form>";
apidb_footer();

?>

