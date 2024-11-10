document.addEventListener('DOMContentLoaded', function() {
    const timezoneElement = document.getElementById('user-timezone');
    const timeElement = document.getElementById('current-time');

    // Function to update the current time
    function updateTime() {
        const currentDate = new Date();
        const options = { hour: '2-digit', minute: '2-digit', hour12: false };
        const currentTimeString = currentDate.toLocaleTimeString(document.documentElement.lang, options);
    
        const offset = currentDate.getTimezoneOffset() / -60;
        const utcOffsetString = `UTC${offset >= 0 ? '+' : ''}${offset}`;
    
        timezoneElement.textContent = `${Intl.DateTimeFormat().resolvedOptions().timeZone}`;
        timeElement.textContent = `${currentTimeString} (${utcOffsetString})`;
    }

    updateTime();
    setInterval(updateTime, 60000);

    let currentDate = new Date();
    const scheduleInterval = parseInt(scheduler_data.interval);
    const ajaxUrl = scheduler_data.ajaxurl;

    // Update calendar sections for the given date
    function updateCalendarSections(date) {
        const leftDate = new Date(date);
        const middleDate = new Date(date);
        const rightDate = new Date(date);

        leftDate.setDate(leftDate.getDate() - 1);
        rightDate.setDate(rightDate.getDate() + 1);

        updateDaySection('left-section', leftDate);
        updateDaySection('middle-section', middleDate);
        updateDaySection('right-section', rightDate);
    }

    function updateDaySection(sectionId, date) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.dataset.date = date.toISOString();
            section.querySelector('.day-heading').textContent = formatDate(date);
            loadSchedule(date, section);
        }
    }

    function formatDate(date) {
        return date.toLocaleDateString(document.documentElement.lang, {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    function loadSchedule(date, section) {
        const selectedDate = date.toISOString().split('T')[0];
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'get_schedule',
                selected_date: selectedDate
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTimeSlots(section, data.data.appointments);
            } else {
                renderTimeSlots(section, []);
            }
        })
        .catch(error => console.error("Error fetching schedule:", error));
    }

    function renderTimeSlots(section, appointments) {
        const table = section.querySelector('.day-schedule-table');
        if (!table) return;

        let timeSlotsHtml = '';
        const totalMinutesInDay = 24 * 60;

        for (let minutes = 0; minutes < totalMinutesInDay; minutes += scheduleInterval) {
            const time = formatTime(minutes);
            const unixTime = Math.floor(new Date(section.dataset.date).setHours(Math.floor(minutes / 60), minutes % 60, 0) / 1000);
            const appointmentText = findAppointment(appointments, minutes) || `<button class="open-slot" data-unix="${unixTime}">Open</button>`;
            timeSlotsHtml += `<tr><td>${time}</td><td>${appointmentText}</td></tr>`;
        }

        table.innerHTML = timeSlotsHtml;

        document.querySelectorAll('.open-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                const unixTimestamp = this.getAttribute('data-unix');
                const userFriendlyDate = new Date(unixTimestamp * 1000).toLocaleString();
                document.getElementById('appointment_datetime').value = userFriendlyDate;
                document.getElementById('unix_timestamp').value = unixTimestamp;
                document.getElementById('local_datetime').value = userFriendlyDate;

                const appointmentFormContainer = document.getElementById('appointment-form-container');
                appointmentFormContainer.style.display = 'block';
                appointmentFormContainer.scrollIntoView();
                document.getElementById('user_name').focus();
            });
        });
    }

    function formatTime(minutes) {
        const hour = String(Math.floor(minutes / 60)).padStart(2, '0');
        const minute = String(minutes % 60).padStart(2, '0');
        return `${hour}:${minute}`;
    }

    function findAppointment(appointments, minutes) {
        for (const appointment of appointments) {
            const appointmentTime = new Date(appointment.appointment_datetime * 1000);
            const appointmentMinutes = appointmentTime.getHours() * 60 + appointmentTime.getMinutes();
    
            if (appointmentMinutes === minutes) {
                return `${appointment.user_name}, ${appointment.city}, ${appointment.country}`;
            }
        }
        return null;
    }

    // Add event listeners for date navigation
    document.getElementById('prev-day').addEventListener('click', function() {
        currentDate.setDate(currentDate.getDate() - 1);
        document.getElementById('jump-to-date').value = currentDate.toISOString().split('T')[0];
        updateCalendarSections(currentDate);
    });

    document.getElementById('next-day').addEventListener('click', function() {
        currentDate.setDate(currentDate.getDate() + 1);
        document.getElementById('jump-to-date').value = currentDate.toISOString().split('T')[0];
        updateCalendarSections(currentDate);
    });

    // Handle form submission
    document.querySelector('.custom-scheduler-form').addEventListener('submit', function(event) {
        event.preventDefault();
    
        const formData = new FormData(this);
        formData.delete('appointment_datetime');
        formData.append('action', 'submit_appointment');
    
        fetch(scheduler_data.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            const formContainer = document.getElementById('appointment-form-container');
    
            // Replace the form content with the success or error message
            if (data.success) {
                const formattedMessage = data.data.message.replace(/\n/g, '<br>');
                formContainer.innerHTML = `<div class="appointment-message">${formattedMessage}</div>`;
            } else {
                const formattedMessage = (data.data.message || 'There was an error scheduling your appointment. Please try again.').replace(/\n/g, '<br>');
                formContainer.innerHTML = `<div class="appointment-message">${formattedMessage}</div>`;
            }
        })
        .catch(error => {
            alert("An error occurred while submitting the form: " + error.message);
        });
    });    

    updateCalendarSections(currentDate);
});
