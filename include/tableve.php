<?

require(BASE."include/"."parsedate.php");

class TableVE {

    var $mode;
    var $titleField;
    var $titleText;
    var $numberedTitles;

    /*
     * mode can be: view, edit, create
     */
    function TableVE($mode)
    {
	$this->mode = $mode;
	$this->titleField = "";
	$this->titleText = "";
	$this->numberedTitles = 0;

	opendb();
    }

    function test($query)
    {
	$result  = mysql_query($query);
	$nfields = mysql_num_fields($result);
	$nrows   = mysql_num_rows($result);
	$table   = mysql_field_table($result, 0);

	echo "Table: $table <br> Fields: $nfields <br> Rows: $nrows <br> <br>\n";

	$i = 0;
	while($i < $nfields)
	    {
		$type = mysql_field_type($result, $i);
		$name = mysql_field_name($result, $i);
		$len  = mysql_field_len($result, $i);
		$flags = mysql_field_flags($result, $i);
		
		echo "$type | $name | $len | $flags <br>\n";
		$i++;
	    }
	
    }

    /* this is a bit of a hack,
     * we first create an empty entry, and then simply use the
     * edit() function to do the rest of the work for us.
     */
    function create($query, $table, $idcolumn)
    {
	$result = mysql_query($query);
	$id     = mysql_insert_id();
	
	$new_query = "SELECT * FROM $table WHERE $idcolumn = $id";
	$this->edit($new_query);
    }


    function view($query)
    {
	//$this->test($query);

	$nrows = 0;

	$result  = mysql_query($query);
        $nrows   = mysql_num_rows($result);

        if(debugging())
        {
		echo "Query returns $nrows rows.";
        }

	for($i = 0; $i < $nrows; $i++)
	    {
		$this->view_entry($result, $i);
		echo "<br>\n";
	    }
    }
    
    function view_entry($result, $num)
    {
        $nfields = mysql_num_fields($result);
	$fields = mysql_fetch_array($result, MYSQL_BOTH);

	$titleValue = $fields[$this->titleField];
	$titleText = $this->titleText;
	if($this->numberedTitles)
	{
	    // don't want zero-based.
	    $num++;
	    $titleText .= "  # $num";
	}

        //echo "<table border=1 bordercolor=black width='80%' cellpadding=0 cellspacing=0>\n";
        //echo "<th class='box-title' colspan='2'></th></tr>\n";

        //echo "<tr><td>\n";
        
	echo html_frame_start("Viewing $titleValue $titleText","80%","",0);
	echo "<table border=0 width='100%' cellspacing=0 cellpadding=2>\n";

	for($i = 0; $i < $nfields; $i++)
	    {
		$field = mysql_fetch_field($result, $i);

                if(ereg("^impl_(.+)$", $field->table, $arr))
                    {
                        if($cur_impl != $arr[1])
                            echo "<tr><th class='box-label' colspan=2> ".ucfirst($arr[1])." Implementation </th></tr>\n";
                        $cur_impl = $arr[1];
                    }
		
		echo "<tr><td width='15%' class='box-label'><b> $field->name </b></td>"; 
		echo "<td class='box-body'>";
		$this->view_entry_output_field($field, $fields[$i], 0);
		echo "</td></tr>\n";
	    }

        echo "</table>\n";
        echo html_frame_end();

    }


    function edit($query)
    {
        $result  = mysql_query($query);
	if(!$result)
	    echo "Oops: ".mysql_error()."<br>$query<br>\n";
        $nrows   = mysql_num_rows($result);

        echo "<form method=post action='".apidb_url("editapi.php")."'>\n";

        for($i = 0; $i < $nrows; $i++)
            {
                $this->edit_entry($result);
                echo "<br>\n";
            }

        echo html_frame_start("Update Database",100);
        echo "<input type=submit value='Update Database'>\n";
	echo html_frame_end();
	
	echo "</form>\n";
    }

    
    function edit_entry($result)
    {
	$nfields = mysql_num_fields($result);
	$fields = mysql_fetch_array($result);
	
	echo html_frame_start(ucfirst($this->mode),"80%","",0);
	echo "<table border=0 width='100%' cellspacing=0 cellpadding=2>\n";

	$cur_impl = null;
        for($i = 0; $i < $nfields; $i++)
            {
		global $testvar;
                $field = mysql_fetch_field($result, $i);
		$len   = mysql_field_len($result, $i);

		if(ereg("^impl_(.+)$", $field->table, $arr))
		    {
			if($cur_impl != $arr[1])
			    echo "<tr><th class='box-label' colspan=2> ".ucfirst($arr[1])." Implementation </th></tr>\n";
			$cur_impl = $arr[1];
		    }

                echo "<tr><td width='15%' class='box-label'><b> $field->name &nbsp; </b></td>";
                echo "<td class='box-body'>&nbsp;";
		$this->edit_entry_output_field($field, $fields[$i], $len);
		echo "</td></tr>\n";
            }

        echo "</table>\n";
	echo html_frame_end();
    }

