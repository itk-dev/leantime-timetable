@extends($layout)
@section('content')
    <!-- page header -->
    <div class="pageheader">
        <div class="pageicon"><span class="fa-regular fa-clock"></span></div>
        <div class="pagetitle">
            <h5>{{ __('label.table-columns') }}</h5>
            <h1>{{ __('timeTable.headline') }}</h1>
        </div>
    </div>
    <!-- page header -->
    <div class="maincontent">
        <div class="maincontentinner">
            <div class="timetable">
                <form method="POST">
                    <input type="hidden" name="action" value="adjustPeriod">
                    <div class="flex-container gap-3 tools">
                        <button type="submit" name="backward" value="1" class="timetable-week-prev btn btn-default">
                            <i class="fa fa-arrow-left"></i> {{ __('timeTable.button_prev_period') }}
                        </button>
                        <input type="text" name="dateRange" id="dateRange"
                            value="{{ $fromDate->format('d-m-Y') }} til {{ $toDate->format('d-m-Y') }}">
                        <button type="submit" name="forward" value="1" class="timetable-week-next btn btn-default">
                            {{ __('timeTable.button_next_period') }} <i class="fa fa-arrow-right"></i>
                        </button>
                        <button type="submit" name="showThisWeek" value="1"
                            class="timetable-to-today btn btn-default">{{ __('timeTable.button_show_this_week') }}</button>
                    </div>
                </form>
                <div class="timetable-scroll-container">
                    <table id="timetable" class="table">
                        <thead>
                            <tr>
                                <th class="th-ticket-title" scope="col">{{ __('timeTable.title_table_header') }}</th>
                                @if (isset($weekDays, $weekDates) && count($weekDates))
                                    <input type="hidden" name="timetable-current-week-first-day"
                                        value="{{ reset($weekDates)->format('Y-m-d') }}" />
                                    <input type="hidden" name="timetable-current-week-last-day"
                                        value="{{ end($weekDates)->format('Y-m-d') }}" />
                                    <input type="hidden" name="timetable-days-loaded" value="{{ count($weekDates) }}" />
                                    <input type="hidden" name="timetable-current-week"
                                        value="{{ reset($weekDates)->format('W') }}" />

                                    @foreach ($weekDates as $date => $day)
                                        @php
                                            $weekendClass = $day->isWeekend() ? 'weekend' : '';
                                            $todayClass = $day->isToday() ? 'today' : '';
                                            $newWeekClass = $day->isMonday() ? 'new-week' : '';
                                            $classes = trim("$weekendClass $todayClass $newWeekClass");
                                        @endphp
                                        <th @if ($classes) class="{{ $classes }}" @endif
                                            @if ($day->isMonday())
                                            data-week="{{ $day->weekOfYear }}"
                                    @endif>
                                    <div> <small>{{ $day->format('j/n') }}</small>
                                        <span>{{ $day->format('D') }}</span>
                                    </div>
                                    </th>
                                @endforeach
                                <th scope="col"><span>Total</span></th> <!-- Total Column Header -->
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <?php $totalHours = []; ?>
                            @if (!empty($timesheetsByTicket))
                                @foreach ($timesheetsByTicket as $ticketId => $timesheet)
                                    <tr data-ticketId="{{ $ticketId }}">
                                        <td class="ticket-title" scope="row"><a href="{{ $timesheet['ticketLink'] }}"
                                                data-tippy-content="#{{ $timesheet['ticketId'] }} - {{ $timesheet['ticketTitle'] }}"
                                                data-tippy-placement="top">{{ $timesheet['ticketTitle'] }}</a>
                                            <span>{{ $timesheet['projectName'] }}</span>
                                            <?php if ($timesheet['ticketType'] !== "task"): ?>
                                            <small>(<?php echo $timesheet['ticketType']; ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <?php $rowTotal = 0; ?>
                                        <!-- initializing row total -->
                                        @foreach ($weekDates as $weekDate)
                                            <?php
                                            $weekDateAccessor = isset($weekDate) ? $weekDate->format('Y-m-d') : null;
                                            $timesheetDate = isset($timesheet) ? $timesheet[$weekDateAccessor] : null;
                                            $id = $timesheetDate[0]['id'] ?? null;
                                            $hours = $timesheetDate[0]['hours'] ?? null;
                                            $hoursLeft = $timesheetDate[0]['hourRemaining'] ?? null;
                                            $description = $timesheetDate[0]['description'] ?? null;
                                            $isMissingDescription = isset($hours) && trim($description) === '';
                                            
                                            // accumulate hours
                                            if ($hours) {
                                                if (isset($totalHours[$weekDateAccessor])) {
                                                    $totalHours[$weekDateAccessor] += $hours;
                                                } else {
                                                    $totalHours[$weekDateAccessor] = $hours;
                                                }
                                                $rowTotal += $hours; // add to row total
                                            }
                                            
                                            $weekendClass = isset($weekDate) && $weekDate->isWeekend() ? 'weekend' : '';
                                            $todayClass = isset($weekDate) && $weekDate->isToday() ? 'today' : '';
                                            $newWeekClass = isset($weekDate) && $weekDate->isMonday() ? 'new-week' : ''; // Add new-week class for Mondays
                                            ?>
                                            <td scope="row"
                                                class="timetable-edit-entry {{ $weekendClass }} {{ $todayClass }} {{ $newWeekClass }} {{ $isMissingDescription ? 'description-missing' : '' }}"
                                                data-id="{{ $id }}" data-ticketid="{{ $ticketId }}"
                                                data-hours="{{ $hours }}" data-hoursleft="{{ $hoursLeft }}"
                                                data-description="{{ $description }}"
                                                data-date="{{ $weekDate->format('Y-m-d') }}"
                                                title="{{ $isMissingDescription ? __('timeTable.description_missing') : '' }}">
                                                <span>{{ $hours }}</span>
                                                @if (!is_null($hours))
                                                    <div class="entry-copy-button"><i class="fa-solid fa-angle-right"></i>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>{{ $rowTotal }}</td> <!-- Row Total Column -->
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="add-new"><input class="timetable-tomselect form-control-lg"
                                            placeholder="Syncing data" /></td>
                                    @foreach ($weekDates as $date)
                                        <td class="{{ $date->isMonday() ? 'new-week' : '' }}">—</td>
                                    @endforeach
                                    <td>—</td>
                                </tr>
                            @else
                                <!-- A little something for when the week has no logs -->
                                <tr class="empty-row"">
                                                    <td class=" empty-row" colspan="{{ count($weekDates) + 2 }}">
                                    {{ __("It seems the 'WORK-IT' fairy forgot to sprinkle her magic dust here! 🧚‍🪄✨") }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="add-new"><input class="timetable-tomselect form-control-lg"
                                            placeholder="Syncing data" /></td>
                                    @foreach ($weekDates as $date)
                                        <td>—</td>
                                    @endforeach
                                    <td>—</td>
                                </tr>
                            @endif
                            <!-- add total hours row here -->
                            <tr class="tr-total">
                                <td scope="row">Total</td>
                                @foreach ($weekDates as $weekDate)
                                    <td class="{{ $weekDate->isMonday() ? 'new-week' : '' }}">
                                        {{ $totalHours[$weekDate->format('Y-m-d')] ?? 0 }}
                                    </td>
                                @endforeach
                                <td>{{ array_sum($totalHours) }}</td> <!-- Grand Total Column -->
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="timetable-sync-panel">
                <div>
                    <button class="timetable-sync-tickets"><span><i class="fa-solid fa-arrows-rotate"></i>Sync data</span>
                    </button>
                </div>
                <div><span></span></div>
            </div>
        </div>
    </div>

    {{-- Modal for editing work logs --}}

    <div id="edit-time-log-modal" class="timetable edit-time-log-modal">
        <form method="POST" class="edit-time-log-form">
            <input type="hidden" name="action" value="saveTicket">
            {{-- Hidden properties for post --}}
            <input type="hidden" name="timesheet-ticket-id" />
            <input type="hidden" name="timesheet-id" />
            <input type="hidden" name="timesheet-offset" />

            <input type="hidden" name="timesheet-date">

            <input type="hidden" class="fromdate-input" name="fromDate" value="{{ $fromDate->format('Y-m-d') }}"
                onchange="submit()" />
            <input type="hidden" class="todate-input" name="toDate" value="{{ $toDate->format('Y-m-d') }}"
                onchange="submit()" />

            <div class="timetable-hours">
                <div class="timesheet-input-wrapper">
                    <input type="number" name="timesheet-hours" step="0.01" placeholder="{{ __('timeTable.hours') }}"
                        required />
                    <div title="{{ __('timeTable.hours_left') }}" class="timetable-hours-left">
                        <input type="number" name="timesheet-hours-left" disabled="disabled" />
                    </div>
                </div>
            </div>


            {{-- Description input --}}
            <div class="description-wrapper">
                <textarea type="text" id="modal-description" name="timesheet-description"
                    placeholder="{{ __('timeTable.description') }}" required></textarea>
            </div>

            {{-- Save or cancel buttons --}}
            <div class="buttons flex-container gap-1">
                <button type="button" class="timetable-modal-delete btn btn-danger"
                    data-loading="{{ __('timeTable.button_modal_deleting') }}"><i class="fa fa-trash"></i></button>
                <button type="button"
                    class="timetable-modal-cancel btn btn-default ml-auto">{{ __('timeTable.button_modal_close') }}</button>
                <button type="submit"
                    class="timetable-modal-submit btn btn-primary">{{ __('timeTable.button_modal_save') }}</button>
            </div>
        </form>
    </div>
    <div id="entry-copy-modal">
        <form method="POST" class="entry-copy-form">
            <input type="hidden" name="action" value="copyEntryForward">
            <input type="hidden" name="entryCopyTicketId" class="entry-copy-ticketId">
            <input type="hidden" name="entryCopyHours" class="entry-copy-hours">
            <input type="hidden" name="entryCopyDescription" class="entry-copy-description">
            <input type="hidden" name="entryCopyFromDate" />
            <input type="hidden" name="entryCopyToDate" />
            <p class="entry-copy-headline"></p>
            <p class="entry-copy-text"></p>
            <div class="entry-copy-overwrite-checkbox">
                <input type="checkbox" name="entryCopyOverwrite" id="entry-copy-overwrite" />
                <label for="entry-copy-overwrite"><small>Overskriv allerede registrerede felter</small></label>
            </div>
            <div class="buttons flex-container gap-1">
                <button type="button"
                    class="entry-copy-modal-cancel btn btn-default ml-auto">{{ __('timeTable.entry_copy_button_close') }}</button>
                <button type="submit"
                    class="entry-copy-modal-apply btn btn-primary">{{ __('timeTable.entry_copy_button_apply') }}</button>
            </div>

        </form>
    </div>
    <div id="edit-time-sync-modal" class="nyroModalBg edit-time-sync-modal">
        <div><span><i
                    class="fa-solid fa-spinner fa-2xl fa-spin"></i></span><span>{{ __('timeTable.synchronizing') }}</span>
        </div>
    </div>
@endsection
