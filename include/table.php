<?php

// classes for managing tables and data related to tables

// class for managing the highlighting/inactive state of a row
class TableRowHighlight
{
  private $oHighlightColor;
  private $oInactiveColor;

  // properties to apply to text when highlighted or inactive
  private $sTextDecorationHighlight;
  private $sTextDecorationInactive;

  function TableRowHighlight(color $oHighlightColor, color $oInactiveColor,
                          $sTextDecorationHighlight = "",
                          $sTextDecorationInactive = "")
  {
    $this->oHighlightColor = $oHighlightColor;
    $this->oInactiveColor = $oInactiveColor;

    $this->sTextDecorationHighlight = $sTextDecorationHighlight;
    $this->sTextDecorationInactive = $sTextDecorationInactive;
  }

  function GetHighlightColor()
  {
    return $this->oHighlightColor;
  }

  function GetInactiveColor()
  {
    return $this->oInactiveColor;
  }

  function GetTextDecorationHighlight()
  {
    return $this->sTextDecorationHighlight;
  }

  function GetTextDecorationInactive()
  {
    return $this->sTextDecorationInactive;
  }
}

class TableRowClick
{
  var $oTableRowHighlight;
  var $bHasHighlight;
  var $shUrl;

  function TableRowClick($shUrl)
  {
    $this->shUrl = $shUrl;
    $this->bHasHighlight = false;
    $this->oTableRowHighlight = null;
  }

  function SetHighlight(TableRowHighlight $oTableRowHighlight)
  {
    $this->oTableRowHighlight = $oTableRowHighlight;
  }

  function GetString()
  {
    $sStr = "";

    // if we have highlighting output the attributes necessary to enable the javascript tht we use
    // to perform the highlighting actions
    if($this->oTableRowHighlight)
    {
      $sStr.= 'onmouseover="ChangeTr(this, true,'.
                                    '\''.$this->oTableRowHighlight->getHighlightColor()->GetHexString().'\','.
                                    '\''.$this->oTableRowHighlight->getInactiveColor()->GetHexString().'\','.
                                    '\''.$this->oTableRowHighlight->getTextDecorationHighlight().'\','.
                                    '\''.$this->oTableRowHighlight->getTextDecorationInactive().'\');"';
      $sStr.= ' onmouseout="ChangeTr(this, false,'.
                                   '\''.$this->oTableRowHighlight->getHighlightColor()->GetHexString().'\','.
                                   '\''.$this->oTableRowHighlight->getInactiveColor()->GetHexString().'\','.
                                   '\''.$this->oTableRowHighlight->getTextDecorationHighlight().'\','.
                                   '\''.$this->oTableRowHighlight->getTextDecorationInactive().'\');"';
    }

    $sStr.= ' onclick="DoNav(\''.$this->shUrl.'\');"';

    return $sStr;
  }
}

class TableCell
{
  private $sCell;
  private $sStyle;
  private $sClass;
  private $sAlign;  // align="$sAlign" will be output if this is not null
  private $sValign; // valign="$sValign" will be output if this is not null
  private $sWidth;  // width="$sWidth"
  private $sUrl;    // wraps the cell contents in an anchor tag if $sUrl is not null
  private $bBold;   // if true will output the cell contents as bold

  // NOTE: We specifically have limited the parameters to the constructor
  //       to only the contents of the cell. Additional parameters, while
  //       appearing convienent, make the parameters confusing
  //       Use accessors to set additional parameters.
  function TableCell($sCellContents)
  {
    $this->sCellContents = $sCellContents;
    $this->sStyle = null;
    $this->sClass = null;
    $this->sAlign = null;
    $this->sValign = null;
    $this->sWidth = null;
    $this->bBold = false;
  }

  function SetCellContents($sCellContents)
  {
    $this->sCellContents = $sCellContents;
  }

  function SetStyle($sStyle)
  {
    $this->sStyle = $sStyle;
  }

  function SetClass($sClass)
  {
    $this->sClass = $sClass;
  }

  function SetAlign($sAlign)
  {
    $this->sAlign = $sAlign;
  }

