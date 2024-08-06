@extends($layout)

@section('content')

<x-global::pageheader :icon="'fa fa-table'">
    <h1>Timetable</h1>
</x-global::pageheader>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@event-calendar/build@2.6.1/event-calendar.min.css">
<script src="https://cdn.jsdelivr.net/npm/@event-calendar/build@2.6.1/event-calendar.min.js"></script>

<link href="<?php echo $tpl->get('timeTableStyle'); ?>" />
<script type="module" src="<?php echo $tpl->get('timeTableScript') ?>"></script>


<div class="maincontent">
<?php echo $tpl->displayNotification(); ?>
    <div class="maincontentinner">
        <h1>Hello World!</h1>
    </div>
</div>
@endsection
