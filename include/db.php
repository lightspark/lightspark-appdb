<?php
function query_appdb($sQuery)
{
    global $hPublicLink;

    if(!$hPublicLink)
    {
        $hPublicLink = mysql_pconnect(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS);
        mysql_select_db(APPS_DB);
    }
    $hResult = mysql_query($sQuery, $hPublicLink);
    if(!$hResult)
    {
        $sStatusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
        addmsg($sStatusMessage, "red");
    }
    return $hResult;
}


function query_userdb($sQuery)
{
    global $hPrivateLink;

    if(!$hPrivateLink)
    {
        $hPrivateLink = mysql_pconnect(USERS_DBHOST, USERS_DBUSER, USERS_DBPASS);
        mysql_select_db(USERS_DB);
    }
    $hResult = mysql_query($sQuery, $hPrivateLink);
    if(!$hResult)
    {
        $sStatusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
        addmsg($sStatusMessage, "red");
    }
    return $hResult;
}
?>
