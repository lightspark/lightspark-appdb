<?php
require_once(BASE."include/util.php");

/*********************/
/* Edit Account Form */
/*********************/

// returns an array of TableRow instances
function GetEditAccountFormRows($sUserEmail, $sUserRealname)
{
  $aTableRows = array();

  $oTableRow = new TableRow();
  $oTableRow->AddTextCell("&nbsp; Email Address");
  $oTableRow->AddTextCell('<input type="text" name="sUserEmail" '.
                          'value="'.$sUserEmail.'">');
  $aTableRows[] = $oTableRow;

  $oTableRow = new TableRow();
  $oTableRow->AddTextCell("&nbsp; Password");
  $oTableRow->AddTextCell('<input type="password" name="sUserPassword">');
  $aTableRows[] = $oTableRow;

  $oTableRow = new TableRow();
  $oTableRow->AddTextCell("&nbsp; Password (again)");
  $oTableRow->AddTextCell('<input type="password" name="sUserPassword2">');
  $aTableRows[] = $oTableRow;

  $oTableRow = new TableRow();
  $oTableRow->AddTextCell("&nbsp; Real Name");
  $oTableRow->AddTextCell('<input type="text" name="sUserRealname" value="'.$sUserRealname.'">');
  $aTableRows[] = $oTableRow;

  $oTableRow = new TableRow();
  $oTableRow->AddTextCell("&nbsp;");
  $oTableRow->AddTextCell("&nbsp;");
  $aTableRows[] = $oTableRow;

  return $aTableRows;
}

?>
