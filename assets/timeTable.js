import TomSelect from "tom-select";
import flatpickr from 'flatpickr';
import { Danish } from "flatpickr/dist/l10n/da.js"
import "tom-select/dist/css/tom-select.default.css";
import 'flatpickr/dist/flatpickr.min.css';
import TimeTableApiHandler from "./timeTableApiHandler";



jQuery(document).ready(function ($) {
    const pluginSettings = {
        userId: timetableSettings.settings.userId,
        ticketCacheTimeout: parseFloat(timetableSettings.settings.ticketCacheExpiration),
    };
  class TimeTable {
    constructor() {

        console.log(pluginSettings);
        this.tomselect = null;
      this.currentViewWeek = $("input[name='timetable-current-week']").val();
      this.currentViewFirstDay = $(
        "input[name='timetable-current-week-first-day']",
      ).val();
      // General selectors
      this.newEntryButton = $("button.timetable-new-entry");
      this.syncButton = $("button.timetable-sync-tickets");
      this.refreshPanel = $(".timetable-sync-panel");

      // Modal selectors
      this.timeEditModal = $("#edit-time-log-modal");
      this.timeEditForm = this.timeEditModal.find(".edit-time-log-form");
      this.timeEditSyncModal = $("#edit-time-sync-modal");
      this.modalInputTimesheetId = this.timeEditModal.find(
        'input[name="timesheet-id"]',
      );
      this.modalInputTicketId = this.timeEditModal.find(
        'input[name="timesheet-ticket-id"]',
      );
      this.modalInputTicketName = this.timeEditModal.find(
        "input.timetable-ticket-input",
      );
      this.modalInputHours = this.timeEditModal.find(
        'input[name="timesheet-hours"]',
      );
      this.modalInputHoursLeft = this.timeEditModal.find(
        'input[name="timesheet-hours-left"]',
      );
      this.modalTextareaDescription = this.timeEditModal.find(
        'textarea[name="timesheet-description"]',
      );
      this.modalInputDate = this.timeEditModal.find(
        'input[name="timesheet-date"]',
      );
      this.modalTicketIdInput = this.timeEditModal.find(
        'input[name="timesheet-ticket-id"]',
      );

      this.modalCloseButton = this.timeEditModal.find(".timetable-close-modal");
      this.modalDeleteButton = this.timeEditModal.find(
        ".timetable-modal-delete",
      );
      this.modalCancelButton = this.timeEditModal.find(
        ".timetable-modal-cancel",
      );
      this.modalSubmitButton = this.timeEditModal.find(
        ".timetable-modal-submit",
      );
      this.modalInputDate = this.timeEditModal.find(
        'input[name="timesheet-date"]',
      );
      this.modalTicketInput = this.timeEditModal.find(
        ".timetable-ticket-input",
      );
      this.modalTicketResults = this.timeEditModal.find(
        ".timetable-ticket-results",
      );

      // Register event handlers
      this.registerEventHandlers();

      this.toggleVisualLoaders();
      this.isFetching = true;
      TimeTableApiHandler.fetchTicketData().then(() => {
        this.isFetching = false;
        this.populateLastUpdated();
        this.initTicketSearch();
      });

        flatpickr("#dateRange", {
            mode: "range",
            dateFormat: 'd-m-Y',
            allowInput: false,
            readonly: false,
            weekNumbers: true,
            locale: Danish,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates && selectedDates.length === 2) {
                    instance.element.form.submit();
                }
            },
        });
    }

    /**
     * Registers event handlers for the timetable module.
     *
     * @function registerEventHandlers
     *
     * @returns {void}
     */
    registerEventHandlers() {
      // New entry
      this.newEntryButton.click(() => {
        if (this.isFetching) {
          let intervalId = setInterval(() => {
            if (!this.isFetching) {
              clearInterval(intervalId);
              this.newTimeEntry();
            }
          }, 500);
        } else {
          this.newTimeEntry();
        }
      });
      // Edit entry
      $(document).on("click", "td.timetable-edit-entry", (e) => {
        const id = e.target.dataset.id ?? null;
        const ticketId = e.target.dataset.ticketid ?? null;
        const hours = e.target.dataset.hours ?? null;
        const hoursLeft = e.target.dataset.hoursleft ?? null;
        const description = e.target.dataset.description ?? null;
        const date = e.target.dataset.date ?? null;

        if (this.isFetching) {
          let intervalId = setInterval(() => {
            if (!this.isFetching) {
              clearInterval(intervalId);
              this.editTimeEntry(
                id,
                ticketId,
                hours,
                hoursLeft,
                description,
                date,
              );
            }
          }, 500);
        } else {
          this.editTimeEntry(id, ticketId, hours, hoursLeft, description, date);
        }
      });

      // Close modal
      this.modalCloseButton.click(() => this.closeEditTimeLogModal());
      this.modalCancelButton.click(() => this.closeEditTimeLogModal());
      $(document).keydown((e) => {
        // Escape key
        if (e.keyCode === 27) {
          this.closeEditTimeLogModal();
        }
      });

      this.boundClickOutsideModalHandler = (e) =>
        this.clickOutsideModalHandler(e);

      $(this.timeEditForm).on("submit", () => {
          this.modalSubmitButton.html('<i class="fa-solid fa-arrows-rotate fa-spin"></i>');
          this.modalSubmitButton.attr("disabled", "disabled");
      });

      // Delete timeentry
      this.modalDeleteButton.click(() => this.deleteTimeEntry());

      this.syncButton.click(() => this.refreshButtonPress());

    }

    /**
     * Opens the Edit Time Log modal for a new entry.
     *
     * @return {void}
     */
    newTimeEntry() {
      this.populateLastUpdated();
      this.openEditTimeLogModal();

      // Set date today
      let currentWeekNumber = new Date().getWeek();
      let viewWeekNumber = parseInt(this.currentViewWeek, 10);

      this.modalInputDate[0].valueAsDate =
        currentWeekNumber === viewWeekNumber
          ? new Date()
          : new Date(this.currentViewFirstDay);

      // Init ticket search
      this.modalInputTicketName.removeAttr("disabled");
      this.modalTicketInput.focus().keyup((e) => this.filterFunction(e));
      this.modalDeleteButton.hide();

      // Ticket result click event
      let context = this;
      this.timeEditForm.on("click", function (e) {
        if ($(e.target).is(".timetable-ticket-result-item")) {
          context.selectTicket(e.target);
        }
      });
    }

    /**
     * Opens the Edit Time Log modal for editing an entry.
     *
     * @param {HTMLElement} target - The HTML element representing the ticket being selected.
     * @returns {void}
     */
    selectTicket(target) {
      const {
        innerText: taskName,
        dataset: { value: taskId, hoursleft: hoursLeft },
      } = target;

      // Set values from selected ticket
      this.modalTicketInput.val(taskName);
      this.modalTicketIdInput.val(taskId);
      this.modalInputHoursLeft.val(hoursLeft).attr("data-value", hoursLeft);

      // Reset search
      this.modalTicketResults.empty();
    }

    /**
     * Filters the timetable ticket results based on the input value.
     *
     * @param event
     *
     * @returns {void}
     */
    filterFunction(event) {
      const { value } = event.target;
      const dropDownElement = $(".timetable-ticket-results");

      // Reset search
      if (value.length <= 1) {
        dropDownElement.empty();
      }

      // Perform search
      if (value.length > 1) {
        const { data: tickets } = TimeTableApiHandler.readFromCache("timetable_tickets");
        this.ticketSearch(tickets, value);
      }
    }

    /**
     * Search for tickets in an object using a query.
     *
     * @param {Object} obj - The object to search in.
     * @param {string} query - The query to search for.
     * @return {Object} results
     */
    ticketSearch(obj, query) {
      const { id, text, children } = obj;

      const lowerCaseQuery = query.toLowerCase();

      let results = [];

      // Checks if `obj`'s `text` or `id` contains `lowerCaseQuery` and not already added to timetable.

      if (
        "text" in obj &&
        typeof text === "string" &&
        (text.toLowerCase().includes(lowerCaseQuery) ||
          (id && id.toString().toLowerCase().includes(lowerCaseQuery)))
      ) {
        const index = text.toLowerCase().indexOf(lowerCaseQuery);
        const relevance = index === -1 ? 0 : text.length - index;
        results.push({ ...obj, relevance });
      }

      // If `children` is an array, searches each `child` for `query` and merges results.
      if (Array.isArray(children)) {
        children.forEach((child) => {
          const childResults = this.ticketSearch(child, query);
          results = results.concat(childResults);
        });
      }

      results.sort((a, b) => b.relevance - a.relevance);

      this.modalTicketResults.empty();
      if (results.length === 0) {
        this.modalTicketResults.append(
          `<div class="timetable-ticket-result-item-no-results" data-value="">No results</div>`,
        );
      }
      results.forEach(({ id, text, projectName, hoursLeft }) => {
        this.modalTicketResults.append(
          `<div class="timetable-ticket-result-item" data-project="${projectName}" data-hoursleft="${hoursLeft}" data-value="${id}"><span>${text}</span></div>`,
        );
      });
      return results;
    }

    /**
     * Updates a time entry.
     *
     * @param {number} id - Time entry ID.
     * @param {string} ticketId - Ticket ID.
     * @param {number} hours - Hours spent.
     * @param {number} hoursLeft - Hours left.
     * @param {string} description - Work done.
     * @param {string} date - Work date.
     * @param {number} offset
     * @return {boolean}
     */
    editTimeEntry(id, ticketId, hours, hoursLeft, description, date, offset) {
      // Find ticket in cache
      const ticket = TimeTableApiHandler.getTicketDataFromCache(
        parseInt(ticketId),
      );

      if (!ticket) {
        this.openEditTimeSyncModal();
        TimeTableApiHandler.fetchTicketDatum(ticketId).then(() => {
          this.closeEditTimeSyncModal();
          this.editTimeEntry(
            id,
            ticketId,
            hours,
            hoursLeft,
            description,
            date,
            offset,
          );
        });
        return false;
      }

      this.openEditTimeLogModal();

      if (id) {
        this.modalDeleteButton.show();
      } else {
        this.modalDeleteButton.hide();
      }

      this.modalInputTimesheetId.val(id);
      this.modalInputTicketId.val(ticket.id);
      this.modalInputTicketName.val(ticket.text).attr("disabled", "disabled");
      this.modalInputHours.val(hours);
      this.modalInputHoursLeft.val(hoursLeft).attr("data-value", hoursLeft);
      this.modalTextareaDescription.val(description);
      this.modalInputDate.val(date);

      this.modalInputHours.focus();
    }

    /**
     * This method handles the button press event for refreshing data.
     *
     * @return {boolean}
     */
    refreshButtonPress() {
        this.toggleVisualLoaders();
      this.refreshTicketSearch();
    }

    toggleVisualLoaders() {
        if (this.isFetching) {
            return false;
        }
        const syncButtonHtml =
                '<i class="fa-solid fa-arrows-rotate fa-spin"></i>Syncing data';

        $(this.syncButton).children("span").html(syncButtonHtml);
    }

    /**
     * Refreshes the ticket search by removing tickets from the cache and setting new ticket data.
     *
     * @return {void}
     */
    refreshTicketSearch() {
      TimeTableApiHandler.removeFromCache("timetable_tickets");
      this.setTicketData();
    }

    /**
     * Sets the ticket data.
     *
     * @return {void}
     */
    setTicketData() {
      if (this.isFetching) {
        setTimeout(() => {
          // If already fetching, recall for cached result.
          this.setTicketData();
        }, 500);
      } else {
        this.isFetching = true;
        TimeTableApiHandler.fetchTicketData().then(() => {
          this.isFetching = false;
          this.populateLastUpdated();
          this.initTicketSearch();
        });
      }
    }

    /**
     * Populates the last updated information in the panel.
     *
     * @returns {void}
     */
    populateLastUpdated() {
      let ticketsLastUpdated =
        TimeTableApiHandler.readFromCache("timetable_tickets").expiration;

      let ticketsLastUpdatedElement =
        "<span>Tickets: " +
        Math.round((Date.now() - ticketsLastUpdated) / 60000) +
        " min ago.</span>";

      $(this.syncButton)
        .children("span")
        .html(
          '<span><i class="fa-solid fa-arrows-rotate"></i> Sync data</span>',
        );
      $(this.refreshPanel)
        .children("div")
        .last()
        .html(ticketsLastUpdatedElement);
    }

    deleteTimeEntry() {
      const timesheetId = this.modalInputTimesheetId.val();
      $(this.modalDeleteButton)
        .html('<i class="fa-solid fa-arrows-rotate"></i>')
        .addClass("deleting");
      fetch(window.location.href, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "delete",
          timesheetId: timesheetId,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            window.location.reload();
          } else {
            alert("An error has occurred");
          }
        });
    }

    /**
     * Closes the edit time log modal.
     *
     * @returns {void}
     */
    closeEditTimeLogModal() {
      $(this.timeEditModal).hide().find("input, textarea").val("");
      $(this.modalTicketResults).empty();
      $(this.modalInputHoursLeft).removeAttr("data-value");
      $(document).off("mousedown", this.boundClickOutsideModalHandler);
    }

    /**
     * Opens the edit time log modal.
     *
     * @returns {void}
     */
    openEditTimeLogModal() {
      $(this.timeEditModal).show().css("display", "flex");
      $(document).on("mousedown", this.boundClickOutsideModalHandler);
    }

    openEditTimeSyncModal() {
      $(this.timeEditSyncModal).show().css("display", "flex");
    }

    closeEditTimeSyncModal() {
      $(this.timeEditSyncModal).hide();
    }

    /**
     * Handles the click outside the modal.
     *
     * @param {Event} event
     *
     * @return {void}
     */
    clickOutsideModalHandler(event) {
      if ($(event.target).find(this.timeEditForm).length > 0) {
        this.closeEditTimeLogModal();
      }
    }

    initTicketSearch(autofocus = false) {
      let {
        data: { children: tickets },
      } = TimeTableApiHandler.readFromCache("timetable_tickets");
      let {
        data: { children: projects },
      } = TimeTableApiHandler.readFromCache("timetable_projects");

      const pageSize = 50;
      const userId = pluginSettings.userId;

      // Sort tickets by editorId and created date.
      tickets.sort((a, b) => {
        if (a.editorId === userId && b.editorId !== userId) {
          return -1;
        } else if (b.editorId === userId && a.editorId !== userId) {
          return 1;
        } else {
          const dateA = new Date(a.createdDate);
          const dateB = new Date(b.createdDate);
          return dateB - dateA;
        }
      });

      // Exclude tickets that are already present in the table.

      const activeTicketIds = $("#timetable > tbody > tr[data-ticketid]")
        .map(function () {
          return $(this).data("ticketid");
        })
        .get();

      const options = tickets
        .filter((child) => !activeTicketIds.includes(child.id))
        .map((child) => {
          return {
            value: child.id,
            text: child.text,
            type: child.type,
            projectName: child.projectName,
            editorId: child.editorId,
          };
        });


      if (this.tomselect) {
          this.tomselect.destroy();
      }
      // Init tomselect
        this.tomselect = new TomSelect(".timetable-tomselect", {
        options: options,
        searchField: ["text", "value", "projectName"],
        loadingClass: "ts-loading",
        placeholder: "+ New registration",
        create: function (input) {
          return { value: input, text: input };
        },
        render: {
          item: function (item, escape) {
              return `
<div>
    <span>
        ${escape(item.text)}
        <span>
            <i class="fa fa-angle-right fa-xs"></i> ${escape(item.projectName)}
            <small>(${escape(item.value)})</small>

            ${item.type !== "task" ? `<small>(${escape(item.type)})</small>` : ""}
        </span>
    </span>
</div>`;
          },
            option: function (item, escape) {
                return `<div><span>${escape(item.text)} <span><i class="fa fa-angle-right fa-xs"></i> ${escape(item.projectName)} <small>(${escape(item.value)})</small> <small style="float: right;">(${escape(item.type)})</small></span></span></div>`;
            },
          option_create: function (data, escape) {
            return `<option data-value="add-new-ticket" class="create">+ Create new ticket: <strong>${escape(data.input)}</strong>&hellip;</option>`;
          },
        },
        load: function (query, callback) {
          if (!query.length) return callback();
          const term = query.toUpperCase();
          let results = options.filter(
            (e) =>
              (e.text && e.text.toUpperCase().includes(term)) ||
              (typeof e.value === "string" && e.value.toUpperCase() === term) ||
              (e.projectName && e.projectName.toUpperCase().includes(term)),
          );
          callback(results.slice(0, pageSize));
        },
        onChange: function (value) {
          const selectedOption = this.options[value];

          // Check if selected option is the "create new" one
          if (
            selectedOption.text === selectedOption.value &&
            typeof selectedOption.projectName === "undefined"
          ) {
            const projectOptions = [
              {
                value: "Select a project:",
                text: "Select a project:",
                disabled: "disabled",
              },
              { value: selectedOption.value, text: selectedOption.value },
              ...projects
                .filter((project) => project.text.trim() !== "")
                .map((project) => ({ value: project.id, text: project.text })),
            ];

            // Destroy select and populate with projects for the new ticket to be created in
            this.destroy();
            this.tomselect = new TomSelect(".timetable-tomselect", {
              options: projectOptions,
              onItemRemove: function () {
                  console.log('hallo?');
                // Reactivate the ticket search upon item removal
                this.destroy();
                timeTable.initTicketSearch(true);
              },
              onChange: function () {
                const selectedValues = this.getValue();
                const resultArray = selectedValues.split(",");
                if (resultArray.length === 2) {
                  const ticketName = resultArray[0];
                  const projectId = resultArray[1];
                  const projectName = tomselect.options[projectId].text;
                  let result = TimeTableApiHandler.createNewTicket(
                    ticketName,
                    projectId,
                    userId,
                  );
                  result.then((data) => {
                    const ticketId = data.result[0];
                    if (ticketId && ticketName && projectName) {
                      timeTable.addRowToTimetable(
                        ticketId,
                        ticketName,
                        projectName,
                      );
                      this.destroy();
                      timeTable.initTicketSearch();
                      TimeTableApiHandler.fetchTicketDatum(ticketId);
                    }
                  });
                }
              },
            });
            tomselect.open();
            return;
          }
          timeTable.addRowToTimetable(
            value,
            selectedOption.text,
            selectedOption.projectName,
          );
          this.clear();
        },
      });

      if (autofocus) {
        tomselect.focus();
        tomselect.open();
      }
    }

    addRowToTimetable(ticketId, ticketText, projectName) {
      const firstDateOfWeek = new Date(
        $("input[name='timetable-current-week-first-day']").val(),
      );

      // Create a new date object to ensure the original date is preserved
      let dateIterator = new Date(firstDateOfWeek.getTime());

      const newRow = `
    <tr class="newly-added-tr">
        <td class="ticket-title">
            <a href="?showTicketModal=${ticketId}#/tickets/showTicket/${ticketId}">${ticketText}</a>
            <span>${projectName}</span>
        </td>
        ${Array.from({ length: 7 })
          .map((_, i) => {
            // Increment date
            if (i > 0) {
              dateIterator.setDate(dateIterator.getDate() + 1);
            }

            // Format date in YYYY-MM-DD format
            const formattedDate = dateIterator.toISOString().slice(0, 10);

            // Depending on the day of the week, add 'weekend' class
            const weekendClass = i === 5 || i === 6 ? "weekend" : "";

            return `<td class="timetable-edit-entry ${weekendClass}" data-ticketid=${ticketId} data-date="${formattedDate}" title="">
                        <span></span>
                    </td>`;
          })
          .join("")}
        <td></td>
    </tr>
`;

      $("td.add-new").parent().before(newRow);
    }
  }

  let timeTable = new TimeTable();
});

/**
 * Retrieves the current date's week number in the year.
 * 86400000 is the number of milliseconds in a day used to convert time between dates into days.
 *
 * @returns {Number} — Week number of the year for this date.
 */
Date.prototype.getWeek = function () {
  const firstDayOfYear = new Date(this.getFullYear(), 0, 1);
  const pastDaysOfYear = (this - firstDayOfYear) / 86400000;
  return Math.ceil((pastDaysOfYear + firstDayOfYear.getDay() + 1) / 7);
};
