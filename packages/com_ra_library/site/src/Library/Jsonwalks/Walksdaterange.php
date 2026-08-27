<?php

/*
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

/**
 * Description of walksrange
 *
 * @author chris
 */

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

class Walksdaterange {

    private $startDate = null;
    private $endDate = null;
    private $future = true;

    public function __construct() {
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime();
        $this->endDate->add(\DateInterval::createFromDateString("12 months"));
//    $this->setPast('1 month');
    }

    public function setPast($period = '10 years') {
        $this->endDate = new \DateTime();
        $this->startDate = new \DateTime();
        $this->startDate->sub(\DateInterval::createFromDateString($period));
        $this->future = true;
    }

    public function setFuture($period = '12 months') {
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime();
        $this->endDate->add(\DateInterval::createFromDateString($period));
        $this->future = false;
    }

    public function isFuture() {
        return $this->future;
    }

    public function getStartDateString() {
        return $this->startDate->format("Y-m-d");
    }

    public function getEndDateString() {
        return $this->endDate->format("Y-m-d");
    }
}
