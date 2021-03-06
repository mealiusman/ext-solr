<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// Windows compatibility

if (!function_exists('strptime')) {
    require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('solr') . 'Resources/Private/Php/strptime/strptime.php');
}

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// registering Index Queue page indexer helpers

if (TYPO3_MODE == 'FE' && isset($_SERVER['HTTP_X_TX_SOLR_IQ'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']['ApacheSolrForTypo3\Solr\IndexQueue\PageIndexerRequestHandler'] = '&' . \ApacheSolrForTypo3\Solr\IndexQueue\PageIndexerRequestHandler::class . '->run';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['Indexer']['indexPageSubstitutePageDocument']['ApacheSolrForTypo3\Solr\AdditionalFieldsIndexer'] = \ApacheSolrForTypo3\Solr\AdditionalFieldsIndexer::class;

    ApacheSolrForTypo3\Solr\IndexQueue\FrontendHelper\Manager::registerFrontendHelper(
        'findUserGroups',
        \ApacheSolrForTypo3\Solr\IndexQueue\FrontendHelper\UserGroupDetector::class
    );

    ApacheSolrForTypo3\Solr\IndexQueue\FrontendHelper\Manager::registerFrontendHelper(
        'indexPage',
        \ApacheSolrForTypo3\Solr\IndexQueue\FrontendHelper\PageIndexer::class
    );
}

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
    'TYPO3.tx_solr.IndexInspector.Remote',
    \ApacheSolrForTypo3\Solr\Backend\IndexInspector\IndexInspectorRemoteController::class,
    'web_info',
    'user,group'
);

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// page module plugin settings summary

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$_EXTKEY . '_pi_results'][$_EXTKEY] = \ApacheSolrForTypo3\Solr\Controller\Backend\PageModuleSummary::class . '->getSummary';

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// register search components

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'access',
    \ApacheSolrForTypo3\Solr\Search\AccessComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'relevance',
    \ApacheSolrForTypo3\Solr\Search\RelevanceComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'sorting',
    \ApacheSolrForTypo3\Solr\Search\SortingComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'debug',
    \ApacheSolrForTypo3\Solr\Search\DebugComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'analysis',
    \ApacheSolrForTypo3\Solr\Search\AnalysisComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'highlighting',
    \ApacheSolrForTypo3\Solr\Search\HighlightingComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'spellchecking',
    \ApacheSolrForTypo3\Solr\Search\SpellcheckingComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'faceting',
    \ApacheSolrForTypo3\Solr\Search\FacetingComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'statistics',
    \ApacheSolrForTypo3\Solr\Search\StatisticsComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'lastSearches',
    \ApacheSolrForTypo3\Solr\Search\LastSearchesComponent::class
);

ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
    'elevation',
    \ApacheSolrForTypo3\Solr\Search\ElevationComponent::class
);

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// register plugin commands

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'results, frequentsearches',
    'frequentSearches',
    \ApacheSolrForTypo3\Solr\Plugin\Results\FrequentSearchesCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_NONE
);

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'search, results',
    'form',
    \ApacheSolrForTypo3\Solr\Plugin\Results\FormCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_NONE
);

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'results',
    'resultsPerPageSwitch',
    \ApacheSolrForTypo3\Solr\Plugin\Results\ResultsPerPageSwitchCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_SEARCHED
    + ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_RESULTS
);

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'search, results',
    'errors',
    \ApacheSolrForTypo3\Solr\Plugin\Results\ErrorsCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_NONE
);

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'results',
    'lastSearches',
    \ApacheSolrForTypo3\Solr\Plugin\Results\LastSearchesCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_NONE
);

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'results',
    'no_results',
    \ApacheSolrForTypo3\Solr\Plugin\Results\NoResultsCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_SEARCHED
    + ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_NO_RESULTS
);

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'results',
    'faceting',
    \ApacheSolrForTypo3\Solr\Plugin\Results\FacetingCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_SEARCHED
    + ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_RESULTS
    + ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_NO_RESULTS
);

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'results',
    'results',
    \ApacheSolrForTypo3\Solr\Plugin\Results\ResultsCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_SEARCHED
    + ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_RESULTS
);

ApacheSolrForTypo3\Solr\CommandResolver::registerPluginCommand(
    'results',
    'sorting',
    \ApacheSolrForTypo3\Solr\Plugin\Results\SortingCommand::class,
    ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_SEARCHED
    + ApacheSolrForTypo3\Solr\Plugin\PluginCommand::REQUIREMENT_HAS_RESULTS
);

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// registering facet types

ApacheSolrForTypo3\Solr\Facet\FacetRendererFactory::registerFacetType(
    'numericRange',
    \ApacheSolrForTypo3\Solr\Facet\NumericRangeFacetRenderer::class,
    \ApacheSolrForTypo3\Solr\Query\FilterEncoder\Range::class,
    \ApacheSolrForTypo3\Solr\Query\FilterEncoder\Range::class
);

ApacheSolrForTypo3\Solr\Facet\FacetRendererFactory::registerFacetType(
    'dateRange',
    \ApacheSolrForTypo3\Solr\Facet\DateRangeFacetRenderer::class,
    \ApacheSolrForTypo3\Solr\Query\FilterEncoder\DateRange::class,
    \ApacheSolrForTypo3\Solr\Query\FilterEncoder\DateRange::class
);

