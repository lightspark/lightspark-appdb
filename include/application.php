<?php
/***********************************************************/
/* this class represents an application incl. all versions */
/***********************************************************/

class Application {

    var $data;

    function Application($id)
    {
	$result = query_appdb("SELECT * FROM appFamily WHERE appId = $id");
	if(!$result)
	    return; // Oops
	if(mysql_num_rows($result) != 1)
	    return; // Not found

	$this->data = mysql_fetch_object($result);
    }


    function getAppVersionList()
    {
	$list = array();

	$result = query_appdb("SELECT * FROM appVersion ".
			      "WHERE appId = ". $this->data->appId . " " .
			      "ORDER BY versionName");
	if(!$result)
	    return $list;

	while($row = mysql_fetch_object($result))
	    {
		if($row->versionName == "NONAME")
		    continue;
		$list[] = $row;
	    }

	return $list;
    }

    function getAppVersion($versionId)
    {
	$result = query_appdb("SELECT * FROM appVersion ".
                              "WHERE appId = ". $this->data->appId ." AND ".
			      "versionId = $versionId");
        if(!$result || mysql_num_rows($result) != 1)
            return 0;

        return mysql_fetch_object($result);
    }

    function getVendor()
    {
	$result = query_appdb("SELECT * FROM vendor ".
			      "WHERE vendorId = ". $this->data->vendorId);
	if(!$result || mysql_num_rows($result) != 1)
	    return array("vendorName" => "Unknown");

	$vendor = mysql_fetch_object($result);
	return $vendor;
    }

    function getComments($versionId = 0)
    {
	$list = array();

        $result = query_appdb("SELECT * FROM appComments ".
                              "WHERE appId = ". $this->data->appId . " AND " .
			      "versionId = $versionId " .
                              "ORDER BY time");
        if(!$result)
            return $list;
	
        while($row = mysql_fetch_object($result))
            $list[] = $row;
	
        return $list;
    }
}

function deleteAppFamily($appId)
{
    $r = query_appdb("DELETE FROM appFamily WHERE appId = $appId", "Failed to delete appFamily $appId");
    if($r)
    {
        $r = query_appdb("DELETE FROM appVersion WHERE appId = $appId", "Failed to delete appVersions");
        if($r)
            addmsg("Application and versions deleted", "green");
    }   
}

function deleteAppVersion($versionId)
{
    $r = query_appdb("DELETE FROM appVersion WHERE versionId = $versionId","Failed to delete appVersion $versionId");
    if($r)
        addmsg("Application Version $versionId deleted", "green");
}

function lookupVersionName($appId, $versionId)
{
    $result = query_appdb("SELECT versionName FROM appVersion WHERE versionId = $versionId and appId = $appId");
    if(!$result || mysql_num_rows($result) != 1)
        return null;
    $ob = mysql_fetch_object($result);
    return $ob->versionName;
}


function lookupAppName($appId)
{
    $result = query_appdb("SELECT appName FROM appFamily WHERE appId = $appId");
    if(!$result || mysql_num_rows($result) != 1)
        return null;
    $ob = mysql_fetch_object($result);
    return $ob->appName;
}


/**
 * Remove html formatting from description and extract the first part of the description only.
 * This is to be used for search results, application summary tables, etc.
 */ 
function trim_description($sDescription)
{
    // 1) let's take the first line of the description:
    $aDesc = explode("\n",trim($sDescription),2);
    // 2) maybe it's an html description and lines are separated with <br> or </p><p>
    $aDesc = explode("<br>",$aDesc[0],2);
    $aDesc = explode("<br />",$aDesc[0],2);
    $aDesc = explode("</p><p>",$aDesc[0],2);
    $aDesc = explode("</p><p /><p>",$aDesc[0],2);
    return trim(strip_tags($aDesc[0]));
}
?>
