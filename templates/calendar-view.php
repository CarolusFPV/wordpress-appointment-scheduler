<style type="text/css">
    <?php echo esc_html(get_option('custom_calendar_css', '')); ?>
</style>

<div class="custom-scheduler-calendar">
    <div class="calendar-navigation">
        <button class="nav-arrow" id="prev-day">&lt;</button>
        <input type="date" id="jump-to-date" class="calendar-jump-button" value="<?php echo date('Y-m-d'); ?>">
        <button class="nav-arrow" id="next-day">&gt;</button>
    </div>
    <div id="day-display">
        <div class="day-section" id="left-section">
            <h4 class="day-heading" style="text-align: center;"></h4>
            <table class="day-schedule-table"></table>
        </div>
        <div class="day-section" id="middle-section">
            <h4 class="day-heading" style="text-align: center;"><?php echo date('Y-m-d'); ?></h4>
            <table class="day-schedule-table"></table>
        </div>
        <div class="day-section" id="right-section">
            <h4 class="day-heading" style="text-align: center;"></h4>
            <table class="day-schedule-table"></table>
        </div>
    </div>
</div>