ApacheSolrForTypo3\Solr\Facet\FacetRendererFactory::registerFacetType(
    'hierarchy',
    \ApacheSolrForTypo3\Solr\Facet\HierarchicalFacetRenderer::class,
    \ApacheSolrForTypo3\Solr\Query\FilterEncoder\Hierarchy::class
);

ApacheSolrForTypo3\Solr\Facet\FacetRendererFactory::registerFacetType(
    'queryGroup',
    \ApacheSolrForTypo3\Solr\Facet\QueryGroupFacetRenderer::class,
    \ApacheSolrForTypo3\Solr\Query\FilterEncoder\QueryGroup::class,
    \ApacheSolrForTypo3\Solr\Query\FilterEncoder\QueryGroup::class
);

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// adding scheduler tasks

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['ApacheSolrForTypo3\Solr\Task\ReIndexTask'] = [
    'extension' => $_EXTKEY,
    'title' => 'LLL:EXT:solr/Resources/Private/Language/locallang.xlf:reindex_title',
    'description' => 'LLL:EXT:solr/Resources/Private/Language/locallang.xlf:reindex_description',
    'additionalFields' => \ApacheSolrForTypo3\Solr\Task\ReIndexTaskAdditionalFieldProvider::class
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['ApacheSolrForTypo3\Solr\Task\IndexQueueWorkerTask'] = [
    'extension' => $_EXTKEY,
    'title' => 'LLL:EXT:solr/Resources/Private/Language/locallang.xlf:indexqueueworker_title',
    'description' => 'LLL:EXT:solr/Resources/Private/Language/locallang.xlf:indexqueueworker_description',
    'additionalFields' => \ApacheSolrForTypo3\Solr\Task\IndexQueueWorkerTaskAdditionalFieldProvider::class
];

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// TODO move into pi_results, initializeSearch, add only when features are activated
$TYPO3_CONF_VARS['EXTCONF']['solr']['modifySearchForm']['keepParameters'] = \ApacheSolrForTypo3\Solr\Plugin\Results\ParameterKeepingFormModifier::class;
$TYPO3_CONF_VARS['EXTCONF']['solr']['modifySearchForm']['spellcheck'] = \ApacheSolrForTypo3\Solr\Plugin\Results\SpellCheckFormModifier::class;
$TYPO3_CONF_VARS['EXTCONF']['solr']['modifySearchForm']['suggest'] = \ApacheSolrForTypo3\Solr\Plugin\Results\SuggestFormModifier::class;

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// registering the eID scripts
// TODO move to suggest form modifier
$TYPO3_CONF_VARS['FE']['eID_include']['tx_solr_suggest'] = 'EXT:solr/Classes/Eid/Suggest.php';
$TYPO3_CONF_VARS['FE']['eID_include']['tx_solr_api'] = 'EXT:solr/Classes/Eid/Api.php';

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

$hasCompatibilityLayer = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('compatibility6');
if ($hasCompatibilityLayer) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'solr',
        'setup',
        'tt_content.search = COA
         tt_content.search {
           10 = < lib.stdheader
           20 >
           20 = < plugin.tx_solr_PiResults_Results
           30 >
        }',
        'defaultContentRendering'
    );
}

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// add custom Solr content objects

$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][ApacheSolrForTypo3\Solr\ContentObject\Multivalue::CONTENT_OBJECT_NAME] = [
    ApacheSolrForTypo3\Solr\ContentObject\Multivalue::CONTENT_OBJECT_NAME,
    \ApacheSolrForTypo3\Solr\ContentObject\Multivalue::class
];

$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][ApacheSolrForTypo3\Solr\ContentObject\Content::CONTENT_OBJECT_NAME] = [
    ApacheSolrForTypo3\Solr\ContentObject\Content::CONTENT_OBJECT_NAME,
    \ApacheSolrForTypo3\Solr\ContentObject\Content::class
];

$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][ApacheSolrForTypo3\Solr\ContentObject\Relation::CONTENT_OBJECT_NAME] = [
    ApacheSolrForTypo3\Solr\ContentObject\Relation::CONTENT_OBJECT_NAME,
    \ApacheSolrForTypo3\Solr\ContentObject\Relation::class
];

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

// Register cache for frequent searches

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr'] = [];
}
// Caching framework solr
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration'] = [];
}

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_siteroots'] = [];
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration']['backend'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration']['backend'] = \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_siteroots']['backend'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_siteroots']['backend'] = \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration']['options'])) {
    // default life time one day
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration']['options'] = ['defaultLifetime' => 60 * 60 * 24];
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_siteroots']['options'])) {
    // default life time one day
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_siteroots']['options'] = ['defaultLifetime' => 60 * 60 * 24];
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration']['groups'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_configuration']['groups'] = ['all'];
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_siteroots']['groups'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_solr_siteroots']['groups'] = ['all'];
}

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \ApacheSolrForTypo3\Solr\Command\SolrCommandController::class;

# ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultClassName '])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultClassName '] = \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResult::class;
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultSetClassName '])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultSetClassName '] = \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet::class;
}

// Log devLog entries to console and files
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog']['extsolr-debug-writer'] = \ApacheSolrForTypo3\Solr\System\Logging\DevLogDebugWriter::class . '->log';
