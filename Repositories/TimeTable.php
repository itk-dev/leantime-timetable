<?php

namespace Leantime\Plugins\TimeTable\Repositories;

use Carbon\CarbonImmutable;
use Leantime\Core\Db as DbCore;
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
     * @return array<string, mixed>
     */
    public function getUniqueTicketIds(CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $sql = 'SELECT DISTINCT
        timesheet.ticketId
        FROM zp_timesheets AS timesheet
        WHERE timesheet.userId = :userId AND (timesheet.workDate BETWEEN :dateFrom AND :dateTo)';
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
     * @return array<string, mixed>
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
        zp_tickets.id as ticketId
        FROM zp_timesheets AS timesheet
        LEFT JOIN zp_tickets ON timesheet.ticketId = zp_tickets.id
        WHERE timesheet.userId = :userId AND timesheet.ticketId = :ticketId AND (timesheet.workDate BETWEEN :dateFrom AND :dateTo)' . $searchTermQuery;

        $stmn = $this->db->database->prepare($sql);

        $userId = session('userdata.id');
        if ($userId !== '') {
            $stmn->bindValue(':userId', $userId, PDO::PARAM_INT);
        }
        if ($searchTerm !== NULL) {
            $stmn->bindValue(':searchTerm', $searchTerm, PDO::PARAM_STR);
        }

        $stmn->bindValue(':ticketId', $ticketId, PDO::PARAM_INT);
        $stmn->bindValue(':dateFrom', $workDate->setToDbTimezone()->startOfDay(), PDO::PARAM_STR);
        $stmn->bindValue(':dateTo', $workDate->setToDbTimezone()->endOfDay(), PDO::PARAM_STR);

        $stmn->execute();
        $values = $stmn->fetchAll();
        $stmn->closeCursor();
        return $values;
    }

    /**
     * updateTime - update specific time entry
     *
     * @param array $values
     *
     * @return void
     */
    public function updateTime(array $values): void
    {
        $sql = "UPDATE
            zp_timesheets
        SET
            hours = :hours,
            description =:description
        WHERE
            id = :id";
        $stmn = $this->db->database->prepare($sql);
        $stmn->bindValue(':hours', $values['hours']);
        $stmn->bindValue(':description', $values['description']);
        $stmn->bindValue(':id', $values['id']);


        $stmn->execute();
        $stmn->closeCursor();
    }

    /**
     * updateTime - update specific time entry
     *
     * @param array $values
     *
     * @return void
     */
    public function logTimeOnTicket(array $values): void
    {
        $sql = "INSERT INTO zp_timesheets (
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
        )";

        $stmn = $this->db->database->prepare($sql);

        $stmn->bindValue(':userId', $values['userId'], PDO::PARAM_INT);
        $stmn->bindValue(':ticket', $values['ticketId']);
        $stmn->bindValue(':date', $values['workDate']);
        $stmn->bindValue(':kind', $values['kind']);
        $stmn->bindValue(':description', $values['description']);
        $stmn->bindValue(':hours', $values['hours']);


        $stmn->execute();
        $stmn->closeCursor();
    }
}
