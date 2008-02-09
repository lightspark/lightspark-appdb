<?php

/**
 * User interface for the SQL filter classes
 *
 * Copyright 2008 BitSplash Software LLC
 * Copyright 2008 Alexander N. Sørnes <alex@thehandofagony.com>
 *
*/

require_once('db_filter.php');

/* Info describing an available filter: what column it applies to,
    and what comparison options are available */
class FilterInfo
{
    private $sColumn;
    private $sDisplayName;
    private $aTypes; // Available filters for this column

    public function FilterInfo($sColumn, $sDisplayName, $aTypes)
    {
        $this->sColumn = $sColumn;
        $this->sDisplayName = $sDisplayName;
        $this->aTypes = $aTypes;
    }

    public function getColumn()
    {
        return $this->sColumn;
    }

    public function getDisplayName()
    {
        return $this->sDisplayName;
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

    public function FilterInterface($sTableName = '')
    {
        $this->aFilterInfo = array();
        $this->oFilterSet = new FilterSet(mysql_real_escape_string($sTableName));
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
    public function AddFilterInfo($sColumn, $sDisplayName, $aTypes)
    {
        $this->aFilterInfo[$sColumn] = new FilterInfo($sColumn, $sDisplayName, $aTypes);
    }

    public function getItemEditor($iId, Filter $oFilter)
    {
        $sColumn = $oFilter->getColumn();
        $oColumn = $this->aFilterInfo[$sColumn];

        $sId = ($iId == -1) ? '' : $iId;
        $shEditor = $oColumn->getDisplayName();

        $shEditor .= " <select name='i{$sColumn}Op$sId'>";

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

        foreach($oColumn->getTypes() as $iType)
        {
            if($oFilter->getOperatorId() == $iType)
                $sSel = " selected='selected'";
            else
                $sSel = '';
            $shEditor .= "<option value='$iType'$sSel>".$oColumn->getOpName($iType).'</option><br />';
        }

        $shEditor .= '</select> ';

        $shEditor .= "<input type='text' value=\"{$oFilter->getData()}\" name='s{$sColumn}Data$sId' size='30' />";

        return $shEditor;
    }

    public function getEditor()
    {
        $shEditor = '';
        $aCounts = array();

        $shEditor .= 'Add new filter<br />';
        foreach($this->aFilterInfo as $oOption)
        {
            $oDummyFilter = new Filter($oOption->getColumn(), 0, '');
            $shEditor .= $this->getItemEditor(-1, $oDummyFilter);
            $shEditor .= '<br />';
        }

        if(sizeof($this->oFilterSet->getFilters()))
             $shEditor .= '<br />Active filters<br />';
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

        for($i = 0; array_key_exists('i'.$oOption->getColumn()."Op$i", $aClean); $i++)
        {
            $sData = mysql_real_escape_string($aClean["s{$oOption->getColumn()}Data$i"]);
            $iOp = $aClean["i{$oOption->getColumn()}Op$i"];

            if(!$iOp)
                continue;

            $oFilter = new Filter($oOption->getColumn(), $iOp, $sData);
            $aReturn[] = $oFilter;
        }

        if(array_key_exists('i'.$oOption->getColumn()."Op", $aClean))
        {
            $i = sizeof($aReturn);
            $sData = $aClean["s{$oOption->getColumn()}Data"];
            $iOp = $aClean["i{$oOption->getColumn()}Op"];

            if($iOp)
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
        $this->oFilterSet->loadTable(mysql_real_escape_string($sTableName));
    }

    public function saveTable($sTableName)
    {
        $this->oFilterSet->saveTable(mysql_real_escape_string($sTableName));
    }

    public function getFilterCount()
    {
        return $this->oFilterSet->getFilterCount();
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
