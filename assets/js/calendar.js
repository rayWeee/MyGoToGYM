document.addEventListener('DOMContentLoaded', () => {

const calendarEl = document.getElementById('calendar');
const modal = document.getElementById('workoutModal');

const canManageClasses = window.USER_ROLE === "admin" || window.USER_ROLE === "trainer";
const userId = parseInt(window.USER_ID || 0);

const titleInput = document.getElementById('classTitle');
const descInput = document.getElementById('classDesc');
const dateInput = document.getElementById('classDate');
const capInput = document.getElementById('classCap');
const colorInput = document.getElementById('classColor');
const classIdInput = document.getElementById('classId');

const saveBtn = document.getElementById('saveClassBtn');
const deleteBtn = document.getElementById('deleteClassBtn');
const closeBtn = document.getElementById('closeModal');

let selectedEvent = null;

// Function to format the date as "Month YYYY" with specific capitalization and no suffixes
function formatMonthYearCustom(date, locale) {
    // Map custom locale strings to standard BCP 47 language tags if necessary
    // For example, if window.I18N.locale is just 'en', 'lv', 'ru'
    const bcp47Locale = {
        'en': 'en-US', // Or 'en-GB', etc.
        'lv': 'lv-LV',
        'ru': 'ru-RU'
    }[locale] || locale; // Fallback to original if not mapped

    // Get the month name (long format)
    let monthName = new Intl.DateTimeFormat(bcp47Locale, { month: 'long' }).format(date);

    // Get the year (numeric format)
    let year = new Intl.DateTimeFormat(bcp47Locale, { year: 'numeric' }).format(date);

    // Clean up unwanted suffixes from the year string based on locale
    if (locale === 'lv') {
        year = year.replace(/\.\s*g\.?$/i, '').trim(); // Remove "g." or "g"
    } else if (locale === 'ru') {
        year = year.replace(/\s*г\.?$/i, '').trim(); // Remove "г." or "г"
    }

    // Capitalize the first letter of the month name
    monthName = monthName.charAt(0).toUpperCase() + monthName.slice(1);

    return `${monthName} ${year}`;
}

// Create a sub-container for user view to avoid destroying admin form
if (!document.getElementById('modalUserContent')) {
    modal.insertAdjacentHTML('beforeend', '<div id="modalUserContent" class="modal-content" style="display:none;"></div>');
}

function openModal(){
    modal.style.display = "flex";
}

function closeModal(){
    modal.style.display = "none";
}

closeBtn.onclick = closeModal;

// ================= COLOR PICKER LOGIC =================
const colorPicker = document.getElementById('colorPicker');
const colorOptions = document.getElementById('colorOptions');
const selectedDot = document.getElementById('selectedDot');
const selectedLabel = document.getElementById('selectedLabel');

const colorMap = {
    'Blue': '#7c5cff',
    'Green': '#28a745',
    'Red': '#dc3545',
    'Orange': '#fd7e14',
    'Purple': '#6f42c1',
    'Teal': '#20c997'
};

function updateColorPicker(name) {
    // Default to Blue if name is missing or invalid
    const validName = colorMap[name] ? name : "Blue";
    colorInput.value = validName;
    selectedDot.style.background = colorMap[validName];
    selectedLabel.textContent = validName;
}

if(colorPicker) {
    colorPicker.onclick = (e) => {
        e.stopPropagation();
        colorOptions.style.display = colorOptions.style.display === 'block' ? 'none' : 'block';
    };
}

document.querySelectorAll('.dropdown-option').forEach(opt => {
    opt.onclick = (e) => {
        e.stopPropagation();
        updateColorPicker(opt.dataset.value);
        colorOptions.style.display = 'none';
    };
});

window.addEventListener('click', () => { if(colorOptions) colorOptions.style.display = 'none'; });

// ================= IMPROVED CALENDAR CONFIG =================
window.calendar = new FullCalendar.Calendar(calendarEl, {

locale: window.I18N.locale,
firstDay: 1,

initialView: 'dayGridMonth',
dayHeaderFormat: { 
    weekday: 'short' 
},
dayHeaderContent: function(arg) {
    // Specifically target Russian locale to capitalize
    if (window.I18N.locale === 'ru') {
        return arg.text.charAt(0).toUpperCase() + arg.text.slice(1);
    }
    // Fallback for EN, LV and other languages to show default text
    return arg.text;
},
initialLocale: window.I18N.locale,
headerToolbar: false,
height: 'auto',

dayCellClassNames: function(arg) {
    const m = arg.date.getMonth() + 1;
    const d = arg.date.getDate();
    const holidays = ['1-1', '5-1', '5-4', '6-23', '6-24', '11-18', '12-24', '12-25', '12-26', '12-31'];
    if (holidays.includes(`${m}-${d}`)) return ['fc-day-holiday'];
    return [];
},

eventSources: [
    {
        url: 'api/get_classes.php'
    },
    {
        events: function(fetchInfo, successCallback) {
            const year = fetchInfo.start.getFullYear();
            const holidayList = [
                { title: 'Jaunais gads', start: `${year}-01-01` },
                { title: 'Darba svētki', start: `${year}-05-01` },
                { title: 'Neatkarības atjaunošana', start: `${year}-05-04` },
                { title: 'Līgo', start: `${year}-06-23` },
                { title: 'Jāņi', start: `${year}-06-24` },
                { title: 'LR proklamēšanas diena', start: `${year}-11-18` },
                { title: 'Ziemassvētku vakars', start: `${year}-12-24` },
                { title: 'Ziemassvētki', start: `${year}-12-25` },
                { title: 'Otrie Ziemassvētki', start: `${year}-12-26` },
                { title: 'Vecgada vakars', start: `${year}-12-31` }
            ];
            
            // Support for views overlapping into next year
            const nextYear = year + 1;
            holidayList.push(
                { title: 'Jaunais gads', start: `${nextYear}-01-01` },
                { title: 'Latvijas neatkarība', start: `${nextYear}-05-04` }
            );

            successCallback(holidayList.map(h => ({
                ...h,
                allDay: true,
                display: 'list-item', // Shows as a small text label
                color: '#ff3b3b',
                interactive: false, // Users can't click holidays
                extendedProps: { isHoliday: true }
            })));
        }
    }
],

eventSourceFailure: function(error) {
    console.error('Calendar events failed to load:', error);
    const infoBox = document.getElementById("classInfo");
    if (infoBox) {
        infoBox.innerHTML = '<p class="error">Calendar classes could not be loaded. Please check the database tables.</p>';
    }
},

eventDataTransform: function(eventData) {
    // Do not turn holidays grey if they are in the past
    if (eventData.extendedProps && eventData.extendedProps.isHoliday) {
        return eventData;
    }

    const now = new Date();
    const eventDate = new Date(eventData.start);
    
    // If event is in the past, force grey color
    if (eventDate < now) {
        // Using specific grey shades for past events
        eventData.color = '#555555';       // Changes the dot color
        eventData.textColor = '#888888';   // Fades the title text
        eventData.backgroundColor = 'transparent'; 
        eventData.borderColor = '#444444';
    }
    // If eventData already has a color from DB, FullCalendar uses it automatically
    return eventData;
},

// 24-hour format (FIX)
eventTimeFormat: {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
},

// =====================================================
// ADMIN CREATE
// =====================================================
dateClick(info){

    if(!canManageClasses) return;

    // Prevent adding workouts on holidays
    const date = new Date(info.dateStr);
    const m = date.getMonth() + 1;
    const d = date.getDate();
    const holidays = ['1-1', '5-1', '5-4', '6-23', '6-24', '11-18', '12-24', '12-25', '12-26', '12-31'];
    if (holidays.includes(`${m}-${d}`)) return;

    classIdInput.value = "";
    titleInput.value = "";
    descInput.value = "";
    capInput.value = 10;
    updateColorPicker("Blue");
    dateInput.value = info.dateStr + "T18:00";

    document.querySelector('.modal-content').style.display = "block";
    document.getElementById('modalUserContent').style.display = "none";
    deleteBtn.style.display = "none";

    openModal();
},

// =====================================================
// EVENT CLICK (ADMIN + USER)
// =====================================================
eventClick(info){

    const event = info.event;

    // Update the right-side dashboard if the function exists
    if (typeof window.updateSidebar === 'function') {
        window.updateSidebar(event);
    }

    // If not logged in, do not open the modal
    if (userId === 0) return;

    const id = event.id;
    const title = event.title;
    const desc = event.extendedProps.description;
    const cap = event.extendedProps.capacity;
    const booked = event.extendedProps.booked;
    
    // Use the color name stored in extendedProps (e.g., "Blue")
    // fallback to "Blue" if it's an old hex-based record
    const colorName = event.extendedProps.original_color || "Blue";

    selectedEvent = id;

    // ================= ADMIN =================
    if(canManageClasses){

        classIdInput.value = id;
        titleInput.value = title;
        descInput.value = desc;
        capInput.value = cap;
        updateColorPicker(colorName);
        dateInput.value = event.startStr.slice(0,16);

        document.querySelector('.modal-content').style.display = "block";
        document.getElementById('modalUserContent').style.display = "none";
        deleteBtn.style.display = "inline-block";

        openModal();
        return;
    }

    // ================= USER POPUP =================
    let remaining = cap - booked;
    const isFull = booked >= cap;
    const isPast = event.start < new Date();
    const userContainer = document.getElementById('modalUserContent');

    userContainer.innerHTML = `
        <h3>${title}</h3>
        <p>${desc}</p>
        <p><b>${window.I18N.attendance}:</b> ${booked}/${cap}</p>
        <p><b>${window.I18N.remaining}:</b> ${remaining}</p>
        <div class="modal-actions">
            <button id="reserveBtn" ${(isFull || isPast) ? 'disabled style="background: #333; color: #777; cursor: not-allowed; border: 1px solid #444;"' : ''}>
                ${isPast ? 'Passed' : (isFull ? window.I18N.classFull : window.I18N.reserve)}
            </button>
            <button id="closeUser" style="background: #2b2b2b; color: white;">${window.I18N.cancel}</button>
    </div>`;

    document.querySelector('.modal-content').style.display = "none";
    userContainer.style.display = "block";
    openModal();

    document.getElementById('closeUser').onclick = closeModal;

    document.getElementById('reserveBtn').onclick = () => {

        fetch('api/reserve_class.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                class_id: id,
                user_id: userId
            })
        })
        .then(r => r.json())
        .then(data => {

            alert(data.message);

            if(data.success){
                closeModal();
                
                // Optimistically update the booked count for the sidebar
                const currentBooked = parseInt(event.extendedProps.booked || 0);
                event.setExtendedProp('booked', currentBooked + 1);

                // Trigger sidebar update immediately
                if (typeof window.updateSidebar === 'function') {
                    window.updateSidebar(event);
                }

                calendar.refetchEvents();
            }

        });

    };

},

datesSet(info){
    const viewDate = info.view.currentStart; // Get the date for the current view (e.g., the first day of the month)
    const currentLocale = window.I18N.locale; // Assuming window.I18N.locale is set to 'en', 'lv', or 'ru'
    document.getElementById('calendarTitle').textContent = formatMonthYearCustom(viewDate, currentLocale);
}

});

calendar.render();

// ================= NAV =================
document.getElementById('prevBtn').onclick = () => calendar.prev();
document.getElementById('nextBtn').onclick = () => calendar.next();
document.getElementById('todayBtn').onclick = () => calendar.today();

// ================= SAVE (ADMIN) =================
saveBtn.onclick = () => {

fetch('api/save_class.php', {
method:'POST',
headers:{'Content-Type':'application/json'},
body: JSON.stringify({
id: classIdInput.value,
title: titleInput.value,
description: descInput.value,
start_datetime: dateInput.value,
capacity: capInput.value,
color: colorInput.value
})
})
.then(r => r.json())
.then(data => {

if(data.success){
closeModal();
calendar.refetchEvents();
}else{
alert(data.message || window.I18N.saveFailed);
}

});

};

// ================= DELETE =================
deleteBtn.onclick = () => {

if(!confirm(window.I18N.confirmDelete)) return;

fetch('api/delete_class.php', {
method:'POST',
headers:{'Content-Type':'application/json'},
body: JSON.stringify({id: classIdInput.value})
})
.then(r => r.json())
.then(data => {

if(data.success){
closeModal();
calendar.refetchEvents();
}

});

};

});
