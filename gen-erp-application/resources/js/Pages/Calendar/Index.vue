<template>
  <AdminLayout title="Calendar">
    <div class="p-6">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Calendar</h1>
          <p class="mt-1 text-sm text-gray-600">
            Manage your events, meetings, and tasks
          </p>
        </div>
        
        <!-- Calendar Selector -->
        <select
          v-model="selectedCalendarId"
          @change="fetchEvents"
          class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
          <option :value="null">All Calendars</option>
          <option v-for="calendar in calendars" :key="calendar.id" :value="calendar.id">
            {{ calendar.name }}
          </option>
        </select>
      </div>

      <!-- Event Type Filters -->
      <div class="mb-4 flex gap-2 flex-wrap">
        <button
          v-for="type in eventTypes"
          :key="type.value"
          @click="toggleEventType(type.value)"
          :class="[
            'px-3 py-1 rounded-full text-xs font-medium transition-colors',
            selectedEventTypes.includes(type.value)
              ? 'text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
          :style="selectedEventTypes.includes(type.value) ? { backgroundColor: type.color } : {}"
        >
          {{ type.label }}
        </button>
      </div>

      <!-- FullCalendar -->
      <div class="rounded-2xl border border-gray-200 bg-white">
        <div class="custom-calendar">
          <FullCalendar ref="calendarRef" class="min-h-screen" :options="calendarOptions" />
        </div>
      </div>

      <!-- Event Modal -->
      <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11">
          <h5 class="mb-2 font-semibold text-gray-800 text-xl lg:text-2xl">
            {{ selectedEvent ? 'Edit Event' : 'Add Event' }}
          </h5>
          <p class="text-sm text-gray-500">
            Plan your next big moment: schedule or edit an event to stay on track
          </p>

          <div class="mt-8">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700">
                Event Title
              </label>
              <input
                v-model="eventTitle"
                type="text"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-indigo-300 focus:outline-none focus:ring-3 focus:ring-indigo-500/10"
              />
            </div>

            <div class="mt-6">
              <label class="mb-1.5 block text-sm font-medium text-gray-700">
                Description
              </label>
              <textarea
                v-model="eventDescription"
                rows="3"
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-indigo-300 focus:outline-none focus:ring-3 focus:ring-indigo-500/10"
              ></textarea>
            </div>

            <div class="mt-6">
              <label class="block mb-4 text-sm font-medium text-gray-700">
                Event Type
              </label>
              <div class="flex flex-wrap items-center gap-4 sm:gap-5">
                <div v-for="type in eventTypes" :key="type.value" class="n-chk">
                  <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                    <span class="relative">
                      <input
                        type="radio"
                        :value="type.value"
                        v-model="eventType"
                        class="sr-only"
                      />
                      <span class="flex items-center justify-center w-5 h-5 mr-2 border border-gray-300 rounded-full">
                        <span 
                          v-if="eventType === type.value"
                          class="w-2 h-2 rounded-full"
                          :style="{ backgroundColor: type.color }"
                        ></span>
                      </span>
                    </span>
                    {{ type.label }}
                  </label>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <label class="mb-1.5 block text-sm font-medium text-gray-700">
                Start Date & Time
              </label>
              <input
                v-model="eventStartDate"
                type="datetime-local"
                class="h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-indigo-300 focus:outline-none focus:ring-3 focus:ring-indigo-500/10"
              />
            </div>

            <div class="mt-6">
              <label class="mb-1.5 block text-sm font-medium text-gray-700">
                End Date & Time
              </label>
              <input
                v-model="eventEndDate"
                type="datetime-local"
                class="h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-indigo-300 focus:outline-none focus:ring-3 focus:ring-indigo-500/10"
              />
            </div>

            <div class="mt-6">
              <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                <input
                  v-model="eventAllDay"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <span class="ml-2">All Day Event</span>
              </label>
            </div>
          </div>

          <div class="flex items-center gap-3 mt-6 sm:justify-end">
            <button
              @click="closeModal"
              class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto"
            >
              Close
            </button>

            <button
              @click="handleSaveEvent"
              class="flex w-full justify-center rounded-lg bg-indigo-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-600 sm:w-auto"
            >
              {{ selectedEvent ? 'Update Event' : 'Add Event' }}
            </button>
            
            <button
              v-if="selectedEvent"
              @click="handleDeleteEvent"
              class="flex w-full justify-center rounded-lg border border-red-500 bg-red-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-600 sm:w-auto"
            >
              Delete Event
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import AdminLayout from '@/Layouts/AppLayout.vue'
import { useApi } from '../../Composables/useApi.js'
import { useToast } from '../../Composables/useToast.js'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const { get, post, put, del } = useApi()
const { showToast } = useToast()

