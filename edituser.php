<?

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");

if(!havepriv("admin"))
{
    errorpage();
    exit;
}


$user_fields = array("stamp", "userid", "username", "password", "realname", "email", "created", "status");

function make_fields($fields, $prefix)
{
    $arr = array();
    while(list($idx, $field) = each($fields))
	$arr[] = "$prefix.$field";
    return $arr;
}



apidb_header("Edit User");

$t = new TableVE("edit");

if($HTTP_POST_VARS)
{
    $t->update($HTTP_POST_VARS);
}
else
{
    $qc = new qclass();
    $qc->add_fields(make_fields($user_fields, "user_list"));
    if($username)
	$qc->add_where("username = '$username'");
    else
	$qc->add_where("userid = $userid");
    $qc->resolve();

    $query = $qc->get_query();

    if(debugging())
	echo "$query <br><br>\n";

    $t->edit($query);
}

apidb_footer();

?>