  function SetValign($sValign)
  {
    $this->sValign = $sValign;
  }

  function SetWidth($sWidth)
  {
    $this->sWidth = $sWidth;
  }

  function SetCellLink($sUrl)
  {
    $this->sUrl = $sUrl;
  }

  function SetBold($bBold)
  {
    $this->bBold = $bBold;
  }

  function GetString()
  {
    $sStr = "<td";

    if($this->sClass)
      $sStr.=" class=\"".$this->sClass."\";";

    if($this->sStyle)
      $sStr.=" style=\"".$this->sStyle."\";";

    if($this->sAlign)
      $sStr.=" align=\"".$this->sAlign."\";";

    if($this->sValign)
      $sStr.=" valign=\"".$this->sValign."\";";

    if($this->sWidth)
      $sStr.=" width=\"".$this->sWidth."\";";

    $sStr.=">";

    // if we have a url, output the start of the anchor tag
    if($this->sUrl)
      $sStr.='<a href="'.$this->sUrl.'">';

    if($this->bBold)
      $sStr.='<b>';

    // output the contents of the cell
    $sStr.=$this->sCellContents;

    if($this->bBold)
      $sStr.='</b>';

    // if we have a url, close the anchor tag
    if($this->sUrl)
      $sStr.='</a>';

    $sStr.="</td>";

    return $sStr;
  }
}

class TableRow
{
    private $aTableCells; // array that contains the cells for the table row
    private $sStyle; // CSS style to be used
    private $sClass; // CSS class to be used
    private $sValign; // valign="$sValign" - if this variable is set

    private $oTableRowClick; // information about whether the table row is clickable etc

    function TableRow()
    {
      $this->aTableCells = array();
      $this->sStyle = null;
      $this->sClass = null;
      $this->sValign = null;
      $this->oTableRowClick = null;
    }

    function AddCell(TableCell $oTableCell)
    {
      $this->aTableCells[] = $oTableCell;
    }

    function AddCells($aTableCells)
    {
      foreach($aTableCells as $oTableCell)
      {
        $this->AddCell($oTableCell);
      }
    }

    function AddTextCell($sCellText)
    {
      $this->AddCell(new TableCell($sCellText));
    }

    function SetStyle($sStyle)
    {
      $this->sStyle = $sStyle;
    }

    function SetClass($sClass)
    {
      $this->sClass = $sClass;
    }

    function SetValign($sValign)
    {
      $this->sValign = $sValign;
    }

    function SetRowClick($oTableRowClick)
    {
      $this->oTableRowClick = $oTableRowClick;
    }

    // get a string that contains the html representation
    // of this table row
    function GetString()
    {
      // generate the opening of the tr element
      $sStr = "<tr";

      if($this->sClass)
        $sStr.= " class=\"$this->sClass\"";

      if($this->sStyle)
        $sStr.= " style=\"$this->sStyle\"";

      if($this->sValign)
        $sStr.= " valign=\"$this->sValign\"";

      if($this->oTableRowClick)
        $sStr.= " ".$this->oTableRowClick->GetString();
      
      $sStr.= ">"; // close the opening tr

      // process the td elements
      foreach($this->aTableCells as $oTableCell)
      {
        $sStr.=$oTableCell->GetString();
      }

      // close the table row
      $sStr.= "</tr>";

      return $sStr;
    }

    function GetClass()
    {
      return $this->sClass;
    }

    function GetTableRowClick()
    {
      return $this->oTableRowClick;
    }
}

// object manager table row, has additional parameters used by the object manager
// when outputting a table row
//TODO: php5 consider inheriting from HtmlTableRow since this class is really an
//  extension of that class
class OMTableRow
{
  private $oTableRow;
  private $bHasDeleteLink;
  private $bCanEdit;

  function OMTableRow($oTableRow)
  {
    $this->oTableRow = $oTableRow;
    $this->bHasDeleteLink = false;
    $this->bCanEdit = false;
  }

  function SetHasDeleteLink($bHasDeleteLink)
  {
    $this->bHasDeleteLink = $bHasDeleteLink;
  }