const calendarRef = ref(null)
const isModalOpen = ref(false)
const selectedEvent = ref(null)
const eventTitle = ref('')
const eventDescription = ref('')
const eventStartDate = ref('')
const eventEndDate = ref('')
const eventType = ref('meeting')
const eventAllDay = ref(false)
const calendars = ref([])
const events = ref([])
const selectedCalendarId = ref(null)
const selectedEventTypes = ref(['meeting', 'call', 'task', 'deadline', 'leave', 'milestone', 'personal', 'company'])

const eventTypes = [
  { value: 'meeting', label: 'Meeting', color: '#3B82F6' },
  { value: 'call', label: 'Call', color: '#10B981' },
  { value: 'task', label: 'Task', color: '#F59E0B' },
  { value: 'deadline', label: 'Deadline', color: '#EF4444' },
  { value: 'leave', label: 'Leave', color: '#8B5CF6' },
  { value: 'milestone', label: 'Milestone', color: '#EC4899' },
  { value: 'personal', label: 'Personal', color: '#6B7280' },
  { value: 'company', label: 'Company', color: '#6366F1' },
]

const getColorForType = (type) => {
  const eventType = eventTypes.find(t => t.value === type)
  return eventType ? eventType.color : '#6B7280'
}

const openModal = () => {
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  resetModalFields()
}

const resetModalFields = () => {
  eventTitle.value = ''
  eventDescription.value = ''
  eventStartDate.value = ''
  eventEndDate.value = ''
  eventType.value = 'meeting'
  eventAllDay.value = false
  selectedEvent.value = null
}

const formatDateTimeLocal = (date) => {
  if (!date) return ''
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

const handleDateSelect = (selectInfo) => {
  resetModalFields()
  eventStartDate.value = selectInfo.startStr
  eventEndDate.value = selectInfo.endStr || selectInfo.startStr
  openModal()
}

const handleEventClick = (clickInfo) => {
  const event = clickInfo.event
  selectedEvent.value = event
  eventTitle.value = event.title
  eventDescription.value = event.extendedProps.description || ''
  eventStartDate.value = formatDateTimeLocal(event.start)
  eventEndDate.value = event.end ? formatDateTimeLocal(event.end) : formatDateTimeLocal(event.start)
  eventType.value = event.extendedProps.type
  eventAllDay.value = event.allDay
  openModal()
}

const handleEventDrop = async (info) => {
  try {
    await put(`/api/v1/events/${info.event.id}`, {
      start_at: info.event.start.toISOString(),
      end_at: info.event.end ? info.event.end.toISOString() : info.event.start.toISOString(),
    })
    showToast('Event rescheduled', 'success')
    await fetchEvents()
  } catch (error) {
    info.revert()
    showToast('Failed to reschedule event', 'error')
  }
}

const handleEventResize = async (info) => {
  try {
    await put(`/api/v1/events/${info.event.id}`, {
      start_at: info.event.start.toISOString(),
      end_at: info.event.end ? info.event.end.toISOString() : info.event.start.toISOString(),
    })
    showToast('Event updated', 'success')
    await fetchEvents()
  } catch (error) {
    info.revert()
    showToast('Failed to update event', 'error')
  }
}

const fetchCalendars = async () => {
  try {
    const response = await get('/api/v1/calendar')
    calendars.value = response.data || response || []
    
    // Auto-select first calendar if none selected
    if (!selectedCalendarId.value && calendars.value.length > 0) {
      selectedCalendarId.value = calendars.value[0].id
    }
  } catch (error) {
    console.error('Failed to load calendars:', error)
    calendars.value = []
    // Don't show toast on initial load to avoid noise
  }
}

const fetchEvents = async () => {
  try {
    // Get current month range from FullCalendar
    const calendarApi = calendarRef.value?.getApi()
    const view = calendarApi?.view
    
    let startDate, endDate
    if (view) {
      startDate = view.activeStart
      endDate = view.activeEnd
    } else {
      // Fallback to current month
      const now = new Date()
      startDate = new Date(now.getFullYear(), now.getMonth(), 1)
      endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    }
    
    const response = await get('/api/v1/calendar/user-events', {
      start_date: startDate.toISOString().split('T')[0],
      end_date: endDate.toISOString().split('T')[0],
      calendar_id: selectedCalendarId.value
    })
    
    events.value = response.data || response || []
  } catch (error) {
    console.error('Failed to load events:', error)
    events.value = []
    // Don't show toast on initial load to avoid noise
  }
}

const toggleEventType = (type) => {
  const index = selectedEventTypes.value.indexOf(type)
  if (index > -1) {
    selectedEventTypes.value.splice(index, 1)
  } else {
    selectedEventTypes.value.push(type)
  }
}

const filteredCalendarEvents = computed(() => {
  return events.value
    .filter(event => selectedEventTypes.value.includes(event.type))
    .map(event => ({
      id: event.id,
      title: event.title,
      start: event.start_at,
      end: event.end_at,
      allDay: event.all_day,
      backgroundColor: getColorForType(event.type),
      borderColor: getColorForType(event.type),
      extendedProps: {
        description: event.description,
        type: event.type,
        status: event.status,
        calendar_id: event.calendar_id,
        originalEvent: event
      }
    }))
})

const calendarOptions = reactive({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay',
  },
  events: filteredCalendarEvents,
  selectable: true,
  select: handleDateSelect,
  eventClick: handleEventClick,
  editable: true,
  eventDrop: handleEventDrop,
  eventResize: handleEventResize,
  height: 'auto',
  contentHeight: 'auto',
})

