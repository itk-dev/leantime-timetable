@extends($layout)

@section('content')
    <div class="time-table-container">
        <div class="flex-container">
            <h1 class="h1"><i class="fa fa-clock"></i> {{ __('timeTable.dashboard_title') }}</h1>
            <div>
                <button type="button" onclick="changeWeek(-1)"><i class="fa fa-arrow-left"></i> {{ __('timeTable.button_prev_week') }}</button>
                <button type="button" onclick="changeWeek(1)">{{ __('timeTable.button_next_week') }} <i class="fa fa-arrow-right"></i></button>
                <button class="new-button" type="button" onclick="openEditTimeLogModal()">{{ __('timeTable.button_add_time_log') }} <i class="fa fa-plus"></i></button>
            </div>
        </div>
        <div class="search-bar">
            <label class="sr-only">{{ __('timeTable.search_label') }}</label>
            <input value="{!! $currentSearchTerm !!}" type="text" class="search-input"
                onchange="redirectWithSearchTerm(this.value)" placeholder="{{ __('timeTable.empty_search_label') }}" />
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">{{ __('timeTable.id_table_header') }}</th>
                    <th scope="col">{{ __('timeTable.title_table_header') }}</th>
                    <?php
                    $i = 0;
                    foreach ($weekDays as $key => $day) { ?>
                    <th>
                        {{ $day }}
                        <?php
                        echo $weekDates[$key]->format('Y-m-d');
                        $i++;
                        ?>
                    </th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                @foreach ($timesheetsByTicket as $key => $timesheet)
                    <tr>
                        <td scope="row">{{ $timesheet['ticketId'] }}</td>
                        <td scope="row">{{ $timesheet['ticketTitle']  }}</td>
                        @foreach ($weekDates as $weekDate)
                            <?php
                            $weekDateAccessor = $weekDate->format('Y-m-d');
                            $hours = $timesheet[$weekDateAccessor][0]['hours'];
                            $id = $timesheet[$weekDateAccessor][0]['id'];
                            $description = $timesheet[$weekDateAccessor][0]['description'];
                            ?>
                            <td scope="row">
                                <input
                                    onclick="openEditTimeLogModal('{{$id}}', '{{ $key }}', '{{ $hours }}', `{{ $description }}`, '{{$weekDate->format('Y/m/d')}}')"
                                    type="number" value="{{ $hours }}" />
                                @if (isset($hours) && $description === '')
                                    <span class="fa fa-circle-exclamation"></span>
                                @endif

                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Modal for editing work logs --}}
        <div id="edit-time-log-modal" class="nyroModalBg edit-time-log-modal">
            <form method="post" id="modal-form" class="modal-content">

                {{-- Hidden properties for post --}}
                <input type="hidden" name="timesheet-ticket-id" />
                <input type="hidden" name="timesheet-date" />
                <input type="hidden" name="timesheet-id" />
                <input type="hidden" name="timesheet-description" />
                <input type="hidden" name="timesheet-hours" />
                <input type="hidden" name="timesheet-offset" />

                {{-- todo obviously this wont do... --}}
                <input type="date">

                {{-- copy paste from https://www.w3schools.com/howto/howto_js_filter_dropdown.asp - also entries in timeTable.css and timeTable.js --}}
                <div class="dropdown">
                    <button onclick="myFunction(event)" class="dropbtn">Dropdown</button>
                    <div id="myDropdown" class="dropdown-content">
                        {{-- todo create string for "search.." --}}
                      <input type="text" placeholder="Search.." id="myInput" onkeyup="filterFunction(event)">
                      {{-- todo remove these obviouslu --}}
                      <a href="#about">About</a>
                      <a href="#base">Base</a>
                      <a href="#blog">Blog</a>
                      <a href="#contact">Contact</a>
                      <a href="#custom">Custom</a>
                      <a href="#support">Support</a>
                      <a href="#tools">Tools</a>
                    </div>
                  </div>

                {{-- Hours input --}}
                <input type="number" onchange="changeHours(this.value)" step="0.01" id="modal-hours" placeholder="Timer" required />
                <span class="fa fa-clock"></span>

                {{-- Description input --}}
                <input type="text" id="modal-description" placeholder="Beskrivelse" onchange="changeDescription(this.value)" required />

                {{-- Save or cancel buttons --}}
                <div class="buttons">
                    <button class="cancel-button" onclick="closeEditTimeLogModal(event)">{{ __('timeTable.button_modal_close') }}</button>
                    <input type="submit" class="save-button" value="{{ __('timeTable.button_modal_save') }}" />
                </div>
            </form>

        </div>
    </div>
@endsection
