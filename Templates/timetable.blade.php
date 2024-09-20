@extends($layout)
@section('content')
    <div class="time-table-container">
        <input type="hidden" name="timetable-ticket-ids" value="{{$ticketIds}}" />
        <input type="hidden" id="timetable-ticketCacheExpiration" name="timetable-ticket-cache" value="{{$ticketCacheExpiration}}" />
        <div class="flex-container">
            <div>
                <button type="button" class="timetable-week-prev"><i class="fa fa-arrow-left"></i> {{ __('timeTable.button_prev_week') }}</button>
                <button type="button" class="timetable-week-next">{{ __('timeTable.button_next_week') }} <i class="fa fa-arrow-right"></i></button>
                <button class="timetable-new-entry" type="button">{{ __('timeTable.button_add_time_log') }} <i class="fa fa-plus"></i></button>
            </div>
        </div>
        <table id="timetable" class="table">
            <thead>
            <tr>
                <th class="th-ticket-title" scope="col">{{ __('timeTable.title_table_header') }}</th>

                @if (isset($weekDays, $weekDates))
                    <input type="hidden" name="timetable-current-week-first-day" value="<?php echo isset($weekDates[0]) ? $weekDates[0]->format('Y-m-d') : ''; ?>" />
                    <input type="hidden" name="timetable-current-week" value="<?php echo isset($weekDates[0]) ? $weekDates[0]->format('W') : ''; ?>" />
                    @foreach ($weekDays as $key => $day)
                        @php
                            $weekDate = $weekDates[$key];
                            $weekendClass = $weekDate->isWeekend() ? 'weekend' : '';
                            $todayClass = $weekDate->isToday() ? 'today' : '';
                            $classes = trim("$weekendClass $todayClass");
                        @endphp
                        <th @if($classes) class="{{ $classes }}" @endif>
                            {{ $weekDate->format('d. D') }}
                        </th>
                    @endforeach
                    <th>Total</th> <!-- new column for row totals -->
                @endif
            </tr>
            </thead>
            <tbody>
            <?php $totalHours = array(); ?>
            @foreach ($timesheetsByTicket as $ticketId => $timesheet)
                    <?php $rowTotal = 0; ?> <!-- initializing row total -->
                <tr>
                    <td class="ticket-title" scope="row"><a href="{{$timesheet['ticketLink']}}">{{ $timesheet['ticketTitle']  }}</a> <span>{{$timesheet['projectName']}}</span></td>
                    @foreach ($weekDates as $weekDate)
                            <?php
                            $weekDateAccessor = isset($weekDate) ? $weekDate->format('Y-m-d') : null;
                            $timesheetDate = isset($timesheet) ? $timesheet[$weekDateAccessor] : null;

                            $id = $timesheetDate[0]['id'] ?? null;
                            $hours = $timesheetDate[0]['hours'] ?? null;
                            $description = $timesheetDate[0]['description'] ?? null;

                            // accumulate hours
                            if ($hours) {
                                if (isset($totalHours[$weekDateAccessor])) {
                                    $totalHours[$weekDateAccessor] += $hours;
                                } else {
                                    $totalHours[$weekDateAccessor] = $hours;
                                }
                                $rowTotal += $hours; // add to row total
                            }

                            $weekendClass = (isset($weekDate) && $weekDate->isWeekend()) ? 'weekend' : '';
                            $todayClass = (isset($weekDate) && $weekDate->isToday()) ? 'today' : '';
                            ?>
                        <td scope="row" class="timetable-edit-entry {{$weekendClass}} {{$todayClass}}" data-id="{{$id}}" data-ticketid="{{ $ticketId }}" data-hours="{{ $hours }}" data-description="{{ $description }}" data-date="{{$weekDate->format('Y-m-d')}}" title="{{ $description }}">
                            <span>{{ $hours }}</span>
                            @if (isset($hours) && $description === '')
                                <span class="fa fa-circle-exclamation"></span>
                            @endif
                        </td>
                    @endforeach
                    <td>{{ $rowTotal }}</td> <!-- displaying row total -->
                </tr>
            @endforeach
            <!-- add total hours row here -->
            <tr class="tr-total">
                <td scope="row">Total</td>
                @foreach ($weekDates as $weekDate)
                    <td> {{ $totalHours[$weekDate->format('Y-m-d')] ?? 0 }} </td>
                @endforeach
                <td>{{ array_sum($totalHours) }}</td> <!-- displaying grand total -->
            </tr>
            </tbody>
        </table>
        {{-- Modal for editing work logs --}}

        <div id="edit-time-log-modal" class="nyroModalBg edit-time-log-modal">
            <form method="post" class="edit-time-log-form">
                <div class="timetable-close-modal">
                    <span>×</span>
                </div>
                {{-- Hidden properties for post --}}
                <input type="hidden" name="timesheet-ticket-id" />
                <input type="hidden" name="timesheet-id" />
                <input type="hidden" name="timesheet-offset" />

                {{-- todo obviously this wont do... --}}
                <input type="date" name="timesheet-date">

                {{-- copy paste from https://www.w3schools.com/howto/howto_js_filter_dropdown.asp - also entries in timeTable.css and timeTable.js --}}
                <div class="timetable-ticket-search">
                    <input class="timetable-ticket-input" type="text" data-placeholder="Search tickets.." data-loading="Filtering tickets.." placeholder="Search todo.." />
                    <div class="timetable-ticket-results"></div>
                  </div>

                {{-- Hours input --}}
                <input type="number" name="timesheet-hours" step="0.01" placeholder="Timer" required />

                {{-- Description input --}}
                <textarea type="text" id="modal-description" name="timesheet-description" placeholder="Beskrivelse" required></textarea>

                {{-- Save or cancel buttons --}}
                <div class="buttons">
                    <button type="button" class="timetable-modal-cancel">{{ __('timeTable.button_modal_close') }}</button>
                    <button type="submit" class="timetable-modal-submit">{{__('timeTable.button_modal_save')}}</button>
                </div>
            </form>
            <div class="timetable-sync-panel">
                <div><button class="timetable-sync-tickets"><span><i class="fa-solid fa-arrows-rotate"></i>Sync data</span></button></div><div><span></span></div>
            </div>
        </div>
    </div>
@endsection
