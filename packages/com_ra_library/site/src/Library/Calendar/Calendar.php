<?php
namespace Ramblers\Component\Ra_library\Site\Library\Calendar;
/**
 * @author  Xu Ding
 * @email   thedilab@gmail.com
 * @website http://www.StarTutorial.com
 * */
class Calendar {

    private $size;
    private $dayLabels = array("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun");
    private $currentYear = 2000;
    private $currentMonth = 1;
    private $currentDay = 0;
    private $currentDate = null;
    private $daysInMonth = 0;
    private $events;
    static $copyno = 0;
    private $baseno = 1;
    private $displayAll = false;
    private $monthFormat = "Y M";
    private $display = null;

    public function __construct($size, $mdisplayAll) {
        $this->size = $size;
        self::$copyno += 1;
        $this->displayAll = $mdisplayAll;
    }

    public function setMonthFormat($format) {
        $this->monthFormat = $format;
    }

    public function show($display, $events) {
        $this->display = $display;
        $lastdate = $events->getLastDate();
        $this->events = $events;
        $this->baseno = self::$copyno * 20;
        $today = getdate();
        $this->currentYear = $today["year"];
        $this->currentMonth = $today["mon"];
        $enddate = sprintf("%04d", $this->currentYear) . "-" . sprintf("%02d", $this->currentMonth) . "-01";
        $navtype = navigationtype::first;
        $i = 0;
        do {
            if (substr($lastdate, 0, 7) == substr($enddate, 0, 7)) {
                if ($i == 0) {
                    $navtype = navigationtype::neither;
                } else {
                    $navtype = navigationtype::last;
                }
            }
            if ($this->displayAll) {
                $navtype = navigationtype::none;
            }
            $this->showMonth($navtype);
            $i+=1;
            If ($i >= 12) {
                return;
            }
            $this->currentMonth+=1;
            if ($this->currentMonth == 13) {
                $this->currentMonth = 1;
                $this->currentYear+=1;
            }
            $navtype = navigationtype::both;
            $enddate = sprintf("%04d", $this->currentYear) . "-" . sprintf("%02d", $this->currentMonth) . "-01";
        } while ($lastdate > $enddate);
    }

    /**
     * print out the calendar
     */
    private function showMonth($navtype) {
        $this->currentDay = 0;
        $this->daysInMonth = $this->_daysInMonth($this->currentMonth, $this->currentYear);
        switch ($navtype) {
            case navigationtype::first:
                $disp = ' style="display: block;"';
                break;
            case navigationtype::neither:
                $disp = ' style="display: block;"';
                break;
            case navigationtype::none:
                $disp = ' style="display: block;"';
                break;
            default:
                $disp = ' style="display: none;"';
                break;
        }
        $class = "ra_calendar ra_calsize250";
        if ($this->size == 0) {
            $class = "ra_calendar  ra_calsize0";
        }
        if ($this->size == 200) {
            $class = "ra_calendar ra_calsize200";
        }
        if ($this->size == 250) {
            $class = "ra_calendar ra_calsize250";
        }
        if ($this->size == 400) {
            $class = "ra_calendar ra_calsize400";
        }
        $content = '<div class="' . $class . '" id=' . $this->getDivId($this->baseno + $this->currentMonth) . $disp . '>' .
                '<div class="box">' .
                $this->_createNavi($navtype) .
                '</div>' . PHP_EOL .
                '<div class="box-content">' . PHP_EOL .
                '<ul class="label">' . $this->_createLabels() . '</ul>' . PHP_EOL;
        $content.='<div class="clear"></div>' . PHP_EOL;
        $content.='<ul class="dates">' . PHP_EOL;
        $weeksInMonth = $this->_weeksInMonth($this->currentMonth, $this->currentYear);
        // Create weeks in a month
        for ($i = 0; $i < $weeksInMonth; $i++) {
            //Create days in a week
            for ($j = 1; $j <= 7; $j++) {
                $content.=$this->_showDay($i * 7 + $j);
            }
        }
        $content.='</ul>' . PHP_EOL;
        $content.='<div class="clear"></div>' . PHP_EOL;
        $content.='</div>' . PHP_EOL;
        $content.='</div>' . PHP_EOL;
        echo $content;
    }

    /*     * ******************* PRIVATE ********************* */

