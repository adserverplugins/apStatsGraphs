<?php

/**
 * apStatsGraphs for the OpenX ad server (Free Version).
 *
 * @author Matteo Beccati
 * @copyright 2009 AdserverPlugins.com
 * @license http://creativecommons.org/licenses/by-nd/3.0/
 */

class AP_Graph_Hourly extends AP_Graph
{
    protected $breakDown = 'hour';

    static function factory($year, $month, $day, $aEntityParams)
    {
        $oStart = new Date(sprintf('%04d-%02d-%02d', $year, $month, $day));
        $oEnd   = new Date(sprintf('%04d-%02d-%02d', $year, $month, $day));
        return new AP_Graph_Hourly($oStart, $oEnd, $aEntityParams);
    }

    public function getUrl($graph = true)
    {
        return $this->appendToUrl(
            parent::getUrl($graph),
            'day='.$this->oStart->format('%Y-%m-%d')
        );
    }

    public function getLinks()
    {
        $oDate = new Date($this->oStart);
        $oNow  = new Date();
        $aLinks = array();
        $baseUrl = parent::getUrl(false);
        $aLinks['up'] = $this->appendToUrl($baseUrl, 'month='.$oDate->format('%Y-%m'));
        $oDate->subtractSpan(new Date_Span('1-0-0-0'));
        $aLinks['prev'] = $this->appendToUrl($baseUrl, 'day='.$oDate->format('%Y-%m-%d'));
        $oDate->addSpan(new Date_Span('2-0-0-0'));
        if ($oNow->after($oDate)) {
            $aLinks['next'] = $this->appendToUrl($baseUrl, 'day='.$oDate->format('%Y-%m-%d'));
        }

        return $aLinks;
    }

    protected function padAfter($lastRow)
    {
        if (empty($lastRow)) {
            for ($i = 0; $i <= 23; $i++) {
                $this->aData[0][] = sprintf('%02d:00 - %02d:59', $i, $i);
                for ($j = 1; $j < count($this->aData); $j++) {
                    $this->aData[$j][] = null;
                }
            }
        }
    }

    protected function getTitle()
    {
        return 'Hourly Stats: '.$this->oStart->format($GLOBALS['date_format']);
    }

}
