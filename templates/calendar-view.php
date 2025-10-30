<style>
.add-slot {
    background-color: #0073aa;
    color: white;
    border: none;
    border-radius: 3px;
    padding: 4px 4px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    margin-left: 5px;
    float: right;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.add-slot:hover {
    background-color: #005a87;
}

.open-slot {
    background-color: #0073aa;
    color: white;
    border: none;
    border-radius: 3px;
    padding: 4px 12px;
    cursor: pointer;
    font-size: 14px;
}

.open-slot:hover {
    background-color: #005a87;
}

/* Ensure proper layout for appointment cells with plus buttons */
.day-schedule-table td:last-child {
    position: relative;
    overflow: hidden;
}

.day-schedule-table td:last-child::after {
    content: "";
    display: table;
    clear: both;
}
</style>

<div class="custom-scheduler-calendar">
    <div class="calendar-navigation">
        <button class="nav-arrow" id="prev-day">&lt;</button>
        <input type="date" id="jump-to-date" class="calendar-jump-button" value="<?php echo date('Y-m-d'); ?>">
        <button class="nav-arrow" id="next-day">&gt;</button>
    </div>

    <div class="timezone-display">
        <span id="user-timezone"></span>
        <span id="current-time"></span>
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
