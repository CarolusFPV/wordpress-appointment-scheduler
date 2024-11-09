document.addEventListener('DOMContentLoaded', function() {
    let currentDate = new Date();
    const scheduleInterval = parseInt(scheduler_data.interval);
    const ajaxUrl = scheduler_data.ajaxurl;

    // Function to update calendar sections
    function updateCalendarSections(date) {
        const leftDate = new Date(date);
        const middleDate = new Date(date);
        const rightDate = new Date(date);

        leftDate.setDate(leftDate.getDate() - 1); // One day before
        rightDate.setDate(rightDate.getDate() + 1); // One day after

        updateDaySection('left-section', leftDate);
        updateDaySection('middle-section', middleDate);
        updateDaySection('right-section', rightDate);
    }

    // Function to update each day section
    function updateDaySection(sectionId, date) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.dataset.date = date.toISOString();
            section.querySelector('.day-heading').textContent = formatDate(date);
            loadSchedule(date, section);
        }
    }

    // Function to format the date
    function formatDate(date) {
        return date.toLocaleDateString(document.documentElement.lang, {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    // Function to load schedule
    function loadSchedule(date, section) {
        const selectedDate = date.toISOString().split('T')[0];
        console.log(`Loading schedule for: ${selectedDate}`);

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
            console.log("Received data:", data);
            if (data.success) {
                renderTimeSlots(section, data.data.appointments);
            } else {
                console.error('Failed to retrieve appointments:', data);
                renderTimeSlots(section, []); // Render empty slots if failed
            }
        })
        .catch(error => console.error("Error fetching schedule:", error));
    }

    // Function to render time slots
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

        // Add click event listeners to open slots
        document.querySelectorAll('.open-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                const unixTimestamp = this.getAttribute('data-unix');
                console.log(`Open button clicked for timestamp: ${unixTimestamp}`);

                // Convert the UNIX timestamp to a local, user-friendly date string
                const userFriendlyDate = new Date(unixTimestamp * 1000).toLocaleString(); // Local date and time
                console.log(`User-friendly date: ${userFriendlyDate}`);

                // Populate the form with both UNIX timestamp and local date/time
                document.getElementById('appointment_datetime').value = userFriendlyDate; // Displayed in form
                document.getElementById('unix_timestamp').value = unixTimestamp; // For database storage
                document.getElementById('local_datetime').value = userFriendlyDate; // For email display

                // Show the appointment form
                const appointmentFormContainer = document.getElementById('appointment-form-container');
                appointmentFormContainer.style.display = 'block'; // Ensure the form is displayed
                appointmentFormContainer.scrollIntoView(); // Scroll to the form

                // Focus on the first input field
                document.getElementById('user_name').focus();
            });
        });
    }


    // Function to format time for display
    function formatTime(minutes) {
        const hour = String(Math.floor(minutes / 60)).padStart(2, '0');
        const minute = String(minutes % 60).padStart(2, '0');
        return `${hour}:${minute}`;
    }

    // Function to find appointments
    function findAppointment(appointments, minutes) {
        for (const appointment of appointments) {
            const appointmentTime = new Date(appointment.appointment_datetime * 1000); // Local timezone by default
            const appointmentMinutes = appointmentTime.getHours() * 60 + appointmentTime.getMinutes();
    
            if (appointmentMinutes === minutes) {
                return `${appointment.user_name}, ${appointment.city}, ${appointment.country}`;
            }
        }
        return null;
    }

    // Handle form submission
    document.querySelector('.custom-scheduler-form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(this);
        formData.delete('appointment_datetime'); // Remove the datetime value before submission
        formData.append('action', 'submit_appointment'); // Add action to the form data

        const data = new URLSearchParams(formData).toString(); // Convert FormData to URLSearchParams

        console.log("Submitting data:", data); // Log the data being submitted

        fetch(scheduler_data.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Optionally, hide the form or clear it
                document.getElementById('appointment-form-container').style.display = 'none';
            } else {
                console.error('Error scheduling appointment:', data);
                alert('There was an error scheduling your appointment. Please try again.');
            }
        })
        .catch(error => {
            console.error("Error submitting form:", error);
            alert("An error occurred while submitting the form: " + error.message);
        });
    });

    // Initial load for the current date
    updateCalendarSections(currentDate);
});
