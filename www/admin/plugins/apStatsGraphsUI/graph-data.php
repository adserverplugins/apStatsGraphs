<?php

/**
 * apStatsGraphs for the OpenX ad server (Free Version).
 *
 * @author Matteo Beccati
 * @copyright 2009 AdserverPlugins.com
 * @license http://creativecommons.org/licenses/by-nd/3.0/
 */

// Prepare the OpenX environment via standard external OpenX scripts
require_once '../../../../init.php';
require_once '../../config.php';
require_once './lib/apGraph.php';

// Limit access to Admin and Manager accounts
OA_Permission::enforceAccount(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER);

// No cache
MAX_commonSetNoCacheHeaders();

$oGraph = AP_Graph::factory($_GET);

$oGraph->displayGraph();
