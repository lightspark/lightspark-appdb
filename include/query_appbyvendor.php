<!-- start of App query -->
<table border=1 width="100%" cellspacing=0 cellpadding=3 bordercolor=black>
  <tr>
    <th class="box-title">Search Apps by Vendor
    </th>
  </tr>
  <tr>
    <td class="box-body">
      <form ACTION="stdquery.php" METHOD="get">
	Vendor Name:
	<input TYPE="TEXT"   NAME="searchfor"> (leave blank to match all)
<?
	include(BASE."include/"."appbyvendor_inc.php");

	output_appbyvendor_forminputs();

?>
    <br><br>
    <input type=checkbox name=verbose value=yes> Verbose query results <br>
    <? if(havepriv("admin")) echo "<input type=checkbox name=mode value=edit> Edit mode <br>\n"; ?>

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

