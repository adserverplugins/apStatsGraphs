<?php

/**
 * apStatsGraphs for the OpenX ad server (Free Version).
 *
 * @author Matteo Beccati
 * @copyright 2009 AdserverPlugins.com
 * @license http://creativecommons.org/licenses/by-nd/3.0/
 */

class AP_Graph_Daily extends AP_Graph
{
    protected $breakDown = 'day';
    protected $drillDown = 'drill_down';

    static function factory($year, $month, $aEntityParams)
    {
        $oStart = new Date(sprintf('%04d-%02d-%02d', $year, $month, 1));
        $oEnd   = new Date(sprintf('%04d-%02d-%02d', $year, $month, $oStart->getDaysInMonth()));
        return new AP_Graph_Daily($oStart, $oEnd, $aEntityParams);
    }

    public function getUrl($graph = true)
    {
        return $this->appendToUrl(
            parent::getUrl($graph),
            'month='.$this->oStart->format('%Y-%m')
        );
    }

    public function getJs()
    {
        return <<<EOF
function {$this->drillDown}(d)
{
        var ym = '{$this->oStart->format('%Y-%m')}';

        d++;

        var u = location.href.replace(/(year|month|day)=[^&]*&?/g, '');
        if (!u.match(/\?$/)) {
            u += u.indexOf('?') == -1 ? '?' : '&';
        }
        u += 'day=' + ym + '-' + (d > 9 ? '' : '0') + d;
        location.href = u;
}
EOF;
    }

    public function getLinks()
    {
        $oDate = new Date($this->oStart);
        $oNow  = new Date();
        $aLinks = array();
        $baseUrl = parent::getUrl(false);
        $aLinks['up'] = $this->appendToUrl($baseUrl, 'year='.$oDate->getYear());
        $oDate->subtractSpan(new Date_Span('1-0-0-0'));
        $aLinks['prev'] = $this->appendToUrl($baseUrl, 'month='.$oDate->format('%Y-%m'));
        $oDate->addSpan(new Date_Span('34-0-0-0'));
        if ($oNow->after($oDate)) {
            $aLinks['next'] = $this->appendToUrl($baseUrl, 'month='.$oDate->format('%Y-%m'));
        }

        return $aLinks;
    }

    protected function padBefore($row)
    {
        $day = (int)substr($row[0], 8);
        for ($i = 1; $i < $day; $i++) {
            $this->aData[0][] = sprintf('%04d-%02d-%02d',
                $this->oStart->getYear(),
                $this->oStart->getMonth(),
                $i
            );
            for ($j = 1; $j < count($this->aData); $j++) {
                $this->aData[$j][] = null;
            }
        }
    }

    protected function padAfter($lastRow)
    {
        $i = 1 + (int)substr($lastRow[0], 8);
        $days = $this->oStart->getDaysInMonth();
        for (; $i <= $days; $i++) {
            $this->aData[0][] = sprintf('%04d-%02d-%02d',
                $this->oStart->getYear(),
                $this->oStart->getMonth(),
                $i
            );
            for ($j = 1; $j < count($this->aData); $j++) {
                $this->aData[$j][] = null;
            }
        }
    }

    protected function getTitle()
    {
        return 'Daily Stats: '.$this->oStart->format($GLOBALS['month_format']);
    }

}
