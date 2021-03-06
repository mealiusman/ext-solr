<?php

namespace ApacheSolrForTypo3\Solr\Domain\Index\Queue\RecordMonitor\Helper;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017- Timo Hund <timo.hund@dkd.de>
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

use ApacheSolrForTypo3\Solr\Site;
use ApacheSolrForTypo3\Solr\System\Cache\TwoLevelCache;
use ApacheSolrForTypo3\Solr\Util;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * RootPageResolver.
 *
 * Responsibility: The RootPageResolver is responsible to determine all relevant site root page id's
 * for a certain records, by table and uid.
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class RootPageResolver implements SingletonInterface
{

    /**
     * @var ConfigurationAwareRecordService
     */
    protected $recordService;

    /**
     * @var TwoLevelCache
     */
    protected $siteRootsCache;

    /**
     * RootPageResolver constructor.
     * @param ConfigurationAwareRecordService|null $recordService
     * @param TwoLevelCache|null $twoLevelCache
     */
    public function __construct(ConfigurationAwareRecordService $recordService = null, TwoLevelCache $twoLevelCache = null)
    {
        $this->recordService = isset($recordService) ? $recordService : GeneralUtility::makeInstance(ConfigurationAwareRecordService::class);
        $this->siteRootsCache = isset($twoLevelCache) ? $twoLevelCache : GeneralUtility::makeInstance(TwoLevelCache::class, 'tx_solr_siteroots');
    }

    /**
     * This method determines the responsible site roots for a record by getting the rootPage of the record and checking
     * if the pid is references in another site with additionalPageIds and returning those rootPageIds as well.
     * The result is cached by the caching framework.
     *
     * @param string $table
     * @param int $uid
     * @return array
     */
    public function getResponsibleRootPageIds($table, $uid)
    {
        $cacheKey = 'rootpageids-' . $table . '-'.$uid;
        $cacheResult = $this->siteRootsCache->get($cacheKey);
        if ($cacheResult !== false) {
            return $cacheResult;
        }

        $methodResult = $this->buildResponsibleRootPageIds($table, $uid);
        $this->siteRootsCache->set($cacheKey, $methodResult);
        return $methodResult;
    }


    /**
     * This method determines the responsible site roots for a record by getting the rootPage of the record and checking
     * if the pid is references in another site with additionalPageIds and returning those rootPageIds as well.
     *
     * @param string $table
     * @param integer $uid
     * @return array
     */
    protected function buildResponsibleRootPageIds($table, $uid)
    {
        $rootPages = [];
        $rootPageId = $this->getRootPageIdByTableAndUid($table, $uid);
        if ($this->getIsRootPageId($rootPageId)) {
            $rootPages[] = $rootPageId;
        }

        $alternativeSiteRoots = $this->getAlternativeSiteRootPagesIds($table, $uid, $rootPageId);
        $rootPages = array_merge($rootPages, $alternativeSiteRoots);
        return $rootPages;
    }

    /**
     * This method checks if the record is a pages record or another one and determines the rootPageId from the records
     * rootline.
     *
     * @param string $table
     * @param int $uid
     * @return int
     */
    protected function getRootPageIdByTableAndUid($table, $uid)
    {
        if ($table === 'pages') {
            $rootPageId = Util::getRootPageId($uid);
            return $rootPageId;
        } else {
            $record = BackendUtility::getRecord($table, $uid);
            $rootPageId = Util::getRootPageId($record['pid'], true);
            return $rootPageId;
        }
    }

    /**
     * Checks if the passed pageId is a root page.
     *
     * @param integer $pageId
     * @return bool
     */
    protected function getIsRootPageId($pageId)
    {
        return Util::isRootPage($pageId);
    }

    /**
     * When no root page can be determined we check if the pageIdOf the record is configured as additionalPageId in the index
     * configuration of another site, if so we return the rootPageId of this site.
     * The result is cached by the caching framework.
     *
     * @param string $table
     * @param int $uid
     * @param int $recordPageId
     * @return array
     */
    public function getAlternativeSiteRootPagesIds($table, $uid, $recordPageId)
    {
        $siteRootsByObservedPageIds = $this->getSiteRootsByObservedPageIds($table, $uid);
        if (!isset($siteRootsByObservedPageIds[$recordPageId])) {
            return [];
        }

        return $siteRootsByObservedPageIds[$recordPageId];
    }

    /**
     * Retrieves an optimized array structure we the monitored pageId as key and the relevant site rootIds as value.
     *
     * @param string $table
     * @param integer $uid
     * @return array
     */
    protected function getSiteRootsByObservedPageIds($table, $uid)
    {
        $cacheKey = 'alternativesiteroots-' . $table . '-' . $uid;
        $cacheResult = $this->siteRootsCache->get($cacheKey);
        if ($cacheResult !== false) {
            return $cacheResult;
        }

        $methodResult = $this->buildSiteRootsByObservedPageIds($table, $uid);
        $this->siteRootsCache->set($cacheKey, $methodResult);
        return $methodResult;
    }

    /**
     * This methods build an array with observer page id as key and rootPageIds as values to determine which root pages
     * are responsible for this record by referencing the pageId in additionalPageIds configuration.
     *
     * @param string $table
     * @param integer $uid
     * @return array
     */
    protected function buildSiteRootsByObservedPageIds($table, $uid)
    {
        $siteRootByObservedPageIds = [];
        $allSites = Site::getAvailableSites();
        foreach ($allSites as $site) {
            $solrConfiguration = $site->getSolrConfiguration();
            $indexingConfigurationName = $this->recordService->getIndexingConfigurationName($table, $uid, $solrConfiguration);
            $observedPageIdsOfSiteRoot = $solrConfiguration->getIndexQueueAdditionalPageIdsByConfigurationName($indexingConfigurationName);
            foreach ($observedPageIdsOfSiteRoot as $observedPageIdOfSiteRoot) {
                $siteRootByObservedPageIds[$observedPageIdOfSiteRoot][] = $site->getRootPageId();
            }
        }

        return $siteRootByObservedPageIds;
    }
}
