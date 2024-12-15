<?php

namespace Leantime\Plugins\TimeTable\Helpers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Domain\Timesheets\Repositories\Timesheets as TimesheetRepository;
use Leantime\Plugins\TimeTable\Services\TimeTable as TimeTableService;
use Carbon\CarbonImmutable;

/**
 *
 */
class TimeTableActionHandler
{
    /**
     * Initialize the TimeTableService and TimesheetRepository dependencies.
     *
     * @param TimeTableService    $timeTableService    The TimeTableService instance to set
     * @param TimesheetRepository $timesheetRepository The TimesheetRepository instance to set
     * @return void
     */
    public function __construct(private readonly TimeTableService $timeTableService, private readonly TimesheetRepository $timesheetRepository)
    {
    }
    /**
     * Adjusts the period based on the provided POST data.
     *
     * @param array<string, mixed> $postData The POST data containing fromDate, toDate, and backward flag.
     * @return string The adjusted redirect URL.
     */
    public function adjustPeriod(array $postData, string $redirectUrl): string
    {
        $queryParams = [];

        if (isset($postData['showThisWeek'])) {
            $now = CarbonImmutable::now();
            $queryParams['fromDate'] = $now->startOfWeek()->format('Y-m-d');
            $queryParams['toDate'] = $now->endOfWeek()->format('Y-m-d');
        } elseif (isset($postData['dateRange'])) {
            list($postData['fromDate'], $postData['toDate']) = explode(' til ', $postData['dateRange']);
        }

        if (isset($postData['fromDate']) && empty($postData['showThisWeek'])) {
            $queryParams['fromDate'] = $postData['fromDate'];
        }

        if (isset($postData['toDate']) && empty($postData['showThisWeek'])) {
            $queryParams['toDate'] = $postData['toDate'];
        }

        if (isset($postData['fromDate']) && isset($postData['toDate'])) {
            $fromDate = CarbonImmutable::createFromFormat('d-m-Y', $postData['fromDate']);
            $toDate = CarbonImmutable::createFromFormat('d-m-Y', $postData['toDate']);
            $interval = $fromDate->diffInDays($toDate) + 1;

            if (isset($postData['backward']) && $postData['backward'] == '1') {
                $fromDate = $fromDate->subDays($interval);
                $toDate = $toDate->subDays($interval);
            } elseif (isset($postData['forward']) && $postData['forward'] == '1') {
                $fromDate = $fromDate->addDays($interval);
                $toDate = $toDate->addDays($interval);
            }

            $queryParams['fromDate'] = $fromDate->format('Y-m-d');
            $queryParams['toDate'] = $toDate->format('Y-m-d');
        }

        // Use appendQueryParams to handle final redirection
        return $this->appendQueryParams($queryParams, $redirectUrl);
    }

    /**
     * Processes the ticket data and updates or adds a time log entry in the system.
     * Redirects with updated query parameters if applicable.
     *
     * @param array<string, mixed> $postData    The data for the ticket including timesheet details, user information, and other parameters
     * @param string               $redirectUrl The URL to redirect to after processing
     * @return string The updated redirect URL with query parameters if applicable
     * @throws BindingResolutionException
     */
    public function saveTicket(array $postData, string $redirectUrl): string
    {
        $jsonPayload = json_decode(file_get_contents('php://input'), true);

        // Handle delete action separately
        if (isset($jsonPayload['action']) && $jsonPayload['action'] === 'delete') {
            return $this->deleteTicket($postData, $redirectUrl);
        }

        // Process ticket save logic
        $timesheetId = isset($postData['timesheet-id']) ? (int)$postData['timesheet-id'] : 0;
        $workDate = new CarbonImmutable($postData['timesheet-date'], session('usersettings.timezone'));
        $workDate = $workDate->setToDbTimezone();

        $values = [
            'timesheetId' => $postData['timesheet-id'],
            'userId' => session('userdata.id'),
            'hours' => $postData['timesheet-hours'],
            'workDate' => $workDate,
            'ticketId' => $postData['timesheet-ticket-id'],
            'description' => $postData['timesheet-description'],
            'kind' => 'GENERAL_BILLABLE',
        ];
        $this->timeTableService->updateOrAddTimelogOnTicket($values, $timesheetId);

        // Delegate query parameter addition to appendQueryParams
        return $this->appendQueryParams($postData, $redirectUrl);
    }

    /**
     * Deletes a timesheet entry based on the given post data.
     *
     * @param array<string, mixed> $postData The data containing timesheet details, including timesheet ID
     * @param string $redirectUrl The URL to redirect to after processing
     * @return string The redirect URL or a success/error JSON message
     * @throws \Exception
     */
    public function deleteTicket(array $postData, string $redirectUrl): string
    {
        $timesheetId = $postData['timesheetId'];
        $redirectUrl = $this->appendQueryParams($postData, $redirectUrl);

        if ($timesheetId) {
            try {
                $this->timesheetRepository->deleteTime($timesheetId);
                exit(json_encode(['status' => 'success', 'redirectUrl' => $redirectUrl]));
            } catch (\Exception $e) {
                exit(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
            }

        } else {
            exit(json_encode(['status' => 'error', 'error' => 'Missing timesheetId in POST data.']));
        }

    }

    /**
     * Appends appropriate query parameters based on POST data to the given redirect URL.
     *
     * @param array<string, mixed> $postData    The POST data for query parameters.
     * @param string               $redirectUrl The base URL to which query parameters will be appended.
     * @return string The updated redirect URL with appended query parameters.
     */
    private function appendQueryParams(array $postData, string $redirectUrl): string
    {
        $queryParams = [];

        // Populate queryParams based on POST data
        if (isset($postData['fromDate'])) {
            $queryParams['fromDate'] = $postData['fromDate'];
        }

        if (isset($postData['toDate'])) {
            $queryParams['toDate'] = $postData['toDate'];
        }

        // Add query parameters to the URL
        if (!empty($queryParams)) {
            $redirectUrl .= '?' . http_build_query($queryParams);
        }

        return $redirectUrl;
    }
}
