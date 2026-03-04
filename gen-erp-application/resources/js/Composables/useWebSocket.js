import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useWebSocket(channels = []) {
  const connected = ref(false)
  const connecting = ref(false)
  const error = ref(null)
  const socket = ref(null)
  const listeners = ref(new Map())

  const connect = () => {
    if (socket.value?.readyState === WebSocket.OPEN) return

    connecting.value = true
    error.value = null

    try {
      const page = usePage()
      const token = page.props.auth?.api_token || document.querySelector('meta[name="api-token"]')?.content
      const wsUrl = `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws`
      
      socket.value = new WebSocket(`${wsUrl}?token=${token}`)

      socket.value.onopen = () => {
        connected.value = true
        connecting.value = false
        console.log('WebSocket connected')

        // Subscribe to channels
        channels.forEach(channel => {
          subscribe(channel)
        })
      }

      socket.value.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data)
          handleMessage(data)
        } catch (err) {
          console.error('Failed to parse WebSocket message:', err)
        }
      }

      socket.value.onclose = (event) => {
        connected.value = false
        connecting.value = false
        
        if (event.code !== 1000) {
          console.log('WebSocket disconnected, attempting to reconnect...')
          setTimeout(connect, 3000) // Reconnect after 3 seconds
        }
      }

      socket.value.onerror = (err) => {
        error.value = 'WebSocket connection failed'
        connecting.value = false
        console.error('WebSocket error:', err)
      }

    } catch (err) {
      error.value = err.message
      connecting.value = false
    }
  }

  const disconnect = () => {
    if (socket.value) {
      socket.value.close(1000, 'Client disconnect')
      socket.value = null
    }
    connected.value = false
    connecting.value = false
  }

  const subscribe = (channel) => {
    if (!connected.value) {
      console.warn('Cannot subscribe: WebSocket not connected')
      return
    }

    const message = {
      type: 'subscribe',
      channel: channel
    }

    socket.value.send(JSON.stringify(message))
  }

  const unsubscribe = (channel) => {
    if (!connected.value) return

    const message = {
      type: 'unsubscribe',
      channel: channel
    }

    socket.value.send(JSON.stringify(message))
  }

  const on = (event, callback) => {
    if (!listeners.value.has(event)) {
      listeners.value.set(event, [])
    }
    listeners.value.get(event).push(callback)
  }

  const off = (event, callback) => {
    if (!listeners.value.has(event)) return

    const eventListeners = listeners.value.get(event)
    const index = eventListeners.indexOf(callback)
    if (index > -1) {
      eventListeners.splice(index, 1)
    }
  }

  const emit = (event, data) => {
    if (!connected.value) {
      console.warn('Cannot emit: WebSocket not connected')
      return
    }

    const message = {
      type: 'event',
      event: event,
      data: data
    }

    socket.value.send(JSON.stringify(message))
  }

  const handleMessage = (data) => {
    const { type, event, channel, data: payload } = data

    switch (type) {
      case 'event':
        if (listeners.value.has(event)) {
          listeners.value.get(event).forEach(callback => {
            callback(payload)
          })
        }
        break

      case 'channel_event':
        const channelEvent = `${channel}:${event}`
        if (listeners.value.has(channelEvent)) {
          listeners.value.get(channelEvent).forEach(callback => {
            callback(payload)
          })
        }
        break

      case 'error':
        console.error('WebSocket server error:', payload)
        error.value = payload.message
        break

      default:
        console.log('Unknown message type:', type)
    }
  }

  // Auto-connect on mount
  onMounted(() => {
    connect()
  })

  // Cleanup on unmount
  onUnmounted(() => {
    disconnect()
  })

  return {
    connected,
    connecting,
    error,
    connect,
    disconnect,
    subscribe,
    unsubscribe,
    on,
    off,
    emit
  }
}

// Project-specific WebSocket composable
export function useProjectWebSocket(projectId) {
  const channels = [
    `project.${projectId}`,
    `project.${projectId}.tasks`,
    `project.${projectId}.members`
  ]

  const ws = useWebSocket(channels)

  // Project-specific event handlers
  const onTaskCreated = (callback) => {
    ws.on(`project.${projectId}.tasks:task.created`, callback)
  }

  const onTaskUpdated = (callback) => {
    ws.on(`project.${projectId}.tasks:task.updated`, callback)
  }

  const onTaskDeleted = (callback) => {
    ws.on(`project.${projectId}.tasks:task.deleted`, callback)
  }

  const onTaskMoved = (callback) => {
    ws.on(`project.${projectId}.tasks:task.moved`, callback)
  }

  const onMemberAdded = (callback) => {
    ws.on(`project.${projectId}.members:member.added`, callback)
  }

  const onMemberRemoved = (callback) => {
    ws.on(`project.${projectId}.members:member.removed`, callback)
  }

  const onProjectUpdated = (callback) => {
    ws.on(`project.${projectId}:project.updated`, callback)
  }

  // Emit project events
  const emitTaskUpdate = (taskId, data) => {
    ws.emit('task.update', { project_id: projectId, task_id: taskId, ...data })
  }

  const emitTaskMove = (taskId, fromStatus, toStatus) => {
    ws.emit('task.move', { 
      project_id: projectId, 
      task_id: taskId, 
      from_status: fromStatus, 
      to_status: toStatus 
    })
  }

  return {
    ...ws,
    onTaskCreated,
    onTaskUpdated,
    onTaskDeleted,
    onTaskMoved,
    onMemberAdded,
    onMemberRemoved,
    onProjectUpdated,
    emitTaskUpdate,
    emitTaskMove
  }
}

// CRM-specific WebSocket composable
export function useCRMWebSocket() {
  const channels = [
    'crm.leads',
    'crm.opportunities',
    'crm.activities'
  ]

  const ws = useWebSocket(channels)

  // CRM-specific event handlers
  const onLeadCreated = (callback) => {
    ws.on('crm.leads:lead.created', callback)
  }

  const onLeadUpdated = (callback) => {
    ws.on('crm.leads:lead.updated', callback)
  }

  const onLeadStatusChanged = (callback) => {
    ws.on('crm.leads:lead.status_changed', callback)
  }

  const onOpportunityCreated = (callback) => {
    ws.on('crm.opportunities:opportunity.created', callback)
  }

  const onOpportunityUpdated = (callback) => {
    ws.on('crm.opportunities:opportunity.updated', callback)
  }

  const onActivityCreated = (callback) => {
    ws.on('crm.activities:activity.created', callback)
  }

  // Emit CRM events
  const emitLeadUpdate = (leadId, data) => {
    ws.emit('lead.update', { lead_id: leadId, ...data })
  }

  const emitOpportunityUpdate = (opportunityId, data) => {
    ws.emit('opportunity.update', { opportunity_id: opportunityId, ...data })
  }

  return {
    ...ws,
    onLeadCreated,
    onLeadUpdated,
    onLeadStatusChanged,
    onOpportunityCreated,
    onOpportunityUpdated,
    onActivityCreated,
    emitLeadUpdate,
    emitOpportunityUpdate
  }
}