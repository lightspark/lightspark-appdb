<?php
function query_appdb($sQuery,$sComment="")
{
    global $hAppdbLink;

    if(!$hAppdbLink)
    {
        $hAppdbLink = mysql_connect(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS);
        mysql_select_db(APPS_DB);
    }
    $hResult = mysql_query($sQuery, $hAppdbLink);
    if(!$hResult) query_error($sQuery, $sComment);
    return $hResult;
}

function query_userdb($sQuery,$sComment="")
{
    global $huserdbLink;

    if(!$huserdbLink)
    {
        $huserdbLink = mysql_connect(USERS_DBHOST, USERS_DBUSER, USERS_DBPASS);
        mysql_select_db(USERS_DB);
    }
    $hResult = mysql_query($sQuery, $huserdbLink);
    if(!$hResult) query_error($sQuery, $sComment);
    return $hResult;
}



function query_bugzilladb($sQuery,$sComment="")
{
    global $hBugzillaLink;

    if(!$hBugzillaLink)
    {
        $hBugzillaLink = mysql_connect(BUGZILLA_DBHOST, BUGZILLA_DBUSER, BUGZILLA_DBPASS);
        mysql_select_db(BUGZILLA_DB);
    }
    $hResult = mysql_query($sQuery, $hBugzillaLink);
    if(!$hResult) query_error($sQuery, $sComment);
    return $hResult;
}


function query_error($sQuery, $sComment="")
{
    $sStatusMessage  = "<p><b>Database Error!</b><br />";
    $sStatusMessage .= "Query: ".$sQuery;
    $sStatusMessage .= $sComment ? $sComment."<br />" : "";
    $sStatusMessage .= mysql_error()."</p>\n";
    addmsg($sStatusMessage, "red");
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
