<?php

/**
 * apStatsGraphs for the OpenX ad server (Free Version).
 *
 * @author Matteo Beccati
 * @copyright 2009 AdserverPlugins.com
 * @license http://creativecommons.org/licenses/by-nd/3.0/
 *
 * $Id$
 */

// Prepare the OpenX environment via standard external OpenX scripts
require_once '../../../../init.php';
require_once '../../config.php';
require_once './lib/apGraph.php';

// Limit access to logged in users
OA_Permission::enforceAccount(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER, OA_ACCOUNT_TRAFFICKER);

// Custom no cache - OFC seems to have issues with the standard OpenX headers
MAX_header('Cache-Control: private, max-age=0, no-store');
MAX_header('Expires: '. date('r'));

$oGraph = AP_Graph::factory($_GET);

$oGraph->displayGraph();
