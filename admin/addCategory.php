<?php

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");

if(!havepriv("admin"))
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

if($_POST)
{
    $t->update($_POST);
}
else
{
    $table = "appCategory";
    $query = "INSERT INTO $table VALUES(0, 'NONAME', null, 0)";

    query_appdb("DELETE FROM $table WHERE catName = 'NONAME'");

    if(debugging())
	echo "$query <br /><br />\n";

    $t->create($query, $table, "catId");
}

apidb_footer();

?>
