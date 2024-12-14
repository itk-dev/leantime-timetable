<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Carbon\CarbonImmutable;
use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;
use Leantime\Plugins\TimeTable\Helpers\TimeTableActionHandler;
use Symfony\Component\HttpFoundation\Response;
use Leantime\Plugins\TimeTable\Services\TimeTable as TimeTableService;
use Leantime\Core\Language as LanguageCore;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Auth\Services\Auth as AuthService;
use Leantime\Domain\Setting\Repositories\Setting as SettingRepository;
use Leantime\Domain\Timesheets\Repositories\Timesheets as TimesheetRepository;
use Leantime\Core\UI\Template;

/**
 * TimeTable controller.
 */
class TimeTable extends Controller
{
    private TimeTableService $timeTableService;
    protected LanguageCore $language;
    private SettingRepository $settings;
    protected Template $template;
    private TimesheetRepository $timesheetRepository;

    /**
     * constructor
     *
     * @param TimeTableService    $timeTableService
     * @param LanguageCore        $language
     * @param SettingRepository   $settings
     * @param Template            $template
     * @param TimesheetRepository $timesheetRepository
     * @return void
     */
    public function init(TimeTableService $timeTableService, LanguageCore $language, SettingRepository $settings, Template $template, TimesheetRepository $timesheetRepository): void
    {
        $this->timeTableService = $timeTableService;
        $this->language = $language;
        $this->settings = $settings;
        $this->template = $template;
        $this->timesheetRepository = $timesheetRepository;
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function post(): Response
    {
        $hest = $_POST;
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return $this->template->displayJson(['Error' => 'Not Authorized'], 403);
        }
        $redirectUrl = BASE_URL . '/TimeTable/TimeTable';
        $actionHandler = new TimeTableActionHandler($this->timeTableService, $this->timesheetRepository);

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'adjustPeriod':
                    $redirectUrl = $actionHandler->adjustPeriod($_POST, $redirectUrl);
                    break;
                case 'saveTicket':
                    $redirectUrl = $actionHandler->saveTicket($_POST, $redirectUrl);
                    break;
            }
        }

        return Frontcontroller::redirect($redirectUrl);
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
        $searchTermForFilter = null;
        $now = CarbonImmutable::now();
        $ticketCacheExpiration = $this->settings->getSetting('itk-leantime-timetable.ticketCacheExpiration') ?? 1200;

        try {
            if (isset($_GET['fromDate']) && $_GET['fromDate'] !== '') {
                if ($_GET['fromDate'][0] === '+' || $_GET['fromDate'][0] === '-') {
                    // If relative date format

                    $fromDate = CarbonImmutable::now()->startOfDay()->modify($_GET['fromDate']);
                } else {
                    // Try specific date format
                    $fromDate = CarbonImmutable::createFromFormat('Y-m-d', $_GET['fromDate'])->startOfDay();
                    if ($fromDate === false) {
                        // If 'Y-m-d' format fails, try 'd/m/Y' format
                        $fromDate = CarbonImmutable::createFromFormat('d/m/Y', $_GET['fromDate'])->startOfDay();
                    }
                }
            } else {
                // Default to start of current week
                $fromDate = CarbonImmutable::now()->startOfWeek()->startOfDay();
            }

            if (isset($_GET['toDate']) && $_GET['toDate'] !== '') {
                if ($_GET['toDate'][0] === '+' || $_GET['toDate'][0] === '-') {
                    // If relative date format

                    $toDate = CarbonImmutable::now()->startOfDay()->modify($_GET['toDate']);
                } else {
                    // Try specific date format
                    $toDate = CarbonImmutable::createFromFormat('Y-m-d', $_GET['toDate'])->startOfDay();
                    if ($toDate === false) {
                        // If 'Y-m-d' format fails, try 'd/m/Y' format
                        $toDate = CarbonImmutable::createFromFormat('d/m/Y', $_GET['toDate'])->startOfDay();
                    }
                }
            } else {
                // Default to end of current week
                $toDate = CarbonImmutable::now()->endOfWeek()->startOfDay();
            }
        } catch (InvalidArgumentException $e) {
            // Handle exception
            echo 'Invalid Date: ' . $e->getMessage();
        }

        $weekStartDateDb = $fromDate->setToDbTimezone();
        $weekEndDateDb = $toDate->setToDbTimezone();

        $this->template->assign('currentSearchTerm', $searchTermForFilter);

        $days = explode(',', mb_strtolower($this->language->__('language.dayNames')));
        $days[] = array_shift($days);

        $weekDates = [];
        $dateIterator = $fromDate->setToUserTimezone()->copy();

        while ($dateIterator <= $toDate) {
            $dayOfWeek = strtolower($dateIterator->locale(session('usersettings.language'))->dayName);

            // If the day is a part of the week
            if (in_array($dayOfWeek, $days)) {
                $weekDates[$dateIterator->format('d-m-Y')] = $dateIterator->copy();
            }

            // Move on to the next day
            $dateIterator = $dateIterator->addDay();
        }

        $relevantTicketIds = $this->timeTableService->getUniqueTicketIds($weekStartDateDb, $weekEndDateDb);

        $timesheetsByTicket = [];
        $ticketIds = [];
        foreach ($relevantTicketIds as $ticket) {
            if (!$ticket['ticketId']) {
                continue;
            }
            $ticketIds[] = intval($ticket['ticketId']);
            $timesheetsSortedByWeekdate = [];
            foreach ($weekDates as $weekDate) {
                $timesheetsByTicketAndDate = $this->timeTableService->getTimesheetByTicketIdAndWorkDate($ticket['ticketId'], $weekDate->setToDbTimezone(), $searchTermForFilter);
                $timesheetsSortedByWeekdate[$weekDate->format('Y-m-d')] = $timesheetsByTicketAndDate;
                if (count($timesheetsByTicketAndDate) > 0) {
                    $timesheetsSortedByWeekdate['ticketTitle'] = $timesheetsByTicketAndDate[0]['headline'];
                    $timesheetsSortedByWeekdate['ticketLink'] = '?showTicketModal=' . $timesheetsByTicketAndDate[0]['ticketId'] . '#/tickets/showTicket/' . $timesheetsByTicketAndDate[0]['ticketId'];
                    $timesheetsSortedByWeekdate['projectName'] = $timesheetsByTicketAndDate[0]['name'];
                    $timesheetsSortedByWeekdate['ticketType'] = $timesheetsByTicketAndDate[0]['ticketType'];
                    $timesheetsSortedByWeekdate['ticketId'] = $timesheetsByTicketAndDate[0]['ticketId'];
                }
            }

            $timesheetsByTicket[$ticket['ticketId']] = $timesheetsSortedByWeekdate;
        }
        // All tickets assigned to the template
        $this->template->assign('ticketIds', implode(',', $ticketIds));
        $this->template->assign('timesheetsByTicket', $timesheetsByTicket);
        $this->template->assign('weekDays', $days);
        $this->template->assign('weekDates', $weekDates);
        $this->template->assign('ticketCacheExpiration', $ticketCacheExpiration);
        $this->template->assign('fromDate', $fromDate);
        $this->template->assign('toDate', $toDate);
        return $this->template->display('TimeTable.timetable');
    }
}
