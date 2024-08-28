<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Carbon\CarbonImmutable;
use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;
use Symfony\Component\HttpFoundation\Response;
use Leantime\Plugins\TimeTable\Services\TimeTable as TimeTableService;
use Leantime\Core\Language as LanguageCore;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Auth\Services\Auth as AuthService;
use Leantime\Domain\Setting\Repositories\Setting as SettingRepository;

/**
 * Timetable controller.
 */
class TimeTable extends Controller
{
    private TimeTableService $timeTableService;
    protected LanguageCore $language;
    private SettingRepository $settings;

    /**
     * constructor
     *
     * @param TimeTableService $timeTableService
     * @param LanguageCore     $language
     * @return void
     */
    public function init(TimeTableService $timeTableService, LanguageCore $language, SettingRepository $settings): void
    {
        $this->timeTableService = $timeTableService;
        $this->language = $language;
        $this->settings = $settings;
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function post(): Response
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return $this->tpl->displayJson(['Error' => 'Not Authorized'], 403);
        }
        if (isset($_POST['timesheet-id']) && $_POST['timesheet-id'] !== '') {
            $values = [
                'hours' => $_POST['timesheet-hours'],
                'description' => $_POST['timesheet-description'],
                'id' => $_POST['timesheet-id'],
            ];
            $this->timeTableService->updateTime($values);
        } else {
            $workDate = new CarbonImmutable($_POST['timesheet-date'], session('usersettings.timezone'));
            $workDate = $workDate->setToDbTimezone();

            $values = [
                'userId' => session('userdata.id'),
                'hours' => $_POST['timesheet-hours'],
                'workDate' => $workDate,
                'ticketId' => $_POST['timesheet-ticket-id'],
                'description' => $_POST['timesheet-description'],
                'kind' => 'GENERAL_BILLABLE',
            ];
            $this->timeTableService->logTimeOnTicket($values);
        }

        $redirectUrl = BASE_URL . '/TimeTable/timetable';
        if (isset($_GET['offset'])) {
            $redirectUrl = $redirectUrl . '?offset=' . $_GET['offset'];
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
        if (isset($_GET['getActiveTicketIdsOfPeriod'])) {
            $startDate = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
            $endDate = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);
            if (!$startDate || !$endDate) {
                echo json_encode([]);
                exit();
            }

            $startDate = (new CarbonImmutable($startDate, session('usersettings.timezone')))->setToDbTimezone();
            $endDate = (new CarbonImmutable($endDate, session('usersettings.timezone')))->setToDbTimezone();

            $data = $this->timeTableService->getUniqueTicketIds($startDate, $endDate);

            $ticketIds = $data ? array_column($data, 'ticketId') : [];

            echo json_encode($ticketIds);
            exit();
        }

        // Filters for the sql select
        $userIdForFilter = null;
        $searchTermForFilter = null;
        $now = CarbonImmutable::now();
        $ticketsCache = $this->settings->getSetting('timetablesettings.ticketscache') ?? 1200;

        if (isset($_GET['searchTerm'])) {
            $searchTerm = $_GET['searchTerm'];
        }

        if (isset($_GET['offset'])) {
            // Multiply offset by 7 days.
            if ((int) $_GET['offset'] > 0) {
                $now = $now->addDays((int) $_GET['offset'] * 7);
            } else {
                $now = $now->subDays(abs((int) $_GET['offset']) * 7);
            }
        }
        if (isset($_GET['searchTerm']) && $_GET['searchTerm'] !== '') {
            $searchTermForFilter = $_GET['searchTerm'];
        }

        $weekStartDateDb = $now->startOfWeek()->setToDbTimezone();
        $weekEndDateDb = $now->endOfWeek()->setToDbTimezone();
        $weekStartDateFrontend = $now->startOfWeek()->setToUserTimezone();

        $this->tpl->assign('currentSearchTerm', $searchTermForFilter);

        $days = explode(',', $this->language->__('language.dayNames'));
        // Make the first day of week monday, by shifting sunday to the back of the array.
        $days[] = array_shift($days);
        $weekDates = [];
        foreach ($days as $key => $day) {
            $weekDates[$key] = $weekStartDateFrontend->addDays($key);
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
                    $timesheetsSortedByWeekdate['ticketId'] = $timesheetsByTicketAndDate[0]['ticketId'];
                }
            }

            $timesheetsByTicket[$ticket['ticketId']] = $timesheetsSortedByWeekdate;
        }
        // All tickets assignet to the template
        $this->tpl->assign('ticketIds', implode(',', $ticketIds));
        $this->tpl->assign('timesheetsByTicket', $timesheetsByTicket);
        $this->tpl->assign('weekDays', $days);
        $this->tpl->assign('weekDates', $weekDates);
        $this->tpl->assign('ticketsCache', $ticketsCache);

        return $this->tpl->display('TimeTable.timetable');
    }
}
