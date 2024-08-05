<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Leantime\Core\Controller;
use Symfony\Component\HttpFoundation\Response;
use Leantime\Plugins\TimeTable\Repositories\TimeTable as TimeTableRepository;

class TimeTable extends Controller
{
    private TimeTableRepository $timeTableRepo;

    /**
     * constructor
     *
     * @param TimeTableRepository $timeTableRepo
     * @return void
     */
    public function init(TimeTableRepository $timeTableRepo): void
    {
      $this->timeTableRepo = $timeTableRepo;
    }

    /**
     * get
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function get(): Response
    {
        $timeTableStyle = dirname(dirname($_SERVER["DOCUMENT_ROOT"])) . "dist/css/timeTable.css";
        $timeTableScript = dirname(dirname($_SERVER["DOCUMENT_ROOT"])) . "dist/js/timeTable.js";
        $this->tpl->assign("timeTableStyle", $timeTableStyle);
        $this->tpl->assign("timeTableScript", $timeTableScript);

        return $this->tpl->display("TimeTable.timetable");
    }

    /**
     * post
     *
     * @param array $params
     * @return void
     */
    public function post(array $params): void
    {
    }
}
