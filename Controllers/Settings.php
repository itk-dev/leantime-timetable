<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Leantime\Core\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings Controller for Timetable plugin
 *
 * @package    leantime
 * @subpackage plugins
 */
class Settings extends Controller
{
    /**
     * init
     *
     * @return void
     */
    public function init(): void
    {
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

        return $this->tpl->display('TimeTable.settings');
    }
}
