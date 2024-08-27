@extends($layout)

@section('content')
    <div class="time-table-container">
    <input type="hidden" name="timetable-ticket-ids" value="{{$ticketIds}}" />
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
                    <th style="width:60%;" scope="col">{{ __('timeTable.title_table_header') }}</th>

                    <?php
                    $i = 0;
                    if (isset($weekDays) && isset($weekDates)){
                    foreach ($weekDays as $key => $day) { ?>
                    <th data-hest="{{$weekDates[$key]}}">
                        <?php
                        echo $weekDates[$key]->format('d. D');
                        $i++;
                        ?>
                    </th>

                    <?php }} ?>

                </tr>
            </thead>
            <tbody>
            <?php $totalHours = array(); ?>
            @foreach ($timesheetsByTicket as $ticketId => $timesheet)
                <tr>
                    <td class="ticket-title" scope="row"><a href="{{$timesheet['ticketLink']}}">{{ $timesheet['ticketTitle']  }}</a> <span>{{$timesheet['projectName']}}</span></td>
                    @if (isset($weekDates))
                        @foreach ($weekDates as $weekDate)
                                <?php
                                $weekDateAccessor = isset($weekDate) ? $weekDate->format('Y-m-d') : null;
                                $hours = isset($timesheet) ? $timesheet[$weekDateAccessor][0]['hours'] : null;

                                // accumulate hours
                                if ($hours) {
                                    if (isset($totalHours[$weekDateAccessor])) {
                                        $totalHours[$weekDateAccessor] += $hours;
                                    } else {
                                        $totalHours[$weekDateAccessor] = $hours;
                                    }
                                }
                                $id = isset($timesheet) ? $timesheet[$weekDateAccessor][0]['id'] : null;
                                $description = isset($timesheet) ? $timesheet[$weekDateAccessor][0]['description'] : null;
                                ?>
                            <td scope="row" class="timetable-edit-entry" data-id="{{$id}}" data-ticketid="{{ $ticketId }}" data-hours="{{ $hours }}" data-description="{{ $description }}" data-date="{{$weekDate->format('Y-m-d')}}">
                                <span>{{ $hours }}</span>
                                @if (isset($hours) && $description === '')
                                    <span class="fa fa-circle-exclamation"></span>
                                @endif

                            </td>
                        @endforeach
                    @endif
                </tr>
            @endforeach
            <!-- add total hours row here -->
            <tr class="tr-total">
                <td scope="row">Total</td>
                @foreach ($weekDates as $weekDate)
                    <td> {{ $totalHours[$weekDate->format('Y-m-d')] ?? 0 }} </td>
                @endforeach
            </tr>
            </tbody>
        </table>
        {{-- Modal for editing work logs --}}

        <div id="edit-time-log-modal" class="nyroModalBg edit-time-log-modal">
            <form method="post" id="modal-form" class="modal-content">
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
                    <button type="submit" class="save-button">{{__('timeTable.button_modal_save')}}</button>
                </div>
            </form>
            <div class="timetable-sync-panel">
                <div><button class="timetable-sync-tickets"><span><i class="fa-solid fa-arrows-rotate"></i>Sync data</span></button></div><div><span></span></div>
            </div>
        </div>
    </div>
@endsection
