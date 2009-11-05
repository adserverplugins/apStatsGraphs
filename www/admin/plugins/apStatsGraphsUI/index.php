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

// Include other required external OpenX libraries
require_once MAX_PATH . '/lib/max/Admin/UI/Field/DaySpanField.php';

// Limit access to Admin and Manager accounts
OA_Permission::enforceAccount(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER);

require_once MAX_PATH . '/lib/OA/Admin/DaySpan.php';
require_once MAX_PATH . '/lib/OA/Admin/Statistics/Factory.php';
require_once MAX_PATH . '/lib/pear/Date.php';

// No cache
MAX_commonSetNoCacheHeaders();

$oStart = new Date();
$oEnd = new Date();
$oStart->setYear($oStart->getYear() - 1);

$_REQUEST = array(
    'period_preset'  => 'specific',
    'period_start'   => $oStart->format('%Y-%m-%d'),
    'period_end'     => $oEnd->format('%Y-%m-%d'),
    'listorder'      => 'day',
    'orderdirection' => 'up',
);

// Prepare the parameters for display or export to XLS
$aParams = array(
    'skipFormatting' => true,
    'disablePager'   => true
);

foreach ($GLOBALS['_MAX']['PREF'] as $k => &$v) {
    if (preg_match('/^ui_column_/', $k)) {
        if (!preg_match('/(_rank|_label)$/', $k)) {
            $v = false;
        }
    }
}

$GLOBALS['_MAX']['PREF']['ui_column_impressions'] = true;
$GLOBALS['_MAX']['PREF']['ui_column_impressions_rank'] = 1;
$GLOBALS['_MAX']['PREF']['ui_column_clicks'] = true;
$GLOBALS['_MAX']['PREF']['ui_column_clicks_rank'] = 2;

// Prepare the stats controller, and populate with the stats
$oStatsController = OA_Admin_Statistics_Factory::getController('global-history', $aParams);
if (PEAR::isError($oStatsController)) {
    phpAds_Die('Error occured', htmlspecialchars($oStatsController->getMessage()));
}
$oStatsController->start();


// Display the OpenX page header
phpAds_PageHeader("apStatsGraphsUI", '', '../../');

?>

<div style="width: auto; height: 350px" id="chart_div">
    Please wait... chart is loading
</div>

<div style="text-align: right; margin: 4px 16px">
    This free plugin was created by the <a href="http://www.adserverplugins.com" target="_blank">AdServerPlugins.com</a> team
</div>

<?php

$aData = $oStatsController->exportArray();

// Load the Google Visualization API library & display the graph
?>
<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
    google.load('visualization', '1', {'packages':['table', 'annotatedtimeline']});
    google.setOnLoadCallback(drawChart);
    function drawChart()
    {
        var data = new google.visualization.DataTable();
        data.addColumn('date', '<?php echo $aData['headers'][0]; ?>');
        data.addColumn('number', '<?php echo $aData['headers'][1] ?>');
        data.addColumn('number', '<?php echo $aData['headers'][2] ?>');
        data.addRows(<?php echo count($aData['data']); ?>);
<?php
    $counter = 0;
    foreach ($aData['data'] as $row) {
        list($d, $m, $y) = array_map('intval', explode('-', $row[0]));
        $m--; // JS months are 0-indexed
        echo "        data.setValue($counter, 0, new Date({$y}, {$m}, {$d}));\n";
        echo "        data.setValue($counter, 1, {$row[1]});\n";
        echo "        data.setValue($counter, 2, {$row[2]});\n";
        $counter++;
    }

?>
        var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('chart_div'));
        chart.draw(data, {
            'scaleColumns': [0, 1],
            'scaleType': 'allfixed',
            'thickness': 3,
            'colors': ['#0066ff', '#009900'],
            'wmode': 'opaque'
        });
    }
</script>

<?php

// Display the OpenX page footer
phpAds_PageFooter();
