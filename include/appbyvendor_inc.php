<?php


function create_appbyvendorsearch_url($vName)
{
        global $fields, $orderby, $join;

	$orderby = "appId";
	$fields[] = "vendor.vendorId";
	$fields[] = "appFamily.appId";
	$fields[] = "appFamily.appName";
	$fields[] = "appFamily.webPage";
	$join = "appFamily.vendorId=vendor.vendorId";

	$searchwhat = "vendor.vendorId";

	$url = "stdquery.php";
	$url .= "?orderby=$orderby";
	$url .= "&searchfor=$vName";
	$url .= "&searchwhat=$searchwhat";
	$url .= "&join=$join";

	foreach($fields as $aField)
	{
		$url .= "&fields[]=$aField";
	}

	$url .= "&linesPerPage=$linesPerPage";

	return $url;
}

function output_appbyvendor_forminputs()
{
        global $fields, $orderby, $join;

	$orderby = "appId";
	$fields[] = "vendor.vendorId";
	$fields[] = "appFamily.appId";
	$fields[] = "appFamily.appName";
	$fields[] = "appFamily.webPage";
	$join = "appFamily.vendorId=vendor.vendorId";


	$searchwhat = "vendor.vendorName";

	echo "<input TYPE=\"HIDDEN\" NAME=\"orderby\" VALUE=\"$orderby\">
	    <input TYPE=\"HIDDEN\" NAME=\"searchwhat\" VALUE=\"$searchwhat\">";

	foreach($fields as $aField)
	{
	    echo "<input TYPE=\"HIDDEN\" NAME=\"fields[]\" VALUE=\"$aField\">";
	}

	echo "<input TYPE=\"HIDDEN\" NAME=\"join\" VALUE=\"$join\">";
}

?>
