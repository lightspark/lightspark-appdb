<?

function log_category_visit($catId)
{
    global $REMOTE_ADDR;

    $result = mysql_query("SELECT * FROM catHitStats WHERE ip = '$REMOTE_ADDR' AND catId = $catId");
    if($result && mysql_num_rows($result) == 1)
	{
	    $stats = mysql_fetch_object($result);
	    mysql_query("UPDATE catHitStats SET count = count + 1 WHERE catHitId = $stats->catHitId");
	}
    else
	{
	    mysql_query("INSERT INTO catHitStats VALUES(null, null, '$REMOTE_ADDR', $catId, 1)");
	}
}

function log_application_visit($appId)
{
    global $REMOTE_ADDR;

    $result = mysql_query("SELECT * FROM appHitStats WHERE ip = '$REMOTE_ADDR' AND appId = $appId");
    if($result && mysql_num_rows($result) == 1)
         {
             $stats = mysql_fetch_object($result);
             mysql_query("UPDATE appHitStats SET count = count + 1 WHERE appHitId = $stats->appHitId");
         }
    else
	{
	    mysql_query("INSERT INTO appHitStats VALUES(null, null, '$REMOTE_ADDR', $appId, 1)");
	}
}

?>
