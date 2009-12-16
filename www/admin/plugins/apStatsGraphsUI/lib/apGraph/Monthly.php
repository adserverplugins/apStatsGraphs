<?php

class AP_Graph_Monthly extends AP_Graph
{
    protected $breakDown = 'month';
    
    static function factory($year)
    {
        $oStart = new Date(sprintf('%04d-%02d-%02d', $year, 1, 1));
        $oEnd   = new Date(sprintf('%04d-%02d-%02d', $year, 12, 31));
        return new AP_Graph_Monthly($oStart, $oEnd);
    }

    public function getUrl()
    {
        return $this->appendToUrl(
            parent::getUrl(),
            'year='.$this->oStart->format('%Y')
        );
    }

    public function getJs()
    {
        return <<<EOF
function drill_down(m)
{
        var y = '{$this->oStart->format('%Y')}';

        m++;

        var u = location.href.replace(/(year|month|day)=[^&]*&?/g, '');
        if (!u.match(/\?$/)) {
            u += u.indexOf('?') == -1 ? '?' : '&';
        }
        u += 'month=' + y + '-' + (m > 9 ? '' : '0') + m;
        location.href = u;
}
EOF;
    }

    public function getLinks()
    {
        $oDate = new Date($this->oStart);
        $oNow  = new Date();
        $aLinks = array();
        $year = $oDate->getYear();
        $aLinks['prev'] = '?year='.($year - 1);
        $oDate->setYear($year + 1);
        if ($oNow->after($oDate)) {
            $aLinks['next'] = '?year='.($year + 1);
        }

        return $aLinks;
    }

    protected function padBefore($row)
    {
        $month = (int)substr($row[0], 5);
        for ($i = 1; $i < $month; $i++) {
            $this->aData[0][] = sprintf('%04d-%02d', $this->oStart->getYear(), $i);
            $this->aData[1][] = $this->aData[2][] = 0;
        }
    }

    protected function padAfter($lastRow)
    {
        $i = 1 + (int)substr($lastRow[0], 5);
        for (; $i <= 12; $i++) {
            $this->aData[0][] = sprintf('%04d-%02d', $this->oStart->getYear(), $i);
            $this->aData[1][] = $this->aData[2][] = 0;
        }
    }

    protected function getSeries($type, $key, $colour)
    {
        $oSeries = parent::getSeries($type, $key, $colour);
        $oSeries->set_on_click('drill_down');
        return $oSeries;
    }

    protected function getTitle()
    {
        return 'Monthly Stats - '.$this->oStart->getYear();
    }

    protected function getToday($oY)
    {
        $oNow = new Date();
        if ($this->oStart->getYear() == $oNow->getYear()) {
            return parent::getToday($oY, $oNow->getMonth());
        }

        return false;
    }


}
