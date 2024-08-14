<?php

namespace Leantime\Plugins\TimeTable\Services;
use Leantime\Plugins\TimeTable\Repositories\TimeTable as TimeTableRepository;


class TimeTable
{

    private TimeTableRepository $timeTableRepo;

    private static array $assets = [
        // source => target
        __DIR__ . '/../assets/timeTable.js' => APP_ROOT . '/public/dist/js/plugin-timeTable.v%%VERSION%%.js',
        __DIR__ . '/../assets/timeTable.css' => APP_ROOT . '/public/dist/css/plugin-timeTable.v%%VERSION%%.css',
    ];

    /**
     * constructor
     *
     * @param TimeTableRepository $timeTableRepo
     * @return void
     */
    public function __construct(TimeTableRepository $timeTableRepo) {
      $this->timeTableRepo = $timeTableRepo;
    }
    public function install(): void
    {
        foreach (self::getAssets() as $source => $target) {
            if (file_exists($target)) {
                unlink($target);
            }
            symlink($source, $target);
        }
    }

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
}
