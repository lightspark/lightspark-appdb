<?php
$public_link = null;
$private_link = null;


function apidb_query($query)
{
    global $public_link;

    if(!$public_link)
    {
        $public_link = mysql_pconnect($db_public_host, $db_public_user, $db_public_pass);
        mysql_select_db($db_public_db);
    }

    return mysql_query($query, $public_link);
}


function userdb_query($query)
{
    global $private_link;
    
    if(!$private_link)
    {
        $private_link = mysql_pconnect($db_private_host, $db_private_user, $db_private_pass);
        mysql_select_db($db_private_db);
    }
    
    return mysql_query($query, $private_link);
}

?>
