<?php

function log_category_visit($catId)
{
    global $REMOTE_ADDR;

    $result = query_appdb("SELECT * FROM catHitStats WHERE ip = '$REMOTE_ADDR' AND catId = $catId");
    if($result && mysql_num_rows($result) == 1)
    {
        $stats = mysql_fetch_object($result);
        query_appdb("UPDATE catHitStats SET count = count + 1 WHERE catHitId = $stats->catHitId");
    } else
    {
        query_parameters("INSERT INTO catHitStats (appHitId, time, ip, catId, count) ".
                         "VALUES (?, ?, '?', '?', '?')",
                         "null", "null", $REMOTE_ADDR, $catId, "1");
    }
}

function log_application_visit($appId)
{
    global $REMOTE_ADDR;

    $result = query_appdb("SELECT * FROM appHitStats WHERE ip = '$REMOTE_ADDR' AND appId = $appId");
    if($result && mysql_num_rows($result) == 1)
    {
        $stats = mysql_fetch_object($result);
        query_appdb("UPDATE appHitStats SET count = count + 1 WHERE appHitId = $stats->appHitId");
    } else
    {
        query_parameters("INSERT INTO appHitStats (appHitId, time, ip, appId, count) ".
                         "VALUES (?, ?, '?', '?', '?')",
                         "null", "null", $REMOTE_ADDR, $appId, "1");
    }
}

?>
