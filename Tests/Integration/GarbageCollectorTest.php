<?php
namespace ApacheSolrForTypo3\Solr\Tests\Integration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2015 Timo Schmidt <timo.schmidt@dkd.de>
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

use ApacheSolrForTypo3\Solr\GarbageCollector;
use ApacheSolrForTypo3\Solr\IndexQueue\RecordMonitor;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use ApacheSolrForTypo3\Solr\Site;
use GeorgRinger\News\Hooks\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This testcase is used to check if the GarbageCollector can delete garbage from the
 * solr server as expected
 *
 * @author Timo Schmidt
 */
class GarbageCollectorTest extends IntegrationTest
{

    /**
     * @var RecordMonitor
     */
    protected $recordMonitor;

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @var Queue
     */
    protected $indexQueue;

    /**
     * @var GarbageCollector
     */
    protected $garbageCollector;

    public function setUp()
    {
        parent::setUp();
        $this->recordMonitor = GeneralUtility::makeInstance(RecordMonitor::class);
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $this->indexQueue = GeneralUtility::makeInstance(Queue::class);
        $this->garbageCollector = GeneralUtility::makeInstance(GarbageCollector::class);
    }

    /**
     * @return void
     */
    protected function assertEmptyIndexQueue()
    {
        $this->assertEquals(0, $this->indexQueue->getAllItemsCount(), 'Index queue is not empty as expected');
    }

    /**
     * @return void
     */
    protected function assertNotEmptyIndexQueue()
    {
        $this->assertGreaterThan(0, $this->indexQueue->getAllItemsCount(),
            'Index queue is empty and was expected to be not empty.');
    }

    /**
     * @param $amount
     */
    protected function assertIndexQueryContainsItemAmount($amount)
    {
        $this->assertEquals($amount, $this->indexQueue->getAllItemsCount(),
            'Index queue is empty and was expected to contain ' . (int) $amount . ' items.');
    }

    /**
     * @test
     */
    public function canQueueAPageAndRemoveItWithTheGarbageCollector()
    {
        /** @var $database  \TYPO3\CMS\Core\Database\DatabaseConnection */
        $database = $GLOBALS['TYPO3_DB'];
        $database->debugOutput = true;
        $this->importDataSetFromFixture('can_queue_a_page_and_remove_it_with_the_garbage_collector.xml');

        // we expect that the index queue is empty before we start
        $this->assertEmptyIndexQueue();

        $dataHandler = $this->dataHandler;
        $this->recordMonitor->processDatamap_afterDatabaseOperations('update', 'pages', 1, [], $dataHandler);

        // we expect that one item is now in the solr server
        $this->assertIndexQueryContainsItemAmount(1);

        $this->garbageCollector->collectGarbage('pages', 1);

        // finally we expect that the index is empty again
        $this->assertEmptyIndexQueue();
    }

    /**
     * @test
     */
    public function canCollectGarbageFromSubPagesWhenPageIsSetToHiddenAndExtendToSubPagesIsSet()
    {
        /** @var $database  \TYPO3\CMS\Core\Database\DatabaseConnection */
        $database = $GLOBALS['TYPO3_DB'];
        $database->debugOutput = true;
        $this->importDataSetFromFixture('can_collect_garbage_from_subPages_when_page_is_set_to_hidden_and_extendToSubpages_is_set.xml');

        // we expect that the index queue is empty before we start
        $this->assertEmptyIndexQueue();

        $this->indexQueue->updateItem('pages', 1);
        $this->indexQueue->updateItem('pages', 10);
        $this->indexQueue->updateItem('pages', 100);

        // we expected that three pages are now in the index
        $this->assertIndexQueryContainsItemAmount(3);

        // simulate the database change and build a faked changeset
        $database->exec_UPDATEquery('pages', 'uid=1', ['hidden' => 1]);
        $changeSet = ['hidden' => 1];

        $dataHandler = $this->dataHandler;
        $this->garbageCollector->processDatamap_afterDatabaseOperations('update', 'pages', 1, $changeSet, $dataHandler);

        // finally we expect that the index is empty again because the root page with "extendToSubPages" has been set to
        // hidden = 1
        $this->assertEmptyIndexQueue();
    }