    function timestamp_to_unix($stamp)
    {
	$result = mysql_query("select unix_timestamp($stamp)");
	if(!$result)
	    return 0;
	$r = mysql_fetch_row($result);
	return $r[0];
    }

    function make_option_list($varname, $cvalue, $table, $idField, $nameField, $where = "")
    {
    
	$result = mysql_query("SELECT $idField, $nameField FROM $table $where ORDER BY $nameField");
	if(!result)
	    return; // Oops

	echo "<select name='$varname'>\n";
	echo "<option value=0>Choose ...</option>\n";
	while(list($id, $name) = mysql_fetch_row($result))
	    {
	        if ($name == "NONAME")
		    continue;
		if($id == $cvalue)
		    echo "<option value=$id selected>$name\n";
		else
		    echo "<option value=$id>$name\n";

	    }
	echo "</select>\n";
    }


    function edit_entry_output_field($field, $value, $len)
    {
	static $idx = 0;

	$idx++;
	if($len > 50)
	    $len = 50;

	$varname = "FIELD_".$field->table."___".$field->name."[]";
	echo "<input type=hidden name='TYPE_$varname' value='$field->type'>\n";

	if($field->name == "appId" && $field->table != "appFamily")
	    {
		$this->make_option_list($varname, $value, "appFamily", "appId", "appName");
		return;
	    }

	if($field->name == "vendorId" && $field->table != "vendor")
	    {
		$this->make_option_list($varname, $value, "vendor", "vendorId", "vendorName");
		return;
	    }

	if($field->name == "catId" && $field->table != "appCategory")
	    {
		$this->make_option_list($varname, $value, "appCategory", "catId", "catName");
		return;
	    }

	if($field->name == "catParent")
	    {
		$this->make_option_list($varname, $value, "appCategory", "catId", "catName");
		return;
	    }
	
	if($field->name == "keywords")
	    {
		echo "<textarea cols=$len rows=3 name='$varname'>".stripslashes($value)."</textarea>\n";
		return;
	    }
	    
	switch($field->type)
	    {
	    case "string":
	    case "enum":
	    case "int":
	    case "text":
		echo "<input type=text size=$len name='$varname' value='".stripslashes($value)."'>\n";
		break;
	    case "blob":
		echo "<textarea cols=$len rows=10 name='$varname'>".stripslashes($value)."</textarea>\n";
		break;
	    case "timestamp":
		$time = $this->timestamp_to_unix($value);
		echo makedate($time);
		break;
	    case "datetime":
		$time = parsedate($value);
		echo makedate($time);
		break;
	    default:
		echo "$value &nbsp;\n";
		break;
	    }

	$this->entry_add_extra($field, $value);
    }



    function view_entry_output_field($field, $value, $len)
    {
        if($len > 50)
            $len = 50;

	//FIXME: need a better way for special cases
	if(!$value && $field->name == "comments")
	    {
		echo "none";
		return;
	    }
	if(!$value && ($field->name == "location" || $field->name == "quality"))
	    {
		echo "unknown";
		return;
	    }

        if($field->name == "lastmodby")
            {
                $user = new user();
                $name = $user->lookup_username($value);
                if(!$name)
                    $name = "system";
                echo "$name ($value)";
                return;
            }

	
        switch($field->type)
            {
            case "string":
            case "enum":
            case "int":
            case "blob":
                echo "$value &nbsp;\n";
                break;
            case "timestamp":
                $time = $this->timestamp_to_unix($value);
                echo makedate($time);
                break;
            case "datetime":
                $time = parsedate($value);
                echo makedate($time);
                break;
            default:
                echo "$value &nbsp;\n";
                break;
            }

	$this->entry_add_extra($field, $value);
    }


