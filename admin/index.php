<?php
/***************************/
/* Admin Script for API Db */
/***************************/

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");

if(!havepriv("admin"))
{
    errorpage();
    exit;
}

//desc
if($table_cmd)
{
    apidb_header("Table Operation");
    $t = new TableVE("view");
    switch($table_cmd)
	{
	case "check":
	    $t->view("CHECK TABLE $table_id");
	    break;
	case "describe":
	    $t->view("DESCRIBE $table_id");
	    break;
	case "optimize":
	    $t->view("OPTIMIZE TABLE $table_id");
	    break;
	}
    apidb_footer();
    exit;
}

// output of admin page begins here
apidb_header("Admin");

// Draw User List
include(BASE."include/"."query_users.php");


apidb_footer();

?>