    /**
     * @test
     */
    public function canCollectGarbageFromSubPagesWhenPageIsSetToHiddenAndExtendToSubPagesIsSetForMultipleSubpages()
    {
        /** @var $database  \TYPO3\CMS\Core\Database\DatabaseConnection */
        $database = $GLOBALS['TYPO3_DB'];
        $database->debugOutput = true;
        $this->importDataSetFromFixture('can_collect_garbage_from_subPages_when_page_is_set_to_hidden_and_extendToSubpages_is_set_multiple_subpages.xml');

        // we expect that the index queue is empty before we start
        $this->assertEmptyIndexQueue();

        $this->indexQueue->updateItem('pages', 1);
        $this->indexQueue->updateItem('pages', 10);
        $this->indexQueue->updateItem('pages', 11);
        $this->indexQueue->updateItem('pages', 12);

        // we expected that three pages are now in the index
        $this->assertIndexQueryContainsItemAmount(4);

        // simulate the database change and build a faked changeset
        $database->exec_UPDATEquery('pages', 'uid=1', ['hidden' => 1]);
        $changeSet = ['hidden' => 1];

        $dataHandler = $this->dataHandler;
        $this->garbageCollector->processDatamap_afterDatabaseOperations('update', 'pages', 1, $changeSet, $dataHandler);

        // finally we expect that the index is empty again because the root page with "extendToSubPages" has been set to
        // hidden = 1
        $this->assertEmptyIndexQueue();
    }

    /**
     * @test
     */
    public function canRemoveDeletedContentElement()
    {
        $this->cleanUpSolrServerAndAssertEmpty();
        $this->importDataSetFromFixture('can_remove_content_element.xml');

        $this->indexPageIds([1]);

        // we index a page with two content elements and expect solr contains the content of both
        $this->waitToBeVisibleInSolr();

        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertContains('will be removed!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');

        // we delete the second content element
        $beUser = $this->fakeBEUser(1, 0);

        $cmd['tt_content'][88]['delete'] = 1;
        $this->dataHandler->start([], $cmd, $beUser);
        $this->dataHandler->stripslashes_values = 0;
        $this->dataHandler->process_cmdmap();
        $this->dataHandler->process_datamap();

        // after applying the commands solr should be empty (because the page was removed from solr and queued for indexing)
        $this->waitToBeVisibleInSolr();
        $this->assertSolrIsEmpty();

        // we expect the is one item in the indexQueue
        $this->assertIndexQueryContainsItemAmount(1);
        $items = $this->indexQueue->getItems('pages', 1);

        // we index this item
        $this->indexPageIds($items);
        $this->waitToBeVisibleInSolr();

        // now the content of the deletec content element should be gone
        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertNotContains('will be removed!', $solrContent, 'solr did not remove deleted content');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');
    }

    /**
     * @test
     */
    public function canRemoveHiddenContentElement()
    {
        $this->cleanUpSolrServerAndAssertEmpty();
        $this->importDataSetFromFixture('can_remove_content_element.xml');

        $this->indexPageIds([1]);

        // we index a page with two content elements and expect solr contains the content of both
        $this->waitToBeVisibleInSolr();

        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertContains('will be removed!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');

        // we hide the second content element
        $beUser = $this->fakeBEUser(1, 0);
        $data = [
            'tt_content' => [
                '88' => [
                    'hidden' => 1
                ]
            ]
        ];
        $this->dataHandler->start($data, [], $beUser);
        $this->dataHandler->stripslashes_values = 0;
        $this->dataHandler->process_cmdmap();
        $this->dataHandler->process_datamap();

        // after applying the commands solr should be empty (because the page was removed from solr and queued for indexing)
        $this->waitToBeVisibleInSolr();
        $this->assertSolrIsEmpty();

        // we expect the is one item in the indexQueue
        $this->assertIndexQueryContainsItemAmount(1);
        $items = $this->indexQueue->getItems('pages', 1);

        // we index this item
        $this->indexPageIds($items);
        $this->waitToBeVisibleInSolr();

        // now the content of the deletec content element should be gone
        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertNotContains('will be removed!', $solrContent, 'solr did not remove hidden content');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');
    }

    /**
     * @test
     */
    public function canRemoveContentElementWithEndTimeSetToPast()
    {
        $this->cleanUpSolrServerAndAssertEmpty();
        $this->importDataSetFromFixture('can_remove_content_element.xml');

        $this->indexPageIds([1]);

        // we index a page with two content elements and expect solr contains the content of both
        $this->waitToBeVisibleInSolr();

        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertContains('will be removed!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');

        // we hide the second content element
        $beUser = $this->fakeBEUser(1, 0);

        $timeStampInPast = time() - (60 * 60 * 24);
        $data = [
            'tt_content' => [
                '88' => [
                    'endtime' => $timeStampInPast
                ]
            ]
        ];
        $this->dataHandler->start($data, [], $beUser);
        $this->dataHandler->stripslashes_values = 0;
        $this->dataHandler->process_cmdmap();
        $this->dataHandler->process_datamap();
        $this->dataHandler->clear_cacheCmd('all');

        // after applying the commands solr should be empty (because the page was removed from solr and queued for indexing)
        $this->waitToBeVisibleInSolr();
        $this->assertSolrIsEmpty();

        // we expect the is one item in the indexQueue
        $this->assertIndexQueryContainsItemAmount(1);
        $items = $this->indexQueue->getItems('pages', 1);

        // we index this item
        $this->indexPageIds($items);
        $this->waitToBeVisibleInSolr();

        // now the content of the deletec content element should be gone
        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertNotContains('will be removed!', $solrContent, 'solr did not remove content hidden by endtime in past');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');
    }

