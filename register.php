<?php

use Leantime\Plugins\TimeTable\Middleware\GetLanguageAssets;
use Leantime\Core\Events;

/**
 * Adds a menu point for adding fixture data.
 *
 * @param  array<string, array<int, array<string, mixed>>> $menuStructure The existing menu structure to which the new item will be added.
 * @return array<string, array<int, array<string, mixed>>> The modified menu structure with the new item added.
 */
function addTimeTableItemToMenu(array $menuStructure): array
{
    // In the menu array, timesheets occupies spot 15 in the array list, which menupoints are sorted by. Timetable should be right after it.
    $menuStructure['personal'][16] = [
        'type' => 'item',
        'title' => '<span class="fas fa-fw fa-table"></span> Timetable',
        'icon' => 'fa fa-fw fa-table',
        'tooltip' => 'View Timetable',
        'href' => '/TimeTable/timetable',
        'active' => ['Timetable'],
        'module' => 'tickets',
    ];

    return $menuStructure;
}

/**
 * Adds Timetable to the personal menu
 * @param array<string, array<int, array<string, mixed>>> $sections The sections in the menu is to do with which menu is displayed on the current page.
 * @return array<string, string> - the sections array, where TimeTable.timetable is in the "personal" menu.
 */
function displayPersonalMenuOnEnteringTimeTable(array $sections): array
{
    $sections['TimeTable.timetable'] = 'personal';
    return $sections;
}

// https://github.com/Leantime/plugin-template/blob/main/register.php#L43-L46
// Register Language Assets
Events::add_filter_listener(
    'leantime.core.httpkernel.handle.plugins_middleware',
    fn (array $middleware) => array_merge($middleware, [GetLanguageAssets::class]),
);

Events::add_filter_listener('leantime.domain.menu.repositories.menu.getMenuStructure.menuStructures', 'addTimeTableItemToMenu');
Events::add_filter_listener('leantime.domain.menu.repositories.menu.getSectionMenuType.menuSections', 'displayPersonalMenuOnEnteringTimeTable');

Events::add_event_listener(
    'leantime.core.template.tpl.*.afterScriptLibTags',
    function () {
        if (null !== (session('userdata.id'))) {
            echo '<script type="module" src="/dist/js/plugin-timeTableApiHandler.v' . urlencode('%%VERSION%%') . '.js"></script>';
            echo '<script type="module" src="/dist/js/plugin-timeTable.v' . urlencode('%%VERSION%%') . '.js"></script>';
            echo '<link rel="stylesheet" href="/dist/css/plugin-timeTable.v' . urlencode('%%VERSION%%') . '.css"></link>';
        }
    },
    5
);