  function GetHasDeleteLink()
  {
    return $this->bHasDeleteLink;
  }

  function SetRowClickable(TableRowClick $oTableRowClick)
  {
    $this->oTableRowClick = $oTableRowClick;
  }

  function SetStyle($sStyle)
  {
    $this->oTableRow->SetStyle($sStyle);
  }

  // add a TableCell to an existing row
  function AddCell($oTableCell)
  {
    $this->oTableRow->AddCell($oTableCell);
  }

  function GetString()
  {
    return $this->oTableRow->GetString();
  }

  function GetTableRow()
  {
    return $this->oTableRow;
  }
}

class Table
{
  private $oTableRowHeader;
  private $aTableRows;
  private $sClass;
  private $sWidth;
  private $iBorder;
  private $sAlign; // align="$sAlign" - deprecated in html standards
  private $iCellSpacing; // cellspacing="$iCellSpacing"
  private $iCellPadding; // cellpadding="$iCellPadding"

  function Table()
  {
    $this->oTableRowHeader = null;
    $this->aTableRows = array();
    $this->sClass = null;
    $this->sWidth = null;
    $this->iBorder = null;
    $this->sAlign = null;
    $this->iCellSpacing = null;
    $this->iCellPadding = null;
  }

  function AddRow($oTableRow)
  {
    $this->aTableRows[] = $oTableRow;
  }

  function SetHeader(TableRow $oTableRowHeader)
  {
    $this->oTableRowHeader = $oTableRowHeader;
  }

  function SetClass($sClass)
  {
    $this->sClass = $sClass;
  }

  function SetWidth($sWidth)
  {
    $this->sWidth = $sWidth;
  }

  function SetBorder($iBorder)
  {
    $this->iBorder = $iBorder;
  }

  function SetAlign($sAlign)
  {
    $this->sAlign = $sAlign;
  }

  function SetCellSpacing($iCellSpacing)
  {
    $this->iCellSpacing = $iCellSpacing;
  }

  function SetCellPadding($iCellPadding)
  {
    $this->iCellPadding = $iCellPadding;
  }

  function GetString()
  {
    $sStr = "<table";

    if($this->sClass)
      $sStr.= ' class="'.$this->sClass.'"';

    if($this->sWidth)
      $sStr.= ' width="'.$this->sWidth.'"';

    if($this->iBorder)
      $sStr.= ' border="'.$this->iBorder.'"';

    if($this->sAlign)
      $sStr.= ' align="'.$this->sAlign.'"';

    if($this->iCellSpacing)
      $sStr.= ' cellspacing="'.$this->iCellSpacing.'"';

    if($this->iCellPadding)
      $sStr.= ' cellpadding="'.$this->iCellPadding.'"';

    $sStr.= ">"; // close the open table element

    if($this->oTableRowHeader)
    {
      $sStr.="<thead>";
      $sStr.= $this->oTableRowHeader->GetString();
      $sStr.="</thead>";
    }

    foreach($this->aTableRows as $oTableRow)
    {
      $sStr.= $oTableRow->GetString();
    }

    $sStr.= "</table>";

    return $sStr;
  }
}

// input is the row index, we alternate colors based on odd or even index rows
// returns a TableRowHighlight instance
function GetStandardRowHighlight($iRowIndex)
{
  //set row color
  $sColor = ($iRowIndex % 2) ? "color0" : "color1";

  $oInactiveColor = new color();
  $oInactiveColor->SetColorByName($sColor);

  $oHighlightColor = GetHighlightColorFromInactiveColor($oInactiveColor);

  $oTableRowHighlight = new TableRowHighlight($oHighlightColor, $oInactiveColor);

  return $oTableRowHighlight;
}

// returns a color class instance
function GetHighlightColorFromInactiveColor(color $oInactiveColor)
{
  $oHighlightColor = new color($oInactiveColor->iRed,
                               $oInactiveColor->iGreen,
                               $oInactiveColor->iBlue);
  $oHighlightColor->Add(50);

  return $oHighlightColor;
}

?>