const handleSaveEvent = async () => {
  if (!eventTitle.value || !eventStartDate.value) {
    showToast('Please fill in required fields', 'error')
    return
  }

  try {
    const eventData = {
      calendar_id: selectedCalendarId.value || calendars.value[0]?.id,
      title: eventTitle.value,
      description: eventDescription.value,
      start_at: eventStartDate.value,
      end_at: eventEndDate.value,
      all_day: eventAllDay.value,
      type: eventType.value,
      color: getColorForType(eventType.value),
    }

    if (selectedEvent.value) {
      // Update existing event
      await put(`/api/v1/events/${selectedEvent.value.id}`, eventData)
      showToast('Event updated successfully', 'success')
    } else {
      // Create new event
      await post('/api/v1/events', eventData)
      showToast('Event created successfully', 'success')
    }

    closeModal()
    await fetchEvents()
  } catch (error) {
    showToast(error.message || 'Failed to save event', 'error')
  }
}

const handleDeleteEvent = async () => {
  if (!selectedEvent.value) return

  if (!confirm('Are you sure you want to delete this event?')) return

  try {
    await del(`/api/v1/events/${selectedEvent.value.id}`)
    showToast('Event deleted successfully', 'success')
    closeModal()
    await fetchEvents()
  } catch (error) {
    showToast('Failed to delete event', 'error')
  }
}

// Watch for calendar changes
watch(selectedCalendarId, () => {
  fetchEvents()
})

onMounted(async () => {
  try {
    await fetchCalendars()
    await fetchEvents()
  } catch (error) {
    console.error('Error initializing calendar:', error)
  }
})
</script>

<style>
/* FullCalendar custom styles */
.custom-calendar .fc {
  font-family: inherit;
}

.custom-calendar .fc-button {
  background-color: #4f46e5;
  border-color: #4f46e5;
  text-transform: capitalize;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.custom-calendar .fc-button:hover {
  background-color: #4338ca;
  border-color: #4338ca;
}

.custom-calendar .fc-button-active {
  background-color: #3730a3 !important;
  border-color: #3730a3 !important;
}

.custom-calendar .fc-daygrid-day-number {
  padding: 0.5rem;
  font-weight: 500;
}

.custom-calendar .fc-daygrid-day.fc-day-today {
  background-color: #eef2ff;
}

.custom-calendar .fc-event {
  border-radius: 0.25rem;
  padding: 0.125rem 0.25rem;
  font-size: 0.75rem;
  cursor: pointer;
}

.custom-calendar .fc-event:hover {
  opacity: 0.8;
}

.custom-calendar .fc-toolbar-title {
  font-size: 1.5rem;
  font-weight: 600;
}
</style>
