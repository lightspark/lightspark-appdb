<?

/* this class represents an application incl. all versions */
class Application {

    var $data;

    function Application($id)
    {
	$result = mysql_query("SELECT * FROM appFamily WHERE appId = $id");
	if(!$result)
	    return; // Oops
	if(mysql_num_rows($result) != 1)
	    return; // Not found

	$this->data = mysql_fetch_object($result);
    }


    function getAppVersionList()
    {
	$list = array();

	$result = mysql_query("SELECT * FROM appVersion ".
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
	$result = mysql_query("SELECT * FROM appVersion ".
                              "WHERE appId = ". $this->data->appId ." AND ".
			      "versionId = $versionId");
        if(!$result || mysql_num_rows($result) != 1)
            return 0;

        return mysql_fetch_object($result);
    }

    function getVendor()
    {
	$result = mysql_query("SELECT * FROM vendor ".
			      "WHERE vendorId = ". $this->data->vendorId);
	if(!$result || mysql_num_rows($result) != 1)
	    return array("vendorName" => "Unknown");

	$vendor = mysql_fetch_object($result);
	return $vendor;
    }

    function getComments($versionId = 0)
    {
	$list = array();

        $result = mysql_query("SELECT * FROM appComments ".
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
