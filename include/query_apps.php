
<!-- start of App query -->
<table border=1 width="100%" cellspacing=0 cellpadding=3 bordercolor=black>
  <tr>
    <th class="box-title">Search Apps
    </th>
  </tr>
  <tr>
    <td class="box-body">
      <form ACTION="stdquery.php" METHOD="get">
	<input TYPE="HIDDEN" NAME="orderby" VALUE="appId">
	App Name:
	<input TYPE="TEXT"   NAME="searchfor"> (leave blank to match all)
	<input TYPE="HIDDEN" NAME="searchwhat" VALUE="appFamily.appName">
	<input TYPE="HIDDEN" NAME="fields[]" VALUE="appFamily.appId">
	<input TYPE="HIDDEN" NAME="fields[]" VALUE="appFamily.appName">
	<input TYPE="HIDDEN" NAME="fields[]" VALUE="appFamily.webPage">
    <br><br>
    <input type=checkbox name=verbose value=yes> Verbose query results <br>
    <? if(havepriv("admin")) echo "<input type=checkbox name=mode value=edit> Edit mode <br>\n"; ?>

    <br>Rating 
	<select NAME="rating">
	<option>ANY
	<option>1
	<option>2
	<option>3
	<option>4
	<option>5
	</select> or higher
	
	<select NAME="system">
	<option>ANY
	<option value=windows> Windows
	<option value=fake> Fake Windows
	</select>

        <br>Entries Per Page:
	<select NAME="linesPerPage">
	  <option>50
	  <option>100
	  <option>150
	  <option>200
	  <option>500
	  <option>ALL
	</select>
	<br> <input TYPE="SUBMIT" VALUE="List Apps">
      </form>
    </td>
  </tr>
</table>
<!-- end of App query -->

