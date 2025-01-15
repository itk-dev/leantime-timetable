<?php

namespace Leantime\Plugins\TimeTable\Repositories;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Leantime\Core\Db\Db as DbCore;
use PDO;

/**
 * This is the time table repository, that makes (hopefully) the relevant sql queries.
 */
class TimeTable
{
    /**
     * @var DbCore|null - db connection
     */
    private null|DbCore $db = null;

    /**
     * __construct - get db connection
     *
     * @access public
     * @return void
     */
    public function __construct(DbCore $db)
    {
        $this->db = $db;
    }

    /**
     * @return array<array<string, string>>
     */
    public function getUniqueTicketIds(CarbonInterface $dateFrom, CarbonInterface $dateTo): array
    {
        $sql = 'SELECT DISTINCT
        timesheet.ticketId
        FROM zp_timesheets AS timesheet
        WHERE timesheet.userId = :userId AND timesheet.workDate >= :dateFrom AND timesheet.workDate < :dateTo';
        $stmn = $this->db->database->prepare($sql);

        $userId = session('userdata.id');
        if ($userId !== '') {
            $stmn->bindValue(':userId', $userId, PDO::PARAM_INT);
        }

        $stmn->bindValue(':dateFrom', $dateFrom, PDO::PARAM_STR);
        $stmn->bindValue(':dateTo', $dateTo, PDO::PARAM_STR);

        $stmn->execute();
        $values = $stmn->fetchAll();
        $stmn->closeCursor();

        return $values;
    }

    /**
     * getTimesheetByTicketIdAndWorkDate - Retrieves timesheet data based on a given ticket ID and work date,
     * optionally filtering by a search term.
     *
     * @access public
     * @param string          $ticketId   The ticket ID to filter the timesheet data.
     * @param CarbonInterface $workDate   The specific work date to filter the timesheet data.
     * @param string|null     $searchTerm An optional search term to further filter results by ticket ID or headline.
     * @return array<string, mixed> Returns an array of matching timesheet data.
     */
    public function getTimesheetByTicketIdAndWorkDate(string $ticketId, CarbonInterface $workDate, ?string $searchTerm): array
    {

        $searchTermQuery = isset($searchTerm)
            ? " AND
        (zp_tickets.id LIKE CONCAT( '%', :searchTerm, '%') OR
        zp_tickets.headline LIKE CONCAT( '%', :searchTerm, '%')) "
            : '';

        $sql = 'SELECT
        timesheet.id,
        CAST(timesheet.workDate AS DATE) as workDate,
        timesheet.hours,
        timesheet.description,
        timesheet.ticketId,
        zp_tickets.headline,
        zp_tickets.id as ticketId,
        zp_tickets.type as ticketType,
        zp_tickets.hourRemaining,
        zp_projects.name
        FROM zp_timesheets AS timesheet
        LEFT JOIN zp_tickets ON timesheet.ticketId = zp_tickets.id
        LEFT JOIN zp_projects ON zp_tickets.projectId = zp_projects.id
        WHERE timesheet.userId = :userId AND timesheet.ticketId = :ticketId AND (timesheet.workDate BETWEEN :dateFrom AND :dateTo)' . $searchTermQuery;

        $stmn = $this->db->database->prepare($sql);

        $userId = session('userdata.id');
        if ($userId !== '') {
            $stmn->bindValue(':userId', $userId, PDO::PARAM_INT);
        }
        if ($searchTerm !== null) {
            $stmn->bindValue(':searchTerm', $searchTerm, PDO::PARAM_STR);
        }

        $stmn->bindValue(':ticketId', $ticketId, PDO::PARAM_INT);
        $stmn->bindValue(':dateFrom', $workDate->startOfDay(), PDO::PARAM_STR);
        $stmn->bindValue(':dateTo', $workDate->endOfDay(), PDO::PARAM_STR);

        $stmn->execute();
        $values = $stmn->fetchAll();
        $stmn->closeCursor();
        return $values;
    }

