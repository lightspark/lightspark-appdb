<?php


include(BASE."include/"."appversion_inc.php");
include(BASE."include/"."appbyvendor_inc.php");

function initFields()
{
	global $fields, $orderby, $join, $searchfor, $searchwhat;

	$fields = "";
	$searchfor = "";
	$searchwhat = "";
	$join = "";
	$orderby = "";
}


/*
 * perform a sql query
 */
function twinedb_query($query, $vars)
{
	// imports vars into symbol table
	extract($vars);

	if(debugging())
	    echo "QUERY: $query <p>";

	// Only permit sql SELECT statements
	if(!eregi("^select .*$", $query))
	    {
		echo "<b> Invalid SQL Query </b>";
		echo "<br> $query <br>";
		return;
	    }

	opendb();
	
	$tmpq = str_replace("\\", "", $query);

	$endPos=$curPos+$linesPerPage;
	$tcurpos = $curPos+$startapi;
	$tendpos = $endPos+$startapi;
	 
	// set a limit if not already set
        if(!stristr($query, "limit"))
		$tmpq .= " LIMIT $tcurpos,$linesPerPage";

	// execute the db query
	$tstamp = time();
        $result = mysql_query($tmpq);
	$tstamp = time() - $tstamp;
	
	if(debugging())
	    echo "<b> QUERY TIME: $tstamp seconds </b><br>\n";

	// query error!
        if(!$result) 
	{
                echo "$query <br><br>\n";
                echo "A QUERY error occurred: ".mysql_error()."\n";
                exit;
        }

        $numRows = mysql_num_rows($result);
        $numCols = mysql_num_fields($result);

	$curPage = $curPos/$linesPerPage;	
        $tmendpos = $curPos + $numRows;
	$explain = "stdquery.php?query=".urlencode("EXPLAIN $tmpq");


	echo html_br(2);

	// set $debug to enable query debugging
	if($debug || stristr($tmpq, "explain"))
	{
		$str = eregi_replace("(SELECT|EXPLAIN|DISTINCT|FROM|WHERE|AND".
				     "|OR |IS NULL|IS NOT NULL|LIMIT|ORDER BY".
				     "|GROUP BY)",
				     "<br><b>\\1</b><br>", $tmpq);
		echo "<br>$str<br>\n";
	}

	echo html_echo("<div align=center>");

	add_pn_buttons($vars, $tmendpos);
	echo html_br(2);

	// output table header
        echo html_table_begin("width='80%' cellspacing=1 border=0 rules=rows frame=hsides");
	$helems = array();
        for($k = 0; $k < $numCols; $k++)
        {
		$name = mysql_field_name($result, $k);
		$helems[] = $name;
		if($name == "apiid")
		    $have_apiid = 1;
	}
	echo html_th($helems, "title");

        $curapiid=0;
        $curName="[NONAME]";

        for($i = 0; $i < $numRows; $i++)
        {
		$row = mysql_fetch_array($result, MYSQL_BOTH);
		$color = ($i % 2);
		$arr = array();

                for($k = 0; $k < $numCols; $k++)
                {
			$fname  = mysql_field_name($result, $k);
			
			
			if($fname == "username")
			    {
				$username = $row[$k];
				$userid   = $row["userid"];
				$arr[] = html_ahref($username."&nbsp;", apidb_url("edituser.php?userid=$userid&username=$username"));
				continue;
			    }

			if($fname == "vendorName")
			{
				initFields();
				$url = "vendorview.php?vendorId=".$row["vendorId"];
				$arr[] = html_ahref($row[$k], $url);
				continue;
			}

			if($fname == "appName")
			{
				initFields();
				$url = "appview.php?appId=".$row["appId"];
				$arr[] = html_ahref($row[$k], $url);
				continue;

			}

			if($fname == "versionName")
			{
				$versionId = $row["versionId"];
				$url = "admin/editAppVersion.php?versionId=$versionId";
				$arr[] = html_ahref($row[$k], $url);
				continue;
			}

			if($fname == "webPage")
			{

				$url = $row[$k];
				$theLink = "$url";
				$arr[] = html_ahref($url, $theLink);

				continue;
			} 

			if(mysql_field_type($result, $k) == "int")
			{
				$val = (int)$row[$k];
				$arr[] =  "<div align=right>$val</div>";
			}			
			else
			{
				if(!$row[$k])
					$arr[] = "&nbsp";
				else
					$arr[] = "$row[$k]";
			}
		}
		
		echo html_tr($arr, "color$color");
        }

	echo html_table_end();
	echo html_br();

	add_pn_buttons($vars, $tmendpos);
	echo html_echo("</div>");

        mysql_free_result($result);
	closedb();
}


?>
<!-- end of query.php -->
