<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Leantime\Core\Template;
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
    protected Template $template;

    /**
     * constructor
     * @access public
     *
     * @return void
     */
    public function init(SettingRepository $settingsRepo, Template $template): void
    {
        $this->settingsRepo = $settingsRepo;
        $this->template = $template;
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
            $ticketCacheExpiration = (int) ($this->settingsRepo->getSetting('itk-leantime-timetable.ticketCacheExpiration') ?: 120);
            $this->template->assign('ticketCacheExpiration', $ticketCacheExpiration);
        } catch (\Exception $e) {
            $this->template->setNotification('An error occurred while saving the settings. ' . $e, 'error');
        }

        return $this->template->display('TimeTable.settings');
    }

    /**
     * post method
     *
     * @param array<string, string> $params The parameters received in the request
     * @return RedirectResponse
     * @throws \Exception
     */
    public function post(array $params): RedirectResponse
    {
        try {
            $this->settingsRepo->saveSetting('itk-leantime-timetable.ticketCacheExpiration', (int)($params['ticketCacheExpiration'] ?? 0));
            $this->template->setNotification('The settings were successfully saved.', 'success');
        } catch (\Exception $e) {
            $this->template->setNotification('An error occurred while saving the settings. ' . $e, 'error');
        }


        return Frontcontroller::redirect(BASE_URL . '/TimeTable/settings');
    }
}
