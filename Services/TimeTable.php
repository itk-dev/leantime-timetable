<?php

namespace Leantime\Plugins\TimeTable\Services;

use Carbon\CarbonInterface;
use Leantime\Plugins\TimeTable\Repositories\TimeTable as TimeTableRepository;
use Leantime\Domain\Tickets\Repositories\Tickets as TicketRepository;
use Carbon\CarbonImmutable;

/**
 * Time table services file.
 */
class TimeTable
{
    private TimeTableRepository $timeTableRepo;
    private TicketRepository $ticketRepo;

    /**
     * @var array<string, string>
     */
    private static array $assets = [
        // source => target
        __DIR__ . '/../dist/js/timeTable.js' => APP_ROOT . '/public/dist/js/plugin-timeTable.js',
        __DIR__ . '/../dist/css/timeTable.css' => APP_ROOT . '/public/dist/css/plugin-timeTable.css',
    ];

    /**
     * constructor
     *
     * @param  TimeTableRepository $timeTableRepo
     * @param TicketRepository $ticketRepo
     * @return void
     */
    public function __construct(TimeTableRepository $timeTableRepo, TicketRepository $ticketRepo)
    {
        $this->timeTableRepo = $timeTableRepo;
        $this->ticketRepo = $ticketRepo;
    }

    /**
     * Install plugin.
     *
     * @return void
     */
    public function install(): void
    {
        foreach (self::getAssets() as $source => $target) {
            if (file_exists($target)) {
                unlink($target);
            }
            symlink($source, $target);
        }
    }

    /**
     * Uninstall plugin.
     *
     * @return void
     */
    public function uninstall(): void
    {
        foreach (self::getAssets() as $target) {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }

    /**
     * Get assets
     *
     * @return array|string[]
     */
    private static function getAssets(): array
    {
        return self::$assets;
    }

    /**
     * @return array<array<string, string>>
     */
    public function getUniqueTicketIds(CarbonInterface $dateFrom, CarbonInterface $dateTo): array
    {
        return $this->timeTableRepo->getUniqueTicketIds($dateFrom, $dateTo);
    }

    /**
     * @return array<array<string, string>>
     */
    public function getTimesheetByTicketIdAndWorkDate(string $ticketId, CarbonInterface $workDate, ?string $searchTerm): array
    {
        return $this->timeTableRepo->getTimesheetByTicketIdAndWorkDate($ticketId, $workDate, $searchTerm);
    }

     /**
     * updateTime - update specific time entry
     * @param array<string, mixed> $values
     * @return void
     */
    public function updateOrAddTimelogOnTicket(array $values, int $originalId): void
    {
        $this->timeTableRepo->updateOrAddTimelogOnTicket($values, $originalId);
    }

    /**
     * Adds a timelog to a ticket.
     *
     * @param array<string, mixed> $values The data required to add the timelog on the ticket.
     * @return void
     */
    public function addTimelogOnTicket(array $values)
    {
        $this->timeTableRepo->addTimelogOnTicket($values);
    }

    /**
     * Retrieves all state labels for all projects.
     *
     * @return array<int, array> The state labels grouped by project IDs.
     */
    public function getAllStateLabels(): array
    {
        $statusListSeed = $this->ticketRepo->statusListSeed;
        return $this->timeTableRepo->getAllStateLabels($statusListSeed);
    }
}