    /**
     * updateOrAddTimelogOnTicket - Updates or adds a timelog entry for a ticket
     *
     * @param array<string, mixed> $values     An array containing the values for the timelog entry
     * @param int|null             $originalId (Optional) The original timelog id to check for updates or deletion
     *
     * @return void
     * @access public
     */
    public function updateOrAddTimelogOnTicket(array $values, ?int $originalId = null): void
    {
        $sql = 'SELECT * FROM zp_timesheets WHERE ticketId = :ticketId AND workDate = :date AND userId = :userId';

        $stmn = $this->db->database->prepare($sql);
        $stmn->bindValue(':ticketId', $values['ticketId']);
        $stmn->bindValue(':date', $values['workDate']->format('Y-m-d H:i:s'));
        $stmn->bindValue(':userId', $values['userId'], PDO::PARAM_INT);
        $stmn->execute();

        $timesheet = $stmn->fetch(PDO::FETCH_ASSOC);
        $stmn->closeCursor();

        if ($timesheet) {
            if ($originalId && $originalId == $timesheet['id']) {
                $sql = 'UPDATE zp_timesheets SET hours = :hours, description = :description WHERE id = :id AND userId = :userId';
            } else {
                $sql = 'UPDATE zp_timesheets SET hours = hours + :hours, description = CONCAT(description, " ", :description) WHERE id = :id AND userId = :userId';
            }

            $stmn = $this->db->database->prepare($sql);
            $stmn->bindValue(':id', $timesheet['id'], PDO::PARAM_INT);
            $stmn->bindValue(':hours', $values['hours']);
            $stmn->bindValue(':userId', $values['userId'], PDO::PARAM_INT);
            $stmn->bindValue(':description', $values['description']);
        } else {
            // else, insert new record
            $sql = 'INSERT INTO zp_timesheets (
        userId,
        ticketId,
        workDate,
        hours,
        description,
        kind
   ) VALUES (
        :userId,
        :ticket,
        :date,
        :hours,
        :description,
        :kind
)';

            $stmn = $this->db->database->prepare($sql);
            $stmn->bindValue(':userId', $values['userId'], PDO::PARAM_INT);
            $stmn->bindValue(':ticket', $values['ticketId']);
            $stmn->bindValue(':date', $values['workDate']->format('Y-m-d H:i:s'));
            $stmn->bindValue(':kind', $values['kind']);
            $stmn->bindValue(':description', $values['description']);
            $stmn->bindValue(':hours', $values['hours']);
        }

        $stmn->execute();
        $stmn->closeCursor();

