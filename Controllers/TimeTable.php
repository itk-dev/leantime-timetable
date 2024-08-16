<?php

namespace Leantime\Plugins\TimeTable\Controllers;

use Carbon\CarbonImmutable;
use Leantime\Core\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Leantime\Plugins\TimeTable\Services\TimeTable as TimeTableService;
use Leantime\Core\Language as LanguageCore;
use Leantime\Core\Frontcontroller as FrontcontrollerCore;
use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Domain\Api\Services\Api as ApiService;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Auth\Services\Auth as AuthService;
use Carbon\Carbon;

/**
 * Timetable controller.
 */
class TimeTable extends Controller
{
    private TimeTableService $timeTableService;
    protected LanguageCore $language;

    /**
     * constructor
     *
     * @param TimeTableService $timeTableService
     * @param LanguageCore     $language
     * @return void
     */
    public function init(TimeTableService $timeTableService, LanguageCore $language): void
    {
        $this->timeTableService = $timeTableService;
        $this->language = $language;
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
            $values = [
                'userId' => session('userdata.id'),
                'hours' => $_POST['timesheet-hours'],
                'workDate' => (new Carbon($_POST['timesheet-date'], session('usersettings.timezone')))->setTimezone('UTC'),
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

        return FrontcontrollerCore::redirect($redirectUrl);
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
        // Filters for the sql select
        $userIdForFilter = null;
        $searchTermForFilter = null;
        $now = CarbonImmutable::now();
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

        $weekStartDate = $now->startOfWeek()->setToDbTimezone();
        $weekEndDate = $now->endOfWeek()->setToDbTimezone();

        $this->tpl->assign('currentSearchTerm', $searchTermForFilter);

        $days = explode(',', $this->language->__('language.dayNames'));
        // Make the first day of week monday, by shifting sunday to the back of the array.
        $days[] = array_shift($days);
        $weekDates = [];
        foreach ($days as $key => $day) {
            $weekDates[$key] = $weekStartDate->addDays($key);
        }
        $relevantTicketIds = $this->timeTableService->getUniqueTicketIds($weekStartDate, $weekEndDate);

        $timesheetsByTicket = [];
        foreach ($relevantTicketIds as $ticket) {
            $timesheetsSortedByWeekdate = [];
            foreach ($weekDates as $weekDate) {
                $timesheetsByTicketAndDate = $this->timeTableService->getTimesheetByTicketIdAndWorkDate($ticket['ticketId'], $weekDate, $searchTermForFilter);
                $timesheetsSortedByWeekdate[$weekDate->format('Y-m-d')] = $timesheetsByTicketAndDate;
                if ($timesheetsByTicketAndDate !== null && count($timesheetsByTicketAndDate) > 0) {
                    $timesheetsSortedByWeekdate['ticketTitle'] = $timesheetsByTicketAndDate[0]['headline'];
                    $timesheetsSortedByWeekdate['ticketId'] = $timesheetsByTicketAndDate[0]['ticketId'];
                }
            }

            $timesheetsByTicket[$ticket['ticketId']] = $timesheetsSortedByWeekdate;
        }

        // All tickets assignet to the template
        $this->tpl->assign('timesheetsByTicket', $timesheetsByTicket);
        $this->tpl->assign('weekDays', $days);
        $this->tpl->assign('weekDates', $weekDates);

        return $this->tpl->display('TimeTable.timetable');
    }
}
