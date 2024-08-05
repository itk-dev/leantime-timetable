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
        // Define assets base dist
        $assetsBaseDist = dirname($_SERVER["DOCUMENT_ROOT"], 2) . DIRECTORY_SEPARATOR . 'dist';

        // Define assets dist
        $timeTableStyle = $assetsBaseDist . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'timeTable.css';
        $timeTableScript = $assetsBaseDist . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'timeTable.js';

        // Assign the styles and scripts to the template
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
