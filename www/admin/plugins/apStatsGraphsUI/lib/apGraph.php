<?php

require_once MAX_PATH . '/lib/max/Admin/UI/Field/DaySpanField.php';
require_once MAX_PATH . '/lib/OA/Admin/DaySpan.php';
require_once MAX_PATH . '/lib/OA/Admin/Statistics/Factory.php';
require_once MAX_PATH . '/lib/pear/Date.php';

require_once './lib/open-flash-chart-object.php';
require_once './lib/open-flash-chart.php';

class AP_Graph
{
    protected $breakDown;
    protected $oStart;
    protected $oEnd;

    protected $aData = array(
        0 => array(),
        1 => array(),
        2 => array(),
    );
    protected $aLabels = array(
        1 => '',
        2 => '',
    );

    public function __construct($oStart, $oEnd)
    {
        $oNow = new Date();
        
        $this->oStart    = $oNow->before($oStart) ? $oNow : $oStart;
        $this->oEnd      = $oNow->before($oEnd)   ? $oNow : $oEnd;
    }

    static public function factory($aGet)
    {
        if (isset($aGet['day']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $aGet['day'], $m)) {
            $breakDown = 'Hourly';
            $aParams = array($m[1], $m[2], $m[3]);
        } elseif (isset($aGet['month']) && preg_match('/^(\d{4})-(\d{2})$/D', $aGet['month'], $m)) {
            $breakDown = 'Daily';
            $aParams = array($m[1], $m[2]);
        } elseif (isset($aGet['year']) && preg_match('/^(\d{4})$/D', $aGet['year'], $m)) {
            $breakDown = 'Monthly';
            $aParams = array($m[1]);
        } else {
            $breakDown = 'Daily';
            $aParams = array(date('Y'), date('m'));
        }

        require_once "./{$breakDown}.php";
        return call_user_func_array(array('AP_Graph_'.$breakDown, 'factory'), $aParams);
    }

    public function getUrl()
    {
        return 'graph-data.php?';
    }

    protected function getController()
    {
        if (empty($this->breakDown)) {
            throw new Exception("Cannot use the base class directly");
        }

        $aBackup = array($_REQUEST, $GLOBALS['_MAX']['PREF'], $GLOBALS['date_format'], $GLOBALS['month_format']);

        $GLOBALS['date_format'] = '%Y-%m-%d';
        $GLOBALS['month_format'] = '%Y-%m';

        $_REQUEST = array(
            'period_preset'  => 'specific',
            'period_start'   => $this->oStart->format('%Y-%m-%d'),
            'period_end'     => $this->oEnd->format('%Y-%m-%d'),
            'statsBreakdown' => $this->breakDown,
            'listorder'      => $this->breakDown,
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
            throw new Exception($oStatsController->getMessage());
        }

        $oStatsController->start();

        list($_REQUEST, $GLOBALS['_MAX']['PREF'], $GLOBALS['date_format'], $GLOBALS['month_format']) = $aBackup;

        return $oStatsController;
    }

    public function getJs()
    {
        return '';
    }

    public function getLinks()
    {
        return array();
    }

    protected function padBefore($row)
    {
    }

    protected function padAfter($lastRow)
    {
    }

    protected function getSeries($idx, $colour)
    {
        $oSeries = new bar_glass();
        $oSeries->set_values($this->aData[$idx]);
        $oSeries->set_key($this->aLabels[$idx]);
        $oSeries->set_colour($colour);

        return $oSeries;
    }

    private function prepareData()
    {
        $oStatsController = $this->getController();
        $aData = $oStatsController->exportArray();

        $this->aLabels = $aData['headers'];

        if (count($aData['data'])) {
            $this->padBefore(current($aData['data']));
        }

        foreach ($aData['data'] as $row) {
            $this->aData[0][] = $row[0];

            for ($i = 1; $i <= 2; $i++) {
                $row[$i] = (int)$row[$i];
                $this->aData[$i][] = $row[1];
            }
        }

        $this->padAfter(end($aData['data']));
    }

    protected function getTitle()
    {
        return '';
    }

    private function setAxisRange($oY, $idx, $scale = 1)
    {
        $max = $oY->max;
        $min = $oY->min;
        foreach ($this->aData[$idx] as $v) {
            if ($v > $max) {
                $max = $v;
            }
            if ($v < $min) {
                $min = $v;
            }
        }

        $max = ceil($max / $scale);
        $i10 = pow(10, strlen($max) - 1);
        if ($i10 * 1.5 > $max) {
                $i10 /= 2;
        }
        $i = max(10, ceil($max / $i10) * $i10);

        $oY->set_range($min, $i, $i / 10);
    }

    private function getChart($aGraphs)
    {
        $this->prepareData();

        $oLabels = new x_axis_labels();
        $oLabels->set_labels($this->aData[0]);
        $oLabels->rotate(-45);

        $oX = new x_axis();
        $oX->set_labels($oLabels);
        $oX->set_colours('#000000', '#ffffff');

        $oChart = new open_flash_chart();
        $oChart->set_bg_colour('#FFFFFF');
        $oChart->set_title(new title($this->getTitle()));
        $oChart->set_x_axis($oX);

        $aY = array();

        foreach ($aGraphs as $k => $v) {
            $y = empty($v['y-right']) ? 0 : 1;

            if (!isset($aY[$v['y-axis']])) {
                $aY[$y] = new y_axis();
                $aY[$y]->set_colour($v['colour']);
                $method = $y ? 'set_y_axis_right' : 'set_y_axis';
                $oChart->$method($aY[$y]);
            }
            $this->setAxisRange($aY[$y], $k, empty($v['scale']) ? 1 : $v['scale']);
            
            $oSeries = $this->getSeries($k, $v['colour']);
            $oSeries->set_on_show($v['effect']);
            $oChart->add_element($oSeries);
        }

        return $oChart;
    }

    public function displayGraph()
    {
        $oChart = $this->getChart(array(
            1 => array(
                'colour'  => '#0000cc',
                'effect'  => new bar_on_show('grow-up', 0.5, 0.2),
            ),
            2 => array(
                'colour'  => '#0000cc',
                'effect'  => new bar_on_show('grow-up', 0.5, 0.2),
                'y-right' => true,
                'scale'   => 0.75,
            ),
        ));

        header('Content-Type: text/javascript');
        echo $oChart->toPrettyString();
    }

    protected function appendToUrl($url, $data)
    {
        return $url.(strpos($url, '?') !== false ? '&' : '?').$data;
    }
}
