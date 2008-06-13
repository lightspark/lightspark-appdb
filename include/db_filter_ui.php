<?php

/**
 * User interface for the SQL filter classes
 *
 * Copyright 2008 BitSplash Software LLC
 * Copyright 2008 Alexander N. Sørnes <alex@thehandofagony.com>
 *
*/

require_once('db_filter.php');

define(FILTER_VALUES_NORMAL, 1);
define(FILTER_VALUES_ENUM, 2);
define(FILTER_VALUES_BOOL, 3);

/* Info describing an available filter: what column it applies to,
    and what comparison options are available */
class FilterInfo
{
    private $sColumn;
    private $sDisplayName;
    private $aTypes; // Available filters for this column
    private $iValueType; // Normal, enum ...
    private $aValueTypeData; // List of enums
    private $aValueTypeDataDisplay; // Optional display names for enums

    public function FilterInfo($sColumn, $sDisplayName, $aTypes, $iValueType = FILTER_VALUES_NORMAL, $aValueTypeData = array(), $aValueTypeDisplay = array())
    {
        $this->sColumn = $sColumn;
        $this->sDisplayName = $sDisplayName;
        $this->aTypes = $aTypes;
        $this->iValueType = $iValueType;
        $this->aValueTypeData = $aValueTypeData;

        if(sizeof($aValueTypeData) && !sizeof($aValueTypeDisplay))
            $this->aValueTypeDataDisplay = $aValueTypeData;
        else
            $this->aValueTypeDataDisplay = $aValueTypeDisplay;
    }

    public function getColumn()
    {
        return $this->sColumn;
    }

    public function getDisplayName()
    {
        return $this->sDisplayName;
    }

    public function getValueType()
    {
        return $this->iValueType;
    }

    public function getValueTypeData()
    {
        return $this->aValueTypeData;
    }

    public function getValueTypeDataDisplay()
    {
        return $this->aValueTypeDataDisplay;
    }

    public function getTypes()
    {
        return $this->aTypes;
    }

    public static function getOpName($iOpId)
    {
        switch($iOpId)
        {
            case FILTER_EQUALS:
                return 'equal to';
            case FILTER_LIKE:
                return 'like';
            case FILTER_NOT_LIKE:
                return 'not like';
            case FILTER_NOT_EQUALS:
                return 'not equal to';
            case FILTER_LESS_THAN:
                return 'less than';
            case FILTER_GREATER_THAN:
                return 'greater than';
        }
    }
}

/* Class handling tables where the user can filter contents */
class FilterInterface
{
    private $aFilterInfo;
    private $oFilterSet;
    private $aEscapeChars;
    private $aEscapeCharsWith;

    public function FilterInterface($sTableName = '')
    {
        $this->aFilterInfo = array();
        $this->oFilterSet = new FilterSet(query_escape_string($sTableName));
        $this->aEscapeChars = array('.');
        $this->aEscapeCharsWith = array('-');
    }

    public function AddFilterObject(Filter $oFilter)
    {
        $this->oFilterSet->AddFilterObject($oFilter);
    }

    public function setFilterSet(FilterSet $oSet)
    {
        $this->oFilterSet = $oSet;
    }

    /* Convenience function to add a filter option */
    public function AddFilterInfo($sColumn, $sDisplayName, $aTypes, $iValueType = VALUE_TYPE_NORMAL, $aValueTypeData = array(), $aValueTypeDisplay = array())
    {
        $this->aFilterInfo[$sColumn] = new FilterInfo($sColumn, $sDisplayName, $aTypes, $iValueType, $aValueTypeData, $aValueTypeDisplay);
    }

    /* We can't use some special chars in variable names, such as '.' */
    public function escapeChars($sIn)
    {
        return str_replace($this->aEscapeChars, $this->aEscapeCharsWith, $sIn);
    }

    public function unescapeChars($sIn)
    {
        return str_replace($this->aEscapeWith, $this->aEscape, $sIn);
    }

    public function getUrlElement($iId, Filter $oFilter)
    {
        $sColumn = $this->escapeChars($oFilter->getColumn());
        $oColumn = $this->aFilterInfo[$sColumn];

        $sId = $iId;

        $shEditor = "&i{$sColumn}Op$sId={$oFilter->getOperatorId()}";
        $shEditor .= "&s{$sColumn}Data$sId={$oFilter->getData()}";

        return $shEditor;
    }

