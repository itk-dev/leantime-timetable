<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Leantime\Core\Controller;
use Leantime\Core\Frontcontroller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Leantime\Domain\Setting\Repositories\Setting as SettingRepository;

/**
 * Settings Controller for TimeTable plugin
 *
 * @package    leantime
 * @subpackage plugins
 */
class Settings extends Controller
{
    private SettingRepository $settingsRepo;

    /**
     * constructor
     * @access public
     *
     * @return void
     */
    public function init(SettingRepository $settingsRepo): void
    {
        $this->settingsRepo = $settingsRepo;
    }

    /**
     * get method
     *
     * @return Response
     * @throws \Exception
     */
    public function get(): Response
    {
        $ticketCacheExpiration = (int) ($this->settingsRepo->getSetting('timetablesettings.ticketscache') ?: 1200);

        $this->tpl->assign('ticketCacheExpiration', $ticketCacheExpiration);

        return $this->tpl->display('timeTable.Settings');
    }

    /**
     * post method
     * @param array<string, int> $params
     * @return RedirectResponse
     * @throws \Exception
     */
    public function post(array $params): RedirectResponse
    {
        $this->settingsRepo->saveSetting('timetablesettings.ticketscache', (int) ($params['ticketCacheExpiration'] ?? 0));

        $this->tpl->setNotification('The settings were successfully saved.', 'success');

        return Frontcontroller::redirect(BASE_URL . '/TimeTable/settings');
    }
}