    /*
     * add extra stuff to certain fields
     */
    function entry_add_extra($field, $value)
    {
	/*
         * add extra stuff to certain fields
         */
	
        if($field->name == "mslink" && $value)
            {
                echo html_imagebutton("Go!", $value);
            }
	
        if($field->name == "apiname")
            {
                echo html_imagebutton("Wine LXR", "http://twine.codeweavers.com/lxr/ident?i=$value");
                echo html_imagebutton("Wine API", "http://www.winehq.com/WineAPI/$value.html");
            }
    }



    /*
     * required field for each table.
     * When editing a query this field needs to be present in the query
     * in order to identify the correct row to update.
     */
    var $table_ids = array(
                           "user_list"       => "userid",
                           "appFamily"       => "appId",
                           "appVersion"      => "versionId",
                           "userExperience"  => "uExpId",
                           "appCategory"     => "catId",
                           "vendor"          => "vendorId",
                           "appNotes"        => "noteId"
                          );

    function get_id($name)
    {
	reset($this->table_ids);
	while(list($table, $id) = each($this->table_ids))
	    {
		$r = "^$table$";
		//echo "Checking $r against $name <br>\n";
		if(ereg($r, $name))
		    {
			//echo "ID for $name -> $id <br>\n";
			return $id;
		    }
	    }
	return null;
    }

    /*
     * update() expects $HTTP_POST_VARS as argument
     * this is where things are getting kinda complex, here we update "
     * multiple entries with multiple fields in multiple tables (get it?)
     */
    function update($vars)
    {
	global $current;

	$tables = array();
	$fieldnames = array();
	$num_entries = 0;
	
	while(list($varname, $arr) = each($vars))
	    {
		if(!ereg("^FIELD_([a-zA-Z_]+)___(.+)$", $varname, $regs))
		    continue;
				
		$tables[$regs[1]][] = $regs[2];
		$fieldnames[$regs[2]] = $arr;
		$num_entries = sizeof($arr);
	    }
	
	while(list($table, $fields) = each($tables))
	    {
		echo "<b> $table (".$this->get_id($table).") </b>";
		
		if($fieldnames[$this->get_id($table)])
		    echo "OK!";
		
		echo "<br>\n";
		
		for($i = 0; $i < sizeof($fields); $i++)
		    echo "- $fields[$i] <br>\n";
		
		echo "<br>\n";
	    }

	for($i = 0; $i < $num_entries; $i++)
	    {
		reset($tables);
		while(list($table, $fields) = each($tables))
		    {
			$update = "UPDATE $table SET ";
			
			$count = sizeof($fields);
			reset($fields);
			while(list($idx, $field) = each($fields))
			    {
				$count--;

				if($this->table_ids[$table] == $field)
				    {
					continue;
				    }
				$key  = "FIELD_".$table."___".$field;
				$type = $vars["TYPE_$key"][$i];
				
				if($type == "int")
				    $update .= "$field = ".$vars[$key][$i];
				else
				    $update .= "$field = '".addslashes($vars[$key][$i])."'";

				if($count)
				    $update .= ", ";
			    }
			    
			$value = $fieldnames[$this->get_id($table)][$i];
			
			$update .= " WHERE ".$this->get_id($table)." = $value";
						
			if(!mysql_query($update))
			{
			    $thisError = "<p><font color=black><b>Query:</b>: $update</font></p>\n";
			    $thisError .= "<p><font color=red>".mysql_error()."</font></p>";
			    addmsg($thisError,"red");
			}
			else
			{
			    addmsg("Database Operation Complete!","green");
			}

			if(ereg("^impl_.+$", $table))
			    {
				$value = $fieldnames["apiid"][$i];
				mysql_query("UPDATE $table SET lastmodby = $current->userid WHERE apiid = $value");
			    }
		    }
	    }
	    
	    

    }

    function set_title_field($newTitleField)
    {
	$this->titleField = $newTitleField;
    }

    function set_title_text($newTitleText)
    {
	$this->titleText = $newTitleText;
    }

    function set_numbered_titles()
    {
	$this->numberedTitles = 1;
    }

};

?>