    public function getHiddenInputTag($iId, Filter $oFilter)
    {
        $sColumn = $this->escapeChars($oFilter->getColumn());
        $oColumn = $this->aFilterInfo[$sColumn];

        $sId = $iId;

        $shEditor = "<input type=\"hidden\" name=\"i{$sColumn}Op$sId\" value=\"{$oFilter->getOperatorId()}\">";
        $shEditor .= "<input type=\"hidden\" name=\"s{$sColumn}Data$sId\" value=\"{$oFilter->getData()}\" />";

        return $shEditor;
    }


    public function getItemEditor($iId, Filter $oFilter)
    {
        $sColumn = $this->escapeChars($oFilter->getColumn());
        $oColumn = $this->aFilterInfo[$oFilter->getColumn()];

        $sId = ($iId == -1) ? '' : $iId;
        $shEditor = $oColumn->getDisplayName().' ';

        $aTypes = $oColumn->getTypes();

        /* It doesn't make sense to show a dropdown menu of choices if there is only one
           If the filter is already active then there are more than one; one to remove */
        if($iId == -1 && sizeof($aTypes) == 1)
        {
            echo "<input type=\"hidden\" name=\"i{$sColumn}Op$sId\" value=\"{$aTypes[0]}\" />";

            /* Printing 'equal to' sounds weird if it is the only choice */
            if($aTypes[0] != FILTER_EQUALS)
                $shEditor .= $oColumn->getOpName($aTypes[0]);
        } else
        {
            $shEditor .= "<select name='i{$sColumn}Op$sId'>";

            if($iId == -1)
            {
                $sText = 'select';
                $sSel = " selected='selected'";
            } else
            {
                $sSel = '';
                $sText = 'remove';
            }

            $shEditor .= "<option value='0'$sSel>-- $sText --</option>";

            foreach($aTypes as $iType)
            {
                if($oFilter->getOperatorId() == $iType)
                    $sSel = " selected='selected'";
                else
                    $sSel = '';
                $shEditor .= "<option value='$iType'$sSel>".$oColumn->getOpName($iType).'</option><br />';
            }
            $shEditor .= '</select> ';
        }

        switch($oColumn->getValueType())
        {
            case FILTER_VALUES_NORMAL:
                $shEditor .= "<input type='text' value=\"{$oFilter->getData()}\" name='s{$sColumn}Data$sId' size='30' />";
            break;
            case FILTER_VALUES_ENUM:
                $shEditor .= $this->getEnumEditor($oColumn, $oFilter, $sId);
            break;
        }

        return $shEditor;
    }

    public function getEnumEditor($oColumn, $oFilter, $sId)
    {
        $sColumn = $this->escapeChars($oFilter->getColumn());
        $aOptions = $oColumn->getValueTypeData();
        $aOptionNames = $oColumn->getValueTypeDataDisplay();

        $sData = $oFilter->getData();

        $shEditor .= "<select name=\"s{$sColumn}Data$sId\">";

        if($sData)
            $shEditor .= "<option value=\"\">-- remove --</option>";
        else
            $shEditor .= "<option value=\"\">-- select --</option>";

        for($i = 0; $i < sizeof($aOptions); $i++)
        {
            $sOption = $aOptions[$i];
            $sSelected = '';
            if($sData == $sOption)
                $sSelected = ' selected="selected"';
            $shEditor .= "<option value=\"$sOption\"$sSelected>{$aOptionNames[$i]}</option>";
        }

        $shEditor .= "</select>";

        return $shEditor;
    }

    /* Get filter data formatted to fit in a URL */
    public function getUrlData()
    {
        $shEditor = '';
        $aCounts = array();

        foreach($this->oFilterSet->getFilters() as $oFilter)
        {
            $sColumn = $oFilter->getColumn();

            if(!array_key_exists($sColumn, $aCounts))
                $aCounts[$sColumn] = 0;

            $shEditor .= $this->getUrlElement($aCounts[$sColumn], $oFilter);

            $shEditor .= '<br />';

            $aCounts[$sColumn]++;
        }

        return $shEditor;
    }

