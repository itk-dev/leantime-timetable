<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Leantime\Core\Controller;
use Symfony\Component\HttpFoundation\Response;

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

        return $this->tpl->display("TimeTable.settings");
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
