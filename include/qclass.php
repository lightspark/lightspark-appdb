<?php
/*********************************************************/
/* query class                                           */
/* (de)compose/exec queries                              */
/* this should have query.php's query preprocessing etc. */
/*********************************************************/

class qclass {

    var $fields;
    var $tables;
    var $where;
    var $limit;
    var $order;

    var $table_ids = array("apimsdefinition" => "apiid",
			   "apimslinks"      => "apiid",
			   "dlldefinition"   => "dllid",
			   "implementation"  => "apiid",
			   "user_list"       => "userid",
			   "project_list"    => "id",
			   "appFamily"       => "appId",
			   "appVersion"      => "versionId",
			   );




    function qclass()
    {
	$this->clear();
    }


    function clear()
    {
	$this->fields = array();
	$this->tables = array();
	$this->where  = array();
	$this->limit  = 10;
	$this->order  = "";
    }


    /*
     * resolve used tables from fields
     */
    function resolve_tables($fields)
    {
	$tables = array();
	while(list($idx, $field) = each($fields))
	    {
		//echo "Field: $field <br>\n";
		if(!ereg("^(.+)\\.(.+)$", $field, $arr))
		    continue;
		$tables[$arr[1]] = $arr[1];
	    }
	return values($tables);
    }



    function get_id($table)
    {
	$id = $this->table_ids[$table];
	if($id)
	    return $id;
	if(ereg("^impl_.*$", $table))
	    return "apiid";
	return null;
    }

    function get_rel($table1, $table2)
    {
	$id1 = $this->get_id($table1);
	$id2 = $this->get_id($table2);

	if($id1 == "dllid" && $table2 == "apimsdefinition")
	    return $id1;
	if($id2 == "dllid" && $table1 == "apimsdefinition")
	    return $id2;

	if($id1 == $id2)
	    return $id1;

	return null;
    }

    function resolve_where($tables)
    {
	$tables = values($tables);
	$arr = array();
	$have = array();
	while(list($idx, $table) = each($tables))
	    {
		for($i = 0; $i < sizeof($tables); $i++)
		    {
			//echo "Checking $table - $tables[$i] <br>\n";
			if($table == $tables[$i])
			    continue;
			$id = $this->get_rel($table, $tables[$i]);
			if(!$id)
			    continue;
			if($have[$id][$table])
			    continue;
			$have[$id][$table] = 1;
			$have[$id][$tables[$i]] = 1;
			$arr[] = "$table.$id = $tables[$i].$id";
		    }
	    }

	/*
	  apidb_header();
	  echo "RESULT: ".implode(" AND ", $arr);
	  apidb_footer();
	  exit;
	*/
	return $arr;
    }



    function process($vars)
    {
	extract($vars);
	//var_dump($vars);

	$sfields = $fields;

	if(!$implementations)
	    $implementations = array("wine");  //FIXME
	
	while(list($idx, $impl) = each($implementations))
	    {

		// Check for quality?
		if($quality[$idx] && $quality[$idx] != "ALL")
		    {
			if($quality[$idx] == "UNKNOWN")
			    $this->where[] = "impl_$impl.quality IS NULL";
			else
			    $this->where[] = "impl_$impl.quality >= $quality[$idx]";
			$sfields[] = "impl_$impl.quality";
		    }
		
		// Check for presence?
		if($presence[$idx] && $presence[$idx] != "ALL")
		    {
			$this->where[] = "impl_$impl.presence = '$presence[$idx]'";
			$sfields[] = "impl_$impl.presence";
		    }
		
		// Check last modified?
		if($lastmod[$idx] > 0)
		    {
			$time = time() - ($lastmod[$idx] * 24 * 3600);
			$this->where[] = "impl_$impl.lastmod > from_unixtime($time)";
			$sfields[] = "impl_$impl.lastmod";
		    }
		
	    }

	// Search in a specific DLL?
	if($dllid && $dllid != "ALL")
	    $this->where[] = "dlldefinition.dllid = $dllid";

	// Check for rating? (APPDB)
	if($rating && $rating != "ANY")
	    {
		
		$q = "";
		if($system == "ANY" || $system == "windows")
		    {
			$q .= " appVersion.rating_windows >= $rating ";
			$sfields[] = "appVersion.rating_windows";
		    }
		if($system == "ANY" || $system == "fake")
		    {
			if($system == "ANY")
			    $q .= " OR ";
			$q .= " appVersion.rating_fake >= $rating ";
                        $sfields[] = "appVersion.rating_fake";
		    }
		$this->where[] = "appVersion.appId = appFamily.appId AND ($q)";
	    }

        // Are we searching?
        if($searchfor)
	    {
		if(ereg("^[0-9]+$", $searchfor))
		    // exact match if we're searching for a number
		    $this->where[] = "$searchwhat = $searchfor";
		else
		    // patterns are case insensitive in MySQL
		    $this->where[] = "$searchwhat LIKE '%$searchfor%'";
	    }

	// Must we join?
	if($join)
        {
		$this->where[] = $join;
	}

        $this->fields = $fields;
        $this->tables = $this->resolve_tables($sfields);
        $this->where  = array_merge($this->resolve_where($this->tables), $this->where);
	
    }

    function add_where($str)
    {
	$this->where[] = $str;
    }

    function add_field($field)
    {
	$this->fields[] = $field;
    }

    function add_fields($arr)
    {
	$this->fields = array_merge($this->fields, $arr);
    }

    function resolve()
    {
        $this->tables = $this->resolve_tables($this->fields);
        $this->where  = array_merge($this->resolve_where($this->tables), $this->where);
    }


    function get_query()
    {
	$query = array();
	$query[] = "SELECT";
	$query[] = implode(", ", $this->fields);
	$query[] = "FROM";
	$query[] = implode(", ", $this->tables);
	if(sizeof($this->where))
	    {
		$query[] = "WHERE";
		$query[] = implode(" AND ", $this->where);
	    }
	// add LIMIT etc.

	return implode(" ", $query);
    }
}
