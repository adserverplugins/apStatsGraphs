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
    protected $entity;
    protected $aEntityParams = array();

    protected $aData = array(
        0 => array(),
        1 => array(),
        2 => array(),
        3 => array(),
    );
    protected $aTooltips;
    protected $aLabels;

    public function __construct($oStart, $oEnd, $aEntityParams)
    {
        $oNow = new Date();

        $this->oStart    = $oNow->before($oStart) ? $oNow : $oStart;
        $this->oEnd      = $oNow->before($oEnd)   ? $oNow : $oEnd;

        $this->parseEntityParams($aEntityParams);
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

        $aParams[] = $aGet;

        require_once dirname(__FILE__)."/apGraph/{$breakDown}.php";
        return call_user_func_array(array('AP_Graph_'.$breakDown, 'factory'), $aParams);
    }

    private function parseEntityParams($aEntityParams)
    {
        $aMap = array(
            'advertiser' => array('clientid'),
            'campaign' => array('clientid', 'campaignid'),
            'banner' => array('clientid', 'campaignid', 'bannerid'),
            'affiliate' => array('affiliateid'),
            'zone' => array('affiliateid', 'zoneid'),
        );

        if (empty($aEntityParams['entity'])) {
            if (OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER)) {
                $aEntityParams['entity'] = 'advertiser';
            } elseif (OA_Permission::isAccount(OA_ACCOUNT_TRAFFICKER)) {
                $aEntityParams['entity'] = 'affiliate';
            } else {
                $aEntityParams['entity'] = 'global';
            }
        }

        if ($aEntityParams['entity'] != 'global') {
            $allowed = implode('|', array_keys($aMap));
            if (!preg_match('/^('.$allowed.')(?:-('.$allowed.'))?$/D', $aEntityParams['entity'], $aMatches)) {
                throw new exception("Unsupported entity breakdown");
            }
            array_shift($aMatches);
            $this->entity = join('-', $aMatches);
            foreach ($aMatches as $type) {
                foreach ($aMap[$type] as $inputVar) {
                    $this->aEntityParams[$inputVar] = !empty($aEntityParams[$inputVar]) ? (int)$aEntityParams[$inputVar] : 0;
                }
            }
        } else {
            $this->entity = 'global';
        }
    }

    public function getMenuIndex()
    {
        return 'apsg-'.$this->entity;
    }

    public function getUrl($graph = true)
    {
        $url = $graph ? 'graph-data' : 'index';
        $url .= '.php?entity='.$this->entity;
        foreach ($this->aEntityParams as $k => $v) {
            $url .= "&{$k}={$v}";
        }
        return $url;
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

        $_REQUEST += $this->aEntityParams;

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
        $GLOBALS['_MAX']['PREF']['ui_column_ctr'] = true;
        $GLOBALS['_MAX']['PREF']['ui_column_ctr_rank'] = 3;

        // Prepare the stats controller, and populate with the stats
        $oStatsController = OA_Admin_Statistics_Factory::getController($this->entity.'-history', $aParams);
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

    private function prepareData()
    {
        $oStatsController = $this->getController();
        $aData = $oStatsController->exportArray();

        $this->aLabels = $aData['headers'];

        for ($i = 1; $i < count($aData['headers']); $i++) {
            $this->aTooltips[$i] = "{$aData['headers'][$i]}: #val#";
            if ($aData['formats'][$i] == 'percent') {
                $this->aTooltips[$i] .= '%';
            }
        }

        if (count($aData['data'])) {
            $this->padBefore(current($aData['data']));
        }

        foreach ($aData['data'] as $row) {
            $this->aData[0][] = $row[0];

            for ($i = 1; $i < count($aData['headers']); $i++) {
                if (!empty($row[$i])) {
                    if ($aData['formats'][$i] == 'percent') {
                        $row[$i] = (float)round($row[$i] * 100, 2);
                    } else {
                        $row[$i] = (float)$row[$i];
                    }
                    $this->aData[$i][] = $row[$i];
                } else {
                    $this->aData[$i][] = null;
                }
            }
        }

        $this->padAfter(end($aData['data']));
    }

    protected function getTitle()
    {
        return '';
    }

    protected function getSeries($idx, $type, $colour)
    {
        $oSeries = new $type();
        $oSeries->set_values($this->aData[$idx]);
        $oSeries->set_key($this->aLabels[$idx], 10);
        $oSeries->set_colour($colour);

        if ($type == 'line') {
            $dot = new solid_dot();
            $dot->colour($colour)->tooltip($this->aTooltips[$idx]);
            $oSeries->set_default_dot_style($dot);
        } else {
            $oSeries->set_tooltip($this->aTooltips[$idx]);
        }

        return $oSeries;
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

    private function remapSeries($oY, $idx, $scale = 1)
    {
        $maxY = $oY->max;

        $max = 0;
        foreach ($this->aData[$idx] as $v) {
            $v = (float)$v;
            if ($v > $max) {
                $max = $v;
            }
        }

        foreach ($this->aData[$idx] as $k => $v) {
            if (isset($v)) {
                if (!$max) {
                    $this->aData[$idx][$k] = 0;
                } else {
                    $oValue = new solid_dot((float)$v / $max * $maxY * $scale);
                    $oValue->tooltip(str_replace('#val#', $v, $this->aTooltips[$idx]));
                    if (!empty($this->drillDown)) {
                        $oValue->on_click($this->drillDown);
                    }
                    $this->aData[$idx][$k] = $oValue;
                }
            }
        }
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

        $oTitle = new title($this->getTitle());
        $oTitle->set_style('font-size: 12px; font-weight: bold;');
        $oChart->set_title($oTitle);

        $oTooltip = new tooltip('#val#');
        $oTooltip->set_body_style('font-size: 10px');
        $oTooltip->set_stroke(1);
        $oTooltip->set_shadow(true);
        $oTooltip->set_background_colour('#ffffcc');
        $oTooltip->set_colour('#cccc99');
        $oChart->set_tooltip($oTooltip);

        $aY = array();

        foreach ($aGraphs as $k => $v) {
            $y = empty($v['y-right']) ? 0 : 1;
            $scale = empty($v['scale']) ? 1 : $v['scale'];

            if (!isset($aY[$y])) {
                $aY[$y] = new y_axis();
                $aY[$y]->set_colours($v['colour'], '#f6f6f6');
            }

            if (empty($v['y-remap'])) {
                $this->setAxisRange($aY[$y], $k, $scale);
            } else {
                $this->remapSeries($aY[$y], $k, $scale);
            }

            $oSeries = $this->getSeries($k, $v['type'], $v['colour']);
            if (!empty($this->drillDown)) {
                $oSeries->set_on_click($this->drillDown);
            }
            $oSeries->set_on_show($v['effect']);
            if ($y) {
                $oSeries->attach_to_right_y_axis();
            }
            $aGraphs[$k] = $oSeries;
        }

        $oChart->set_x_axis($oX);

        foreach ($aY as $y => $e) {
            $method = $y ? 'set_y_axis_right' : 'set_y_axis';
            $oChart->$method($e);
        }
        foreach ($aGraphs as $e) {
            $oChart->add_element($e);
        }

        return $oChart;
    }

    public function displayGraph()
    {
        $oChart = $this->getChart(array(
            1 => array(
                'type'    => 'bar_glass',
                'colour'  => '#0033cc',
                'effect'  => new bar_on_show('grow-up', 1, 0.25),
            ),
            2 => array(
                'type'    => 'bar_glass',
                'colour'  => '#009900',
                'effect'  => new bar_on_show('grow-up', 1, 0.25),
                'y-right' => true,
                'scale'   => 0.67,
            ),
            3 => array(
                'type'    => 'line',
                'colour'  => '#cc3333',
                'effect'  => new bar_on_show('pop-up', 1, 0.25),
                'y-remap' => true,
                'scale'   => 0.33,
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
