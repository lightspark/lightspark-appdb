<?php

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");

if(!loggedin() || !havepriv("admin"))
{
    errorpage();
    exit;
}
else
{
    global $admin_mode;
    $admin_mode = 1;
}

apidb_header("Add Application Category");

$t = new TableVE("create");

if($HTTP_POST_VARS)
{
    $t->update($HTTP_POST_VARS);
}
else
{
    $table = "appCategory";
    $query = "INSERT INTO $table VALUES(0, 'NONAME', null, 0)";

    mysql_query("DELETE FROM $table WHERE catName = 'NONAME'");

    if(debugging())
	echo "$query <br><br>\n";

    $t->create($query, $table, "catId");
}

apidb_footer();

?>
