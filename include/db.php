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

/**
* Expects an array in this form:
* $aFoo['field'] = 'value';
* 
* Returns an array ready to be put in a query like this
* $sQuery = "INSERT INTO `foo` {$aReturn['FIELDS']} VALUES {$aReturn['VALUES']}";
* 
* Values are addslashes()'d.
*/

function compile_insert_string($aData)
{
    foreach ($aData as $k => $v)
    {
        $field_names .= "`$k`,";
        $field_values .= "'".addslashes($v)."',";
    }

    // Get rid of the end ,
    $field_names  = preg_replace( "/,$/" , "" , $field_names  );
    $field_values = preg_replace( "/,$/" , "" , $field_values );

    return array('FIELDS' => $field_names, 'VALUES' => $field_values);
}

/**
* Expects an array in this form:
* $aFoo['field'] = 'value';
* 
* Returns a string ready to be put in a query like this
* $sQuery = "UPDATE `foo` $sReturn";
* 
* Values are addslashes()'d.
*/
function compile_update_string($aData)
{
    foreach ($aData as $k => $v) 
    {
        $return .= "`$k`='".addslashes($v)."',";
    }
    
    $return = preg_replace( "/,$/" , "" , $return );
    
    return $return;
}
?>
