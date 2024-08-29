<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;
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
        try {
            $ticketCacheExpiration = (int) ($this->settingsRepo->getSetting('itk-leantime-timetable.ticketCacheExpiration') ?: 1200);
            $this->tpl->assign('ticketCacheExpiration', $ticketCacheExpiration);
        } catch (\Exception $e) {
            $this->tpl->setNotification('An error occurred while saving the settings. ' . $e, 'error');
        }

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
        try {
            $this->settingsRepo->saveSetting('itk-leantime-timetable.ticketCacheExpiration', (int)($params['ticketCacheExpiration'] ?? 0));
            $this->tpl->setNotification('The settings were successfully saved.', 'success');
        } catch (\Exception $e) {
            $this->tpl->setNotification('An error occurred while saving the settings. ' . $e, 'error');
        }

        return Frontcontroller::redirect(BASE_URL . '/TimeTable/settings');
    }
}
