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
<!-- start of App query -->
<table border=1 width="100%" cellspacing=0 cellpadding=3 bordercolor=black>
  <tr>
    <th class="box-title">Search Apps by Vendor
    </th>
  </tr>
  <tr>
    <td class="box-body">
      <form ACTION="stdquery.php" METHOD="get">
      Vendor Name:
      <input TYPE="TEXT" NAME="searchfor" /> (leave blank to match all)
<?php
include(BASE."include/"."appbyvendor.php");

output_appbyvendor_forminputs();

?>
    <br><br>
    <input type=checkbox name=verbose value=yes> Verbose query results <br>
    <? if(havepriv("admin")) echo "<input type=checkbox name=mode value=edit> Edit mode <br>\n"; ?>

        <br>Entries Per Page:
<select NAME="linesPerPage">
  <option>50
  <option>100
  <option>150
  <option>200
  <option>500
  <option>ALL
</select>
<br /> <input TYPE="SUBMIT" VALUE="List Apps" />
      </form>
    </td>
  </tr>
</table>
<!-- end of App query -->
