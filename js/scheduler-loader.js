document.addEventListener('DOMContentLoaded', function() {
    const timezoneElement = document.getElementById('user-timezone');
    const timeElement = document.getElementById('current-time');

    // DST Detection and Handling Functions
    function getLastDSTChangeDate() {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        
        // Detect user's timezone and use appropriate DST rules
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        
        // US DST changes: 2nd Sunday in March (spring forward) and 1st Sunday in November (fall back)
        if (timezone.startsWith('America/')) {
            const marchSecondSunday = getNthSundayOfMonth(currentYear, 2, 2); // March, 2nd Sunday
            const novemberFirstSunday = getNthSundayOfMonth(currentYear, 10, 1); // November, 1st Sunday
            
            // If we're before March change, use last year's November change
            if (currentDate < marchSecondSunday) {
                return getNthSundayOfMonth(currentYear - 1, 10, 1);
            }
            // If we're between March and November, use March change
            else if (currentDate < novemberFirstSunday) {
                return marchSecondSunday;
            }
            // If we're after November, use November change
            else {
                return novemberFirstSunday;
            }
        }
        // European DST changes: last Sunday in March (spring forward) and last Sunday in October (fall back)
        else {
            const marchLastSunday = getLastSundayOfMonth(currentYear, 2); // March (month 2)
            const octoberLastSunday = getLastSundayOfMonth(currentYear, 9); // October (month 9)
            
            // If we're before March change, use last year's October change
            if (currentDate < marchLastSunday) {
                return getLastSundayOfMonth(currentYear - 1, 9);
            }
            // If we're between March and October, use March change
            else if (currentDate < octoberLastSunday) {
                return marchLastSunday;
            }
            // If we're after October, use October change
            else {
                return octoberLastSunday;
            }
        }
    }
    
    function getLastSundayOfMonth(year, month) {
        const lastDay = new Date(year, month + 1, 0); // Last day of the month
        const lastSunday = new Date(lastDay);
        lastSunday.setDate(lastDay.getDate() - lastDay.getDay());
        return lastSunday;
    }
    
    function getNthSundayOfMonth(year, month, nth) {
        const firstDay = new Date(year, month, 1);
        const firstSunday = new Date(firstDay);
        firstSunday.setDate(1 + (7 - firstDay.getDay()) % 7); // First Sunday of the month
        const nthSunday = new Date(firstSunday);
        nthSunday.setDate(firstSunday.getDate() + (nth - 1) * 7);
        return nthSunday;
    }
    
    function isDSTTransitionDay(date) {
        const nextDay = new Date(date);
        nextDay.setDate(nextDay.getDate() + 1);
        return date.getTimezoneOffset() !== nextDay.getTimezoneOffset();
    }
    
    function isDSTTransitionHour(date, hour) {
        if (!isDSTTransitionDay(date)) return false;
        
        // Check if this is the problematic hour (usually 2 AM)
        return hour === 2;
    }
    
    function getDSTPeriod(date) {
        const january = new Date(date.getFullYear(), 0, 1);
        const july = new Date(date.getFullYear(), 6, 1);
        return date.getTimezoneOffset() < Math.max(january.getTimezoneOffset(), july.getTimezoneOffset()) 
            ? 'zomertijd' : 'wintertijd';
    }
    
    function shouldShowDSTMarker(appointmentDate, currentDate) {
        const dstChangeDate = getLastDSTChangeDate();
        const oneMonthAfter = new Date(dstChangeDate);
        oneMonthAfter.setMonth(oneMonthAfter.getMonth() + 1);
        
        return appointmentDate < dstChangeDate && currentDate < oneMonthAfter;
    }
    
    function addDSTInfoMarker(appointmentTime, appointmentDate) {
        const currentDate = new Date();
        if (shouldShowDSTMarker(appointmentDate, currentDate)) {
            const appointmentDSTPeriod = getDSTPeriod(appointmentDate);
            const currentDSTPeriod = getDSTPeriod(currentDate);
            
            // Only show marker if the appointment was scheduled in a different DST period
            if (appointmentDSTPeriod !== currentDSTPeriod) {
                const alternativeTime = getTimeInAlternativeDST(appointmentTime, appointmentDate);
                return `<span class="dst-info" title="Gepland in ${appointmentDSTPeriod}. ${currentDSTPeriod} tijd: ${alternativeTime}">ℹ️</span>`;
            }
        }
        return '';
    }
    
    function getTimeInAlternativeDST(unixTimestamp, originalDate) {
        // Get the time in the alternative DST context
        const appointmentTime = new Date(unixTimestamp * 1000);
        const currentDate = new Date();
        
        // Calculate what this time would be in the current DST
        const timeInCurrentDST = new Date(appointmentTime);
        
        // If we're viewing in winter time but appointment was in summer time, add 1 hour
        // If we're viewing in summer time but appointment was in winter time, subtract 1 hour
        const originalDST = getDSTPeriod(originalDate);
        const currentDST = getDSTPeriod(currentDate);
        
        if (originalDST === 'zomertijd' && currentDST === 'wintertijd') {
            timeInCurrentDST.setHours(timeInCurrentDST.getHours() - 1);
        } else if (originalDST === 'wintertijd' && currentDST === 'zomertijd') {
            timeInCurrentDST.setHours(timeInCurrentDST.getHours() + 1);
        }
        
        return timeInCurrentDST.toLocaleTimeString('nl-NL', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
    
    function isProblematicDSTTime(date, hour) {
        if (!isDSTTransitionDay(date)) return false;
        
        // During spring forward (2 AM -> 3 AM), 2 AM doesn't exist
        // During fall back (3 AM -> 2 AM), we need to handle the duplicate 2 AM carefully
        const isSpringForward = date.getTimezoneOffset() > new Date(date.getFullYear(), 0, 1).getTimezoneOffset();
        
        if (isSpringForward && hour === 2) {
            return true; // 2 AM doesn't exist during spring forward
        }
        
        return false;
    }
    
    function extractAppointmentTime(appointmentText) {
        // Extract Unix timestamp from appointment data if available
        // This is a simplified version - in practice you'd need to parse the appointment data
        return null; // Placeholder - would need actual implementation
    }
    
    function generateTimeSlotsForDay(date, isDSTDay) {
        const slots = [];
        const currentUnixTime = Math.floor(Date.now() / 1000);
        const totalMinutesInDay = 24 * 60;
        
        if (!isDSTDay) {
            // Normal day - generate standard 24-hour slots
            for (let minutes = 0; minutes < totalMinutesInDay; minutes += scheduleInterval) {
                const slotDate = new Date(date);
                slotDate.setHours(Math.floor(minutes / 60), minutes % 60, 0, 0);
                
                slots.push({
                    minutes: minutes,
                    time: slotDate.toLocaleTimeString('nl-NL', { hour: 'numeric', minute: '2-digit' }),
                    unixTime: Math.floor(slotDate.getTime() / 1000),
                    isPastTime: Math.floor(slotDate.getTime() / 1000) < currentUnixTime,
                    isExtraSlot: false,
                    isSkippedSlot: false
                });
            }
        } else {
            // DST transition day - handle special cases
            const isSpringForward = isSpringForwardTransition(date);
            
            if (isSpringForward) {
                // Spring forward: skip 2 AM hour entirely
                for (let minutes = 0; minutes < totalMinutesInDay; minutes += scheduleInterval) {
                    const hour = Math.floor(minutes / 60);
                    if (hour === 2) continue; // Skip 2 AM hour
                    
                    const slotDate = new Date(date);
                    slotDate.setHours(Math.floor(minutes / 60), minutes % 60, 0, 0);
                    slots.push({
                        minutes: minutes,
                        time: slotDate.toLocaleTimeString('nl-NL', { hour: 'numeric', minute: '2-digit' }),
                        unixTime: Math.floor(slotDate.getTime() / 1000),
                        isPastTime: Math.floor(slotDate.getTime() / 1000) < currentUnixTime,
                        isExtraSlot: false,
                        isSkippedSlot: false
                    });
                }
            } else {
                // Fall back: create normal slots + extra hour slots
                for (let minutes = 0; minutes < totalMinutesInDay; minutes += scheduleInterval) {
                    const hour = Math.floor(minutes / 60);
                    const minute = minutes % 60;
                    
                    // Create normal slots for all hours
                    const slotDate = new Date(date);
                    slotDate.setHours(Math.floor(minutes / 60), minutes % 60, 0, 0);
                    slots.push({
                        minutes: minutes,
                        time: slotDate.toLocaleTimeString('nl-NL', { hour: 'numeric', minute: '2-digit' }),
                        unixTime: Math.floor(slotDate.getTime() / 1000),
                        isPastTime: Math.floor(slotDate.getTime() / 1000) < currentUnixTime,
                        isExtraSlot: false,
                        isSkippedSlot: false
                    });
                    
                    // Add extra hour slots after 2:30
                    if (hour === 2 && minute === 30) {
                        // Create extra 2:00 and 2:30 slots (representing the 3:00 and 3:30 unix times)
                        const extra2AM = new Date(date);
                        extra2AM.setHours(2, 0, 0, 0);
                        slots.push({
                            minutes: 155, // Between 2:30 and 3:00
                            time: extra2AM.toLocaleTimeString('nl-NL', { hour: 'numeric', minute: '2-digit' }),
                            unixTime: Math.floor(extra2AM.getTime() / 1000) + 7200, // 3:00 unix time (add 2 hours)
                            isPastTime: Math.floor(extra2AM.getTime() / 1000) + 7200 < currentUnixTime,
                            isExtraSlot: true,
                            isSkippedSlot: false
                        });
                        
                        const extra2AM30 = new Date(date);
                        extra2AM30.setHours(2, 30, 0, 0);
                        slots.push({
                            minutes: 165, // Between 2:30 and 3:00
                            time: extra2AM30.toLocaleTimeString('nl-NL', { hour: 'numeric', minute: '2-digit' }),
                            unixTime: Math.floor(extra2AM30.getTime() / 1000) + 7200, // 3:30 unix time (add 2 hours)
                            isPastTime: Math.floor(extra2AM30.getTime() / 1000) + 7200 < currentUnixTime,
                            isExtraSlot: true,
                            isSkippedSlot: false
                        });
                    }
                }
            }
        }
        
        return slots;
    }
    
    function isSpringForwardTransition(date) {
        // Check if this is a spring forward transition (2 AM -> 3 AM)
        // Spring forward happens in March (last Sunday)
        const month = date.getMonth();
        return month === 2; // March (0-indexed)
    }

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
    
    // Debug: Check if scheduler_data is available
    console.log('Scheduler data:', scheduler_data);
    console.log('AJAX URL:', ajaxUrl);
    console.log('Schedule interval:', scheduleInterval);

    // Update calendar sections for the given date
    function updateCalendarSections(date) {
        const leftDate = new Date(date);
        const middleDate = new Date(date);
        const rightDate = new Date(date);

        leftDate.setDate(leftDate.getDate() - 1);
        rightDate.setDate(rightDate.getDate() + 1);

        // Show loading indicator
        showLoadingIndicator();

        // Load all three sections in parallel for better performance
        const promises = [
            updateDaySectionAsync('left-section', leftDate),
            updateDaySectionAsync('middle-section', middleDate),
            updateDaySectionAsync('right-section', rightDate)
        ];

        Promise.all(promises).then(() => {
            console.log('All calendar sections loaded');
            hideLoadingIndicator();
        }).catch(error => {
            console.error('Error loading calendar sections:', error);
            hideLoadingIndicator();
        });
    }

    // Loading indicator functions
    function showLoadingIndicator() {
        const sections = ['left-section', 'middle-section', 'right-section'];
        sections.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            if (section) {
                const table = section.querySelector('.day-schedule-table');
                if (table) {
                    table.innerHTML = '<tr><td colspan="2" style="text-align: center; padding: 20px;">Laden...</td></tr>';
                }
            }
        });
    }

    function hideLoadingIndicator() {
        // Loading indicator will be replaced by actual content
    }

    function updateDaySection(sectionId, date) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.dataset.date = date.toISOString();
            section.querySelector('.day-heading').textContent = formatDate(date);
            loadSchedule(date, section);
        }
    }

    // Async version for parallel loading
    function updateDaySectionAsync(sectionId, date) {
        return new Promise((resolve, reject) => {
            const section = document.getElementById(sectionId);
            if (section) {
                section.dataset.date = date.toISOString();
                section.querySelector('.day-heading').textContent = formatDate(date);
                loadScheduleAsync(date, section).then(resolve).catch(reject);
            } else {
                reject(new Error(`Section ${sectionId} not found`));
            }
        });
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

    // Async version for parallel loading (no caching for dynamic data)
    function loadScheduleAsync(date, section) {
        return new Promise((resolve, reject) => {
            const selectedDate = date.toISOString().split('T')[0];
            console.log(`Loading schedule for section on ${selectedDate}`);
            
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
                console.log(`Schedule data received:`, data);
                if (data.success) {
                    renderTimeSlots(section, data.data.appointments);
                } else {
                    renderTimeSlots(section, []);
                }
                resolve();
            })
            .catch(error => {
                console.error("Error fetching schedule:", error);
                reject(error);
            });
        });
    }

    function renderTimeSlots(section, appointments) {
        const table = section.querySelector('.day-schedule-table');
        if (!table) return;
        

        // Use DocumentFragment for better performance
        const fragment = document.createDocumentFragment();
        const currentUnixTime = Math.floor(Date.now() / 1000);
        const sectionDate = new Date(section.dataset.date);
        const isDSTDay = isDSTTransitionDay(sectionDate);
        
        // Generate time slots based on DST transition type
        const timeSlots = generateTimeSlotsForDay(sectionDate, isDSTDay);
        
        for (const slot of timeSlots) {
            const { time, unixTime, isPastTime, isExtraSlot, isSkippedSlot } = slot;
            
            // Find appointments by Unix time for more accurate matching
            let matchingAppointments = findAppointmentByUnixTime(appointments, unixTime, sectionDate);
            
            let appointmentText = '';
            let timeDisplay = time;
            
            // Handle special DST transition cases
            let rowClass = '';
            if (isExtraSlot) {
                timeDisplay = `${time} (Extra uur)`;
                rowClass = 'extra-hour';
            } else if (isSkippedSlot) {
                // This slot doesn't exist, skip it
                continue;
            }
            
            // Build appointment text based on number of appointments
            if (matchingAppointments && matchingAppointments.length > 0) {
                // Show first person's name
                const firstAppointment = matchingAppointments[0];
                appointmentText = `${firstAppointment.user_name}, ${firstAppointment.city}, ${firstAppointment.country}`;
                
                // Add DST info marker if needed
                if (sectionDate) {
                    const dstMarker = addDSTInfoMarker(firstAppointment.appointment_datetime, sectionDate);
                    if (dstMarker) {
                        appointmentText += ` ${dstMarker}`;
                    }
                }
                
                // If there are more appointments, show count
                if (matchingAppointments.length > 1) {
                    appointmentText += ` (en ${matchingAppointments.length - 1} meer)`;
                }
                
                
                // Add a "+" button to allow more signups to this slot
                if (!isPastTime) {
                    appointmentText += ` <button class="add-slot" data-unix="${unixTime}">+</button>`;
                }
            } else {
                // No appointments - show Open button or "Open" text for past slots
                appointmentText = isPastTime ? "Open" : `<button class="open-slot" data-unix="${unixTime}">Open</button>`;
            }
            
            // Create row element
            const row = document.createElement('tr');
            if (rowClass) row.className = rowClass;
            
            const timeCell = document.createElement('td');
            timeCell.textContent = timeDisplay;
            
            const appointmentCell = document.createElement('td');
            appointmentCell.innerHTML = appointmentText;
            
            row.appendChild(timeCell);
            row.appendChild(appointmentCell);
            fragment.appendChild(row);
        }

        // Clear and append all at once for better performance
        table.innerHTML = '';
        table.appendChild(fragment);

        // Use event delegation for better performance
        table.addEventListener('click', function(e) {
            if (e.target.classList.contains('open-slot') || e.target.classList.contains('add-slot')) {
                const unixTimestamp = e.target.getAttribute('data-unix');
                const userFriendlyDate = new Date(unixTimestamp * 1000).toLocaleString('nl-NL');
                document.getElementById('appointment_datetime').value = userFriendlyDate;
                document.getElementById('unix_timestamp').value = unixTimestamp;

                const appointmentFormContainer = document.getElementById('appointment-form-container');
                appointmentFormContainer.style.display = 'block';
                appointmentFormContainer.scrollIntoView();
                document.getElementById('user_name').focus();

                // Uncheck the button and trigger the event
                const repeatToggle = document.getElementById('repeat_toggle');
                if (repeatToggle) {
                    repeatToggle.checked = false;
                    toggleRepeatOptions(false);
                }
            }
        });
    }

    function findAppointment(appointments, minutes, sectionDate) {
        for (const appointment of appointments) {
            const appointmentTime = new Date(appointment.appointment_datetime * 1000);
            const appointmentMinutes = appointmentTime.getHours() * 60 + appointmentTime.getMinutes();
    
            if (appointmentMinutes === minutes) {
                let appointmentText = `${appointment.user_name}, ${appointment.city}, ${appointment.country}`;
                
                // Add DST info marker if needed
                if (sectionDate) {
                    const dstMarker = addDSTInfoMarker(appointment.appointment_datetime, sectionDate);
                    if (dstMarker) {
                        appointmentText += ` ${dstMarker}`;
                    }
                }
                
                return appointmentText;
            }
        }
        return null;
    }
    
    function findAppointmentByUnixTime(appointments, unixTime, sectionDate) {
        const matchingAppointments = [];
        
        // Convert the timeslot unix time to a date object to get hours and minutes
        const slotDate = new Date(unixTime * 1000);
        const slotHours = slotDate.getHours();
        const slotMinutes = slotDate.getMinutes();
        
        for (const appointment of appointments) {
            // Convert appointment unix time to date object
            const appointmentDate = new Date(appointment.appointment_datetime * 1000);
            const appointmentHours = appointmentDate.getHours();
            const appointmentMinutes = appointmentDate.getMinutes();
            
            // Match by time of day (hours and minutes) within the same date
            if (appointmentHours === slotHours && appointmentMinutes === slotMinutes) {
                matchingAppointments.push(appointment);
            }
        }
        
        if (matchingAppointments.length === 0) {
            return null;
        }
        
        // Return all matching appointments for this timeslot
        return matchingAppointments;
    }

    // Add event listeners for date navigation
    document.getElementById('prev-day').addEventListener('click', function() {
        console.log('Previous day clicked');
        currentDate.setDate(currentDate.getDate() - 1);
        document.getElementById('jump-to-date').value = currentDate.toISOString().split('T')[0];
        updateCalendarSections(currentDate);
    });

    document.getElementById('next-day').addEventListener('click', function() {
        console.log('Next day clicked');
        currentDate.setDate(currentDate.getDate() + 1);
        document.getElementById('jump-to-date').value = currentDate.toISOString().split('T')[0];
        updateCalendarSections(currentDate);
    });
    
    // Removed week preloading since appointments are dynamic and real-time

    // Performance monitoring
    function measurePerformance() {
        const startTime = performance.now();
        return {
            end: () => {
                const endTime = performance.now();
                console.log(`Timeslot loading took ${endTime - startTime} milliseconds`);
                return endTime - startTime;
            }
        };
    }

    // Initialize with performance monitoring
    const perfTimer = measurePerformance();
    
    // Debug: Check if elements exist
    console.log('Left section:', document.getElementById('left-section'));
    console.log('Middle section:', document.getElementById('middle-section'));
    console.log('Right section:', document.getElementById('right-section'));
    console.log('Prev button:', document.getElementById('prev-day'));
    console.log('Next button:', document.getElementById('next-day'));
    
    // Check if all required elements exist
    const leftSection = document.getElementById('left-section');
    const middleSection = document.getElementById('middle-section');
    const rightSection = document.getElementById('right-section');
    const prevButton = document.getElementById('prev-day');
    const nextButton = document.getElementById('next-day');
    
    if (!leftSection || !middleSection || !rightSection || !prevButton || !nextButton) {
        console.error('Required calendar elements not found!');
        return;
    }
    
    updateCalendarSections(currentDate);
    
    // Log performance after initial load
    setTimeout(() => {
        perfTimer.end();
    }, 1000);
});
