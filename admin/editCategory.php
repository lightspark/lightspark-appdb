<?php

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");

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

apidb_header("Edit Category");

$t = new TableVE("edit");


if($_POST)
{
    $t->update($_POST);
}
else
{
    $table = "appCategory";
    $query = "SELECT * FROM $table WHERE catId = $catId";

    if(debugging())
	echo "$query <br><br>\n";

    $t->edit($query);
}

apidb_footer();

?>
