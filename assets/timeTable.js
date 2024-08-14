function changeWeek(offset) {
  let params = new URLSearchParams(document.location.search);
  if (params.get("offset")) {
    offset += parseInt(params.get("offset"));
  }
  offset === ""
    ? updateLocation("offset", "")
    : updateLocation("offset", offset);
}

function redirectWithSearchTerm(searchTerm) {
  searchTerm === "all"
    ? updateLocation("searchTerm", "")
    : updateLocation("searchTerm", searchTerm);
}

function updateLocation(key, value) {
  let params = new URLSearchParams(document.location.search);
  if (params.has(key)) {
    params.delete(key);
  }
  if (value !== "") {
    params.append(key, value);
  }
  window.location = `?${params.toString()}`;
}

function openEditTimeLogModal(id, ticketId, hours, description, date, offset) {
  let createNewTimeLog = false;
  // Set post parameters
  if (id) {
    document.getElementsByName("timesheet-id")[0].setAttribute("value", id);
  }
  if (ticketId) {
    document
      .getElementsByName("timesheet-ticket-id")[0]
      .setAttribute("value", ticketId);
  }
  if (offset) {
    document
      .getElementsByName("timesheet-offset")[0]
      .setAttribute("value", offset);
  }
  if (description) {
    document
      .getElementsByName("timesheet-description")[0]
      .setAttribute("value", description);
  }
  if (hours) {
    document
      .getElementsByName("timesheet-hours")[0]
      .setAttribute("value", hours);
  }
  if (date) {
    document.getElementsByName("timesheet-date")[0].setAttribute("value", date);
  }

  if (!date && !ticketId) {
    createNewTimeLog = true;
  }

  // fill out form with known values
  if (description) {
    document.getElementById("modal-description").value = description;
  }

  document.getElementById("modal-hours").value = hours;

  // Display modal
  document.getElementById("edit-time-log-modal").style.display = "flex";
  if (createNewTimeLog) {
  }
}

function closeEditTimeLogModal() {
  // Reset modal
  document.getElementById("modal-description").value = "";
  document.getElementById("modal-hours").value = "";
  // Remove modal
  document.getElementById("edit-time-log-modal").style.display = "none";
}

function changeHours(hours) {
  if (hours) {
    document
      .getElementsByName("timesheet-hours")[0]
      .setAttribute("value", hours);
  }
}

function changeDescription(description) {
  if (description) {
    document
      .getElementsByName("timesheet-description")[0]
      .setAttribute("value", description);
  }
}

// todo rewrite these to be more readable
// copy paste from https://www.w3schools.com/howto/howto_js_filter_dropdown.asp - also entries in timeTable.css and timetable.blade.php
function myFunction(e) {
  e.preventDefault();
  document.getElementById("myDropdown").classList.toggle("show");
}

// Todo create a get request for tasks (api), and only get tasks on 3 inputs or something
function filterFunction(e) {
  e.preventDefault();
  var input, filter, ul, li, a, i;
  input = document.getElementById("myInput");
  filter = input.value.toUpperCase();
  div = document.getElementById("myDropdown");
  a = div.getElementsByTagName("a");
  for (i = 0; i < a.length; i++) {
    txtValue = a[i].textContent || a[i].innerText;
    if (txtValue.toUpperCase().indexOf(filter) > -1) {
      a[i].style.display = "";
    } else {
      a[i].style.display = "none";
    }
  }
}
