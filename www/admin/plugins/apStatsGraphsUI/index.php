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

// Display the OpenX page header
phpAds_PageHeader("apStatsGraphsUI", '', '../../');

$oGraph = AP_Graph::factory($_GET);

?>

<div style="width: 800px">

    <?php open_flash_chart_object('800', 350, $oGraph->getUrl()); ?>

    <div style="text-align: center; margin: 8px 0; padding: 4px 32px; border-top: 1px solid #cccccc; border-bottom: 1px solid #cccccc;">
        <?php
        $aLinks = $oGraph->getLinks();

        if (!empty($aLinks['prev']))  {
            echo '<a href="'.$aLinks['prev'].'" style="float: left">&lt; Prev</a>';
        }
        if (!empty($aLinks['next']))  {
            echo '<a href="'.$aLinks['next'].'" style="float: right">Next &gt;</a>';
        }
        if (!empty($aLinks['up']))  {
            echo '<a href="'.$aLinks['up'].'">Up</a>';
        } else {
            echo "&nbsp;";
        }

        ?>
    </div>
    <div style="text-align: right; margin: 4px">
        This free plugin was created by the <a href="http://www.adserverplugins.com" target="_blank">AdServerPlugins.com</a> team
    </div>

</div>

<script type="text/javascript">
<?php echo $oGraph->getJs(); ?>
</script>

<?php

// Display the OpenX page footer
phpAds_PageFooter();
