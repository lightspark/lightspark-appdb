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

apidb_header("Add Vendor");

$t = new TableVE("create");

if($_POST)
{
    $t->update($_POST);
}
else
{
    $table = "vendor";
    $query = "INSERT INTO $table VALUES(0, 'NONAME', null)";

    mysql_query("DELETE FROM $table WHERE vendorName = 'NONAME'");

    if(debugging())
	echo "$query <br><br>\n";

    $t->create($query, $table, "vendorId");
}

apidb_footer();

?>
