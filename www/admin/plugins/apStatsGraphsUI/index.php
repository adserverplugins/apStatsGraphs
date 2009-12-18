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

// Limit access to logged in users
OA_Permission::enforceAccount(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER, OA_ACCOUNT_TRAFFICKER);

// No cache
MAX_commonSetNoCacheHeaders();

$oGraph = AP_Graph::factory($_GET);

// Display the OpenX page header
phpAds_PageHeader($oGraph->getMenuIndex(), '', '../../');

?>

<div style="width: 800px">

    <?php open_flash_chart_object('800', 350, $oGraph->getUrl()); ?>

    <div style="text-align: center; margin: 8px 0; padding: 4px 32px; border-top: 1px solid #cccccc; border-bottom: 1px solid #cccccc;">
        <?php echo getButtons($oGraph->getLinks()); ?>
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


function getButtons($aLinks) {
    $aButtons = array(
        'prev' => array('< Prev', 'left'),
        'up'   => array('Up', 'none'),
        'next' => array('Next >', 'right'),
    );
    $str = '';
    foreach ($aButtons as $type => $aData) {
        list($text, $float) = $aData;
        $str .= '<button ';
        if (!empty($aLinks[$type])) {
            $str .= 'onclick="location.href=\''.$aLinks['up'].'\'" ';
        } else {
            $str .= 'disabled="disabled" ';
        }
        $str .= 'style="width: 6em; float: '.$float.'">'.htmlspecialchars($text).'</button>';
    }
    return $str;
}