    /* Get a list of hidden input tags to preserve form data */
    public function getHiddenFormData()
    {
        $shEditor = '';
        $aCounts = array();

        foreach($this->oFilterSet->getFilters() as $oFilter)
        {
            $sColumn = $oFilter->getColumn();

            if(!array_key_exists($sColumn, $aCounts))
                $aCounts[$sColumn] = 0;

            $shEditor .= $this->getHiddenInputTag($aCounts[$sColumn], $oFilter);

            $shEditor .= '<br />';

            $aCounts[$sColumn]++;
        }

        return $shEditor;
    }

    public function getEditor()
    {
        $shEditor = '';
        $aCounts = array();

        $shEditor .= '<b>Add new filter</b> <i>(You don&#8217;t have to fill out all rows.)</i><br />';
        foreach($this->aFilterInfo as $oOption)
        {
            $oDummyFilter = new Filter($oOption->getColumn(), 0, '');
            $aTypes = $oOption->getTypes();

              $shEditor .= $this->getItemEditor(-1, $oDummyFilter);
            $shEditor .= '<br />';
        }

        if(sizeof($this->oFilterSet->getFilters()))
             $shEditor .= '<br /><b>Active filters</b><br />';
        foreach($this->oFilterSet->getFilters() as $oFilter)
        {
            $sColumn = $oFilter->getColumn();

            if(!array_key_exists($sColumn, $aCounts))
                $aCounts[$sColumn] = 0;

            $shEditor .= $this->getItemEditor($aCounts[$sColumn], $oFilter);
            $shEditor .= '<br />';

            $aCounts[$sColumn]++;
        }

        return $shEditor;
    }

    public function getFilterInfo()
    {
        return $this->aFilterInfo;
    }

    /* Reads all input related to filters for the given table column */
    public function readInputForColumn($aClean, FilterInfo $oOption)
    {
        $aReturn = array();

        for($i = 0; array_key_exists('i'.$this->escapeChars($oOption->getColumn())."Op$i", $aClean); $i++)
        {
            $sColumn = $this->escapeChars($oOption->getColumn());
            $sData = query_escape_string($aClean["s{$sColumn}Data$i"]);
            $iOp = $aClean["i{$sColumn}Op$i"];

            if(!$iOp)
                continue;

            $oFilter = new Filter($oOption->getColumn(), $iOp, $sData);

            $aReturn[] = $oFilter;
        }

        if(array_key_exists('i'.$this->escapeChars($oOption->getColumn())."Op", $aClean))
        {
            $sColumn = $this->escapeChars($oOption->getColumn());
            $i = sizeof($aReturn);
            $sData = $aClean["s{$sColumn}Data"];
            $iOp = $aClean["i{$sColumn}Op"];

            if($iOp && $sData)
            {
                $oFilter = new Filter($oOption->getColumn(), $iOp, $sData);
                $aReturn[] = $oFilter;
            }
        }

        return $aReturn;
    }

    /* Reads an input array get enabled filters from form data.
       The given TableFilterSet defines available options */
    public function readInput($aClean)
    {
        foreach($this->getFilterInfo() as $oOption)
        {
            foreach($this->readInputForColumn($aClean, $oOption) as $oNewFilter)
                $this->AddFilterObject($oNewFilter);
        }
    }

    public function loadTable($sTableName)
    {
        $this->oFilterSet->loadTable($sTableName);
    }

    public function saveTable($sTableName)
    {
        $this->oFilterSet->saveTable($sTableName);
    }

    public function getFilterCount()
    {
        return $this->oFilterSet->getFilterCount();
    }

    public function getWhereClause()
    {
        return $this->oFilterSet->getWhereClause();
    }

    public function getTable($sTable, $iLimit = 0)
    {
        $hResult = $this->oFilterSet->getMatchedItems($sTable, $iLimit);

        if(!$hResult)
            return;

        echo 'Selected '.$this->oFilterSet->getMatchedItemsCount($sTable).' rows<br><br>';

        $oTable = new Table();

        while($aRow = mysql_fetch_row($hResult))
        {
            $oRow = new TableRow();

            foreach($aRow as $sCell)
            {
                $oRow->AddTextCell($sCell);
            }

            $oTable->AddRow($oRow);
        }

        return $oTable->getString();
    }
}

?>
