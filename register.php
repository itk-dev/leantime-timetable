<?php

use Leantime\Core\Events;

/**
 * Adds a menu point for adding fixture data.
 * @param array<string, array<int, array<string, mixed>>> $menuStructure The existing menu structure to which the new item will be added.
 * @return array<string, array<int, array<string, mixed>>> The modified menu structure with the new item added.
 */
function addImportDataMenuPointTimeTable(array $menuStructure): array
{
    // In the menu array, timesheets occupies spot 15 in the array list, which menupoints are sorted by. Timetable should be right after it.
    $menuStructure['personal'][16] = [
        'type' => 'item',
        'title' => '<span class="fas fa-fw fa-table"></span> Timetable',
        'icon' => 'fa fa-fw fa-table',
        'tooltip' => 'View Timetable',
        'href' => '/TimeTable/timetable',
        'active' => ['timetable'],
        'module' => 'tickets',
    ];


    return $menuStructure;
}

/**
 * Adds Timetable to the personal menu
 * @return string - the string "personal" if the route is TimeTable.timetable.
 */
function addProjectOverviewToPersonalMenuTimeTable(): string
{
    if (FrontcontrollerCore::getCurrentRoute() === 'TimeTable.timetable') {
        return 'personal';
    }
    return '';
}


Events::add_filter_listener("leantime.domain.menu.repositories.menu.getMenuStructure.menuStructures", 'addImportDataMenuPointTimeTable');
Events::add_filter_listener('leantime.domain.menu.repositories.menu.getSectionMenuType', 'addProjectOverviewToPersonalMenuTimeTable');
