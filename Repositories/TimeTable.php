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
     * @return array<array<string, string>>
     */
    public function getTimesheetByTicketIdAndWorkDate(string $ticketId, CarbonImmutable $workDate, ?string $searchTerm): array
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
    public function updateOrAddTimelogOnTicket(array $values, int $originalId = null): void
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

    public function addTimelogOnTicket(array $values)
    {
        $sql = 'SELECT id FROM zp_timesheets WHERE ticketId = :ticketId AND workDate = :date AND userId = :userId';
        $stmn = $this->db->database->prepare($sql);
        $stmn->bindValue(':ticketId', $values['ticketId']);
        $stmn->bindValue(':date', $values['workDate']->format('Y-m-d H:i:s'));
        $stmn->bindValue(':userId', $values['userId'], PDO::PARAM_INT);
        $stmn->execute();

        $existingEntry = $stmn->fetch(PDO::FETCH_ASSOC);
        $stmn->closeCursor();

        // Insert only if the entry doesn't exist
        if (!$existingEntry) {
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
    }
}