    /**
     * @test
     */
    public function canRemoveContentElementWithStartDateSetToFuture()
    {
        $this->cleanUpSolrServerAndAssertEmpty();
        $this->importDataSetFromFixture('can_remove_content_element.xml');

        $this->indexPageIds([1]);

        // we index a page with two content elements and expect solr contains the content of both
        $this->waitToBeVisibleInSolr();

        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertContains('will be removed!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');

        // we hide the second content element
        $beUser = $this->fakeBEUser(1, 0);

        $timestampInFuture = time() +  (60 * 60 * 24);
        $data = [
            'tt_content' => [
                '88' => [
                    'starttime' => $timestampInFuture
                ]
            ]
        ];
        $this->dataHandler->start($data, [], $beUser);
        $this->dataHandler->stripslashes_values = 0;
        $this->dataHandler->process_cmdmap();
        $this->dataHandler->process_datamap();
        $this->dataHandler->clear_cacheCmd('all');

        // after applying the commands solr should be empty (because the page was removed from solr and queued for indexing)
        $this->waitToBeVisibleInSolr();
        $this->assertSolrIsEmpty();

        // we expect the is one item in the indexQueue
        $this->assertIndexQueryContainsItemAmount(1);
        $items = $this->indexQueue->getItems('pages', 1);

        // we index this item
        $this->indexPageIds($items);
        $this->waitToBeVisibleInSolr();

        // now the content of the deletec content element should be gone
        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertNotContains('will be removed!', $solrContent, 'solr did not remove content hidden by starttime in future');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');
    }


    /**
     * @test
     */
    public function canRemovePageWhenPageIsHidden()
    {
        $this->cleanUpSolrServerAndAssertEmpty();
        $this->importDataSetFromFixture('can_remove_page.xml');

        $this->indexPageIds([1,2]);

        // we index two pages and check that both are visible
        $this->waitToBeVisibleInSolr();

        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertContains('will be removed!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('"numFound":2', $solrContent, 'Expected to have two documents in the index');

        // we hide the seconde page
        $beUser = $this->fakeBEUser(1, 0);

        $data = [
            'pages' => [
                '2' => [
                    'hidden' => 1
                ]
            ]
        ];
        $this->dataHandler->start($data, [], $beUser);
        $this->dataHandler->stripslashes_values = 0;
        $this->dataHandler->process_cmdmap();
        $this->dataHandler->process_datamap();
        $this->dataHandler->clear_cacheCmd('all');

        $this->waitToBeVisibleInSolr();
        $this->assertIndexQueryContainsItemAmount(1);

        // we reindex all queue items
        $items = $this->indexQueue->getItemsToIndex(Site::getFirstAvailableSite());
        $pages = [];
        foreach($items as $item) {
            $pages[] = $item->getRecordUid();
        }
        $this->indexPageIds($pages);
        $this->waitToBeVisibleInSolr();

        // now only one document should be left with the content of the first content element
        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertNotContains('will be removed!', $solrContent, 'solr did not remove content from hidden page');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('"numFound":1', $solrContent, 'Expected to have two documents in the index');
    }

    /**
     * @test
     */
    public function canRemovePageWhenPageIsDeleted()
    {
        $this->cleanUpSolrServerAndAssertEmpty();
        $this->importDataSetFromFixture('can_remove_page.xml');

        $this->indexPageIds([1,2]);

        // we index two pages and check that both are visible
        $this->waitToBeVisibleInSolr();

        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertContains('will be removed!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('"numFound":2', $solrContent, 'Expected to have two documents in the index');

        // we hide the seconde page
        $beUser = $this->fakeBEUser(1, 0);

        $cmd['pages'][2]['delete'] = 1;
        $this->dataHandler->start([], $cmd, $beUser);
        $this->dataHandler->stripslashes_values = 0;
        $this->dataHandler->process_cmdmap();
        $this->dataHandler->process_datamap();
        $this->dataHandler->clear_cacheCmd('all');

        $this->waitToBeVisibleInSolr();
        $this->assertIndexQueryContainsItemAmount(1);

        // we reindex all queue items
        $items = $this->indexQueue->getItemsToIndex(Site::getFirstAvailableSite());
        $pages = [];
        foreach($items as $item) {
            $pages[] = $item->getRecordUid();
        }
        $this->indexPageIds($pages);
        $this->waitToBeVisibleInSolr();

        // now only one document should be left with the content of the first content element
        $solrContent = file_get_contents('http://localhost:8999/solr/core_en/select?q=*:*');
        $this->assertNotContains('will be removed!', $solrContent, 'solr did not remove content from deleted page');
        $this->assertContains('will stay!', $solrContent, 'solr did not contain rendered page content');
        $this->assertContains('"numFound":1', $solrContent, 'Expected to have two documents in the index');
    }
}
