<?php


$fields = "";
$join = "";
$orderby = "";
$searchfor = "";
$searchwhat = "";



function create_appversionsearch_url($vName)
{
        global $fields, $orderby, $searchwhat, $join;

	$orderby = "appId";
	$searchwhat = "appVersion.appId";
	$fields[] = "appVersion.appId";
	$fields[] = "appVersion.versionId";
	$fields[] = "appVersion.versionName";


	$url = "stdquery.php";
	$url .= "?orderby=$orderby";
	$url .= "&searchfor=$vName";
	$url .= "&searchwhat=$searchwhat";

	foreach($fields as $aField)
	{
		$url .= "&fields[]=$aField";
	}

	$url .= "&join=$join";
	$url .= "&linesPerPage=$linesPerPage";

	return $url;
}

function output_appversion_forminputs()
{
        global $fields, $orderby, $searchwhat, $join;

	$orderby = "appId";
	$searchwhat = "appVersion.versionId";
	$fields[] = "appVersion.appId";
	$fields[] = "appVersion.versionId";
	$fields[] = "appVersion.versionName";


	echo "<input TYPE=\"HIDDEN\" NAME=\"orderby\" VALUE=\"$orderby\">
	    <input TYPE=\"HIDDEN\" NAME=\"searchwhat\" VALUE=\"$searchwhat\">";

	foreach($fields as $aField)
	{
	    echo "<input TYPE=\"HIDDEN\" NAME=\"fields[]\" VALUE=\"$aField\">";
	}

	echo "<input TYPE=\"HIDDEN\" NAME=\"join\" VALUE=\"$join\">";
}

?>