        if ($originalId && (empty($timesheet) || $values['workDate'] == $timesheet['workDate'] && $values['timesheetId'] != $timesheet['id'])) {
            $sql = 'DELETE FROM zp_timesheets WHERE id = :id AND userId = :userId';
            $stmn = $this->db->database->prepare($sql);
            $stmn->bindValue(':id', $originalId, PDO::PARAM_INT);
            $stmn->bindValue(':userId', $values['userId'], PDO::PARAM_INT);
            $stmn->execute();
            $stmn->closeCursor();
        }
    }

    /**
     * addTimelogOnTicket - Adds a timelog entry for a specific ticket.
     * If an entry for the same date, ticket, and user already exists, it checks
     * whether the entry should be overwritten or prevents duplicate insertion.
     *
     * @param array<string, mixed> $values An associative array containing the following keys:
     *     - 'userId' (int): The ID of the user creating the timelog.
     *     - 'ticketId' (int): The ID of the ticket associated with the timelog.
     *     - 'workDate' (DateTime): The date and time the timelog is being created for.
     *     - 'hours' (float): The number of hours being logged.
     *     - 'description' (string): The description of the work done.
     *     - 'kind' (string): The type of work being logged.
     *     - 'entryCopyOverwrite' (string|null, optional): A flag to indicate if existing entries should be overwritten.
     *
     * @return void
     */
    public function addTimelogOnTicket(array $values)
    {
        // Check for an existing timelog
        $sql = 'SELECT id FROM zp_timesheets WHERE ticketId = :ticketId AND workDate = :date AND userId = :userId';
        $stmn = $this->db->database->prepare($sql);
        $stmn->bindValue(':ticketId', $values['ticketId']);
        $stmn->bindValue(':date', $values['workDate']->format('Y-m-d H:i:s'));
        $stmn->bindValue(':userId', $values['userId'], PDO::PARAM_INT);
        $stmn->execute();

        $existingEntry = $stmn->fetch(PDO::FETCH_ASSOC);
        $stmn->closeCursor();

        // If 'entryCopyOverwrite' is set, delete the existing entry
        if ($existingEntry) {
            if (isset($values['entryCopyOverwrite']) && $values['entryCopyOverwrite'] === 'on') {
                $sql = 'DELETE FROM zp_timesheets WHERE id = :id';
                $stmn = $this->db->database->prepare($sql);
                $stmn->bindValue(':id', $existingEntry['id'], PDO::PARAM_INT);
                $stmn->execute();
                $stmn->closeCursor();
            } else {
                // If overwrite is not set, prevent duplicate addition
                return; // Exit without inserting
            }
        }

        // Insert the new timelog
        $sql = 'INSERT INTO zp_timesheets (
            userId, ticketId, workDate, hours, description, kind
        ) VALUES (
            :userId, :ticketId, :date, :hours, :description, :kind
        )';

        $stmn = $this->db->database->prepare($sql);
        $stmn->bindValue(':userId', $values['userId'], PDO::PARAM_INT);
        $stmn->bindValue(':ticketId', $values['ticketId']);
        $stmn->bindValue(':date', $values['workDate']->format('Y-m-d H:i:s'));
        $stmn->bindValue(':hours', $values['hours']);
        $stmn->bindValue(':description', $values['description']);
        $stmn->bindValue(':kind', $values['kind']);
        $stmn->execute();
        $stmn->closeCursor();
    }

    /**
     * getAllStateLabels - Retrieves all state labels for projects based on a seed list of statuses and stored settings.
     *
     * @param array<int|string, mixed> $statusListSeed An array of default status definitions to seed the state labels.
     * @return array<string, array<int|string, mixed>> An associative array where keys are project IDs and values are arrays of state labels.
     */
    public function getAllStateLabels(array $statusListSeed): array
    {
        $sql = 'SELECT `key`, `value` FROM zp_settings WHERE `key` LIKE :keyPattern';
        $stmn = $this->db->database->prepare($sql);
        $stmn->bindValue(':keyPattern', 'projectsettings.%.ticketlabels', PDO::PARAM_STR);
        $stmn->execute();
        $results = $stmn->fetchAll(PDO::FETCH_ASSOC);
        $stmn->closeCursor();

        $allStatusLabels = [];

        foreach ($results as $row) {
            // Extract the project ID from the key
            $projectId = explode('.', $row['key'])[1];
            // Unserialize the value
            $values = @unserialize($row['value']);

            if ($values !== false) {
                $statusList = $statusListSeed;

                $statusList[-1] = $statusListSeed[-1];

                foreach ($values as $key => $status) {
                    if (is_int($key)) {
                        if (!is_array($status)) {
                            $statusList[$key] = $statusListSeed[$key];
                            if (is_array($statusList[$key]) && isset($statusList[$key]['name']) && $key !== -1) {
                                $statusList[$key]['name'] = $status;
                            }
                        } else {
                            $statusList[$key] = $status;
                        }
                    }
                }

                uasort($statusList, function ($a, $b) {
                    return $a['sortKey'] <=> $b['sortKey'];
                });

                $allStatusLabels[$projectId] = $statusList;
            }
        }

        // Ensure every project has a state list

        // Fetch all project IDs separately
        $projectIds = $this->getAllProjectIds();
        foreach ($projectIds as $projectId) {
            if (!isset($allStatusLabels[$projectId])) {
                // Default to $statusListSeed if no state list exists
                $allStatusLabels[$projectId] = $statusListSeed;
            }
        }

        return $allStatusLabels;
    }

    /**
     * getAllProjectIds - Retrieve all project IDs from the database
     *
     * @access private
     * @return array<string, string> Array of project IDs
     */
    private function getAllProjectIds(): array
    {
        $sql = 'SELECT id FROM zp_projects';
        $stmn = $this->db->database->prepare($sql);
        $stmn->execute();
        // Fetch all project IDs as a 1-dimensional array
        $projectIds = $stmn->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmn->closeCursor();

        return $projectIds;
    }
}