    /**
     * create the li element for ul
     */
    private function _showDay($cellNumber) {
        $todayclass = "";
        if ($this->currentDay == 0) {
            $firstDayOfTheWeek = date('N', strtotime($this->currentYear . '-' . $this->currentMonth . '-01'));
            if (intval($cellNumber) == intval($firstDayOfTheWeek)) {
                $this->currentDay = 1;
            }
        }
        if (($this->currentDay != 0) && ($this->currentDay <= $this->daysInMonth)) {
            $this->currentDate = date('Y-m-d', strtotime($this->currentYear . '-' . $this->currentMonth . '-' . ($this->currentDay)));
            $cellContent = $this->currentDay;
            $this->currentDay++;
        } else {
            $this->currentDate = null;
            $cellContent = null;
        }
        if ($cellContent != null) {
            $today = date("Y-m-d");
            if ($this->currentDate == $today) {
                $todayclass = " today";
            }
            $cellContent = $this->addEvents($cellContent, $this->currentDate);
        }
        return '<li class="' . $todayclass . ($cellNumber % 7 == 1 ? ' start ' : ($cellNumber % 7 == 0 ? ' end ' : ' ')) .
                ($cellContent == null ? 'mask' : '') . '">' . $cellContent . '</li>' . PHP_EOL;
    }

    /**
     * create navigation
     */
    private function _createNavi($navtype) {
        $nextMonth = $this->currentMonth == 12 ? 1 : intval($this->currentMonth) + 1;
        // $nextYear = $this->currentMonth == 12 ? intval($this->currentYear) + 1 : $this->currentYear;
        $preMonth = $this->currentMonth == 1 ? 12 : intval($this->currentMonth) - 1;
        // $preYear = $this->currentMonth == 1 ? intval($this->currentYear) - 1 : $this->currentYear;
        $out = '<div class="header">';
        if ($navtype == navigationtype::last or $navtype == navigationtype::both) {
            $out.= '<a class="prev" ' . $this->getTogglePair($preMonth, $this->currentMonth) . ' >Prev</a>';
        }
        $out.= '<span class="title">' . date($this->monthFormat, strtotime($this->currentYear . '-' . $this->currentMonth . '-1')) . '</span>';
        if ($navtype == navigationtype::first or $navtype == navigationtype::both) {
            $out.= '<a class="next" ' . $this->getTogglePair($nextMonth, $this->currentMonth) . ' >Next</a>';
        }
        $out.= '</div>';
        return $out;

        // <a href="#" onclick="javascript:toggle_visibility('foo');">Click here to toggle visibility of element #foo</a>
    }

    private function getTogglePair($one, $two) {
        $idone = $this->getDivId($this->baseno + $one);
        $idtwo = $this->getDivId($this->baseno + $two);
        return ' onclick="javascript:ra.html.toggleVisibilities(' . $idone . ',' . $idtwo . ')"';
    }

    private function getDivId($no) {
        return "'ra_cal" . sprintf("%04d", $this->baseno + $no) . "'";
    }

    /**
     * create calendar week labels
     */
    private function _createLabels() {
        $content = '';
        foreach ($this->dayLabels as $index => $label) {
            $content.='<li class="' . ($label == 6 ? 'end title' : 'start title') . ' title">' . $label . '</li>';
        }
        return $content;
    }

    /**
     * calculate number of weeks in a particular month
     */
    private function _weeksInMonth($month, $year) {
        // find number of days in this month
        $daysInMonths = $this->_daysInMonth($month, $year);
        $numOfweeks = ($daysInMonths % 7 == 0 ? 0 : 1) + intval($daysInMonths / 7);
        $monthEndingDay = date('N', strtotime($year . '-' . $month . '-' . $daysInMonths));
        $monthStartDay = date('N', strtotime($year . '-' . $month . '-01'));
        if ($monthEndingDay < $monthStartDay) {
            $numOfweeks++;
        }
        return $numOfweeks;
    }

    /**
     * calculate number of days in a particular month
     */
    private function _daysInMonth($month, $year) {
        return $number = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    private function addEvents($cellContent, $currentDate) {
        //  echo $cellContent;
        //  echo $currentDate;
        return $this->events->addEvent($this->display, $cellContent, $currentDate);
    }

}

abstract class navigationtype {

    const first = 0;
    const both = 1;
    const last = 2;
    const neither = 3;
    const none = 4;

}

//- See more at: http://www.startutorial.com/articles/view/how-to-build-a-web-calendar-in-php#sthash.dyuf6D75.dpuf