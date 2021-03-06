<?php

namespace ApacheSolrForTypo3\Solr\Domain\Index\Queue\RecordMonitor\Helper;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 - Thomas Hohn <tho@systime.dk>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Extracted logic from the AbstractDataHandlerListener in order to
 * handle ConfigurationAwareRecords
 *
 * @author Thomas Hohn Hund <tho@systime.dk>
 */
class ConfigurationAwareRecordService
{
    /**
     * Retrieves the name of the Index Queue Configuration for a record.
     *
     * @param string $recordTable Table to read from
     * @param int $recordUid Id of the record
     * @param TypoScriptConfiguration $solrConfiguration
     * @return string Name of indexing configuration
     */
    public function getIndexingConfigurationName($recordTable, $recordUid, TypoScriptConfiguration $solrConfiguration)
    {
        $name = $recordTable;
        $indexingConfigurations = $solrConfiguration->getEnabledIndexQueueConfigurationNames();
        foreach ($indexingConfigurations as $indexingConfigurationName) {
            if (!$solrConfiguration->getIndexQueueConfigurationIsEnabled($indexingConfigurationName)) {
                // ignore disabled indexing configurations
                continue;
            }

            $record = $this->getRecordIfIndexConfigurationIsValid($recordTable, $recordUid,
                $indexingConfigurationName, $solrConfiguration);
            if (!empty($record)) {
                $name = $indexingConfigurationName;
                // FIXME currently returns after the first configuration match
                break;
            }
        }

        return $name;
    }

    /**
     * Retrieves a record, taking into account the additionalWhereClauses of the
     * Indexing Queue configurations.
     *
     * @param string $recordTable Table to read from
     * @param int $recordUid Id of the record
     * @param TypoScriptConfiguration $solrConfiguration
     * @return array Record if found, otherwise empty array
     */
    public function getRecord($recordTable, $recordUid, TypoScriptConfiguration $solrConfiguration)
    {
        $record = [];
        $indexingConfigurations = $solrConfiguration->getEnabledIndexQueueConfigurationNames();
        foreach ($indexingConfigurations as $indexingConfigurationName) {
            $record = $this->getRecordIfIndexConfigurationIsValid($recordTable, $recordUid,
                $indexingConfigurationName, $solrConfiguration);
            if (!empty($record)) {
                // if we found a record which matches the conditions, we can continue
                break;
            }
        }
        return $record;
    }

    /**
     * This method return the record array if the table is valid for this indexingConfiguration.
     * Otherwise an empty array will be returned.
     *
     * @param string $recordTable
     * @param integer $recordUid
     * @param string $indexingConfigurationName
     * @param TypoScriptConfiguration $solrConfiguration
     * @return array
     */
    protected function getRecordIfIndexConfigurationIsValid($recordTable, $recordUid, $indexingConfigurationName, TypoScriptConfiguration $solrConfiguration)
    {
        if (!$this->isValidTableForIndexConfigurationName($recordTable, $indexingConfigurationName, $solrConfiguration)) {
            return [];
        }

        $recordWhereClause = $solrConfiguration->getIndexQueueAdditionalWhereClauseByConfigurationName($indexingConfigurationName);

        if ($recordTable === 'pages_language_overlay') {
            return $this->getPageOverlayRecordIfParentIsAccessible($recordUid, $recordWhereClause);
        }

        return (array)BackendUtility::getRecord($recordTable, $recordUid, '*', $recordWhereClause);
    }

    /**
     * This method is used to check if a table is an allowed table for an index configuration.
     *
     * @param string $recordTable
     * @param string $indexingConfigurationName
     * @param TypoScriptConfiguration $solrConfiguration
     * @return boolean
     */
    protected function isValidTableForIndexConfigurationName($recordTable, $indexingConfigurationName, TypoScriptConfiguration $solrConfiguration)
    {
        $tableToIndex = $solrConfiguration->getIndexQueueTableNameOrFallbackToConfigurationName($indexingConfigurationName);

        $isMatchingTable = ($tableToIndex === $recordTable);
        $isPagesPassedAndOverlayRequested = $tableToIndex === 'pages' && $recordTable === 'pages_language_overlay';

        if ($isMatchingTable || $isPagesPassedAndOverlayRequested) {
            return true;
        }

        return false;
    }

    /**
     * This method retrieves the pages_language_overlay record when the parent record is accessible
     * through the recordWhereClause
     *
     * @param int $recordUid
     * @param string $parentWhereClause
     * @return array
     */
    protected function getPageOverlayRecordIfParentIsAccessible($recordUid, $parentWhereClause)
    {
        $overlayRecord = (array)BackendUtility::getRecord('pages_language_overlay', $recordUid, '*');
        $pageRecord = (array)BackendUtility::getRecord('pages', $overlayRecord['pid'], '*', $parentWhereClause);

        if (empty($pageRecord)) {
            return [];
        }

        return $overlayRecord;
    }
}
