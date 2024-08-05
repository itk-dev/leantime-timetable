<?php

namespace Leantime\Plugins\TimeTable\Repositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Domain\Timesheets\Repositories\Timesheets as TimesheetRepository;


/**
 * TimeTable Repository
 */
class TimeTable {

    /**
   * constructor
   *
   * @access public
   *
   */
  public function __construct(
    private readonly TimesheetRepository $timesheetRepository,
  ) {
  }

}
