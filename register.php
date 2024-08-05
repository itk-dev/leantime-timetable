<?php

use Leantime\Core\Events;

/**
 * Adds a menu point for adding fixture data.
 * @param array<string, array<int, array<string, mixed>>> $menuStructure The existing menu structure to which the new item will be added.
 * @return array<string, array<int, array<string, mixed>>> The modified menu structure with the new item added.
 */
function addImportDataMenuPointTimeTable(array $menuStructure): array
{
    $menuStructure['personal'][16] = [
        'type' => 'item',
        'module' => 'timetable',
        'title' => '<span class="fas fa-fw fa-table"></span> Timetable',
        'icon' => 'fa fa-fw fa-table',
        'tooltip' => 'View Timetable',
        'href' => '/TimeTable/timetable',
        'active' => ['settings'],
    ];


    return $menuStructure;
}


Events::add_filter_listener("leantime.domain.menu.repositories.menu.getMenuStructure.menuStructures", 'addImportDataMenuPointTimeTable');
