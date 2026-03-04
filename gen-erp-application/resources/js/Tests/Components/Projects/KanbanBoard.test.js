import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import KanbanBoard from '@/Components/Projects/KanbanBoard.vue'

// Mock drag and drop API
Object.defineProperty(window, 'DataTransfer', {
  value: class {
    constructor() {
      this.data = {}
    }
    setData(type, data) {
      this.data[type] = data
    }
    getData(type) {
      return this.data[type]
    }
  }
})

describe('KanbanBoard', () => {
  let wrapper
  
  const mockColumns = [
    {
      id: 1,
      title: 'To Do',
      color: '#gray',
      order: 0,
      tasks: [
        { id: 1, title: 'Task 1', priority: 'high', assignee: { name: 'John' } },
        { id: 2, title: 'Task 2', priority: 'medium', assignee: { name: 'Jane' } }
      ]
    },
    {
      id: 2,
      title: 'In Progress',
      color: '#blue',
      order: 1,
      tasks: [
        { id: 3, title: 'Task 3', priority: 'low', assignee: { name: 'Bob' } }
      ]
    },
    {
      id: 3,
      title: 'Done',
      color: '#green',
      order: 2,
      tasks: []
    }
  ]

  beforeEach(() => {
    wrapper = mount(KanbanBoard, {
      props: {
        columns: mockColumns,
        projectId: 1
      }
    })
  })

  it('renders all columns', () => {
    const columns = wrapper.findAll('[data-testid="kanban-column"]')
    expect(columns).toHaveLength(3)
    
    expect(wrapper.text()).toContain('To Do')
    expect(wrapper.text()).toContain('In Progress')
    expect(wrapper.text()).toContain('Done')
  })

  it('renders tasks in correct columns', () => {
    const firstColumn = wrapper.findAll('[data-testid="kanban-column"]')[0]
    const tasks = firstColumn.findAll('[data-testid="task-card"]')
    
    expect(tasks).toHaveLength(2)
    expect(firstColumn.text()).toContain('Task 1')
    expect(firstColumn.text()).toContain('Task 2')
  })

  it('shows task count in column headers', () => {
    const firstColumnHeader = wrapper.findAll('[data-testid="column-header"]')[0]
    expect(firstColumnHeader.text()).toContain('2') // 2 tasks in To Do
    
    const secondColumnHeader = wrapper.findAll('[data-testid="column-header"]')[1]
    expect(secondColumnHeader.text()).toContain('1') // 1 task in In Progress
  })

  it('handles task drag start', async () => {
    const taskCard = wrapper.find('[data-testid="task-card"]')
    const dragEvent = new Event('dragstart')
    dragEvent.dataTransfer = new DataTransfer()
    
    await taskCard.element.dispatchEvent(dragEvent)
    
    expect(dragEvent.dataTransfer.getData('text/plain')).toBe('1') // task id
  })

  it('handles task drop between columns', async () => {
    const targetColumn = wrapper.findAll('[data-testid="kanban-column"]')[2] // Done column
    
    const dropEvent = new Event('drop')
    dropEvent.dataTransfer = new DataTransfer()
    dropEvent.dataTransfer.setData('text/plain', '1') // task id
    dropEvent.preventDefault = vi.fn()
    
    await targetColumn.element.dispatchEvent(dropEvent)
    
    expect(wrapper.emitted('task-moved')).toBeTruthy()
    expect(wrapper.emitted('task-moved')[0]).toEqual([{
      taskId: 1,
      fromColumnId: 1,
      toColumnId: 3,
      newIndex: 0
    }])
  })

  it('shows add task button in each column', () => {
    const addButtons = wrapper.findAll('[data-testid="add-task-btn"]')
    expect(addButtons).toHaveLength(3)
  })

  it('opens create task modal when add button clicked', async () => {
    const addButton = wrapper.find('[data-testid="add-task-btn"]')
    await addButton.trigger('click')
    
    expect(wrapper.emitted('create-task')).toBeTruthy()
    expect(wrapper.emitted('create-task')[0]).toEqual([1]) // column id
  })

  it('handles task card click', async () => {
    const taskCard = wrapper.find('[data-testid="task-card"]')
    await taskCard.trigger('click')
    
    expect(wrapper.emitted('task-clicked')).toBeTruthy()
    expect(wrapper.emitted('task-clicked')[0][0]).toEqual(mockColumns[0].tasks[0])
  })

  it('shows column settings menu', async () => {
    const columnMenu = wrapper.find('[data-testid="column-menu"]')
    await columnMenu.trigger('click')
    
    expect(wrapper.find('[data-testid="column-settings"]').exists()).toBe(true)
  })

  it('handles column reordering', async () => {
    const column = wrapper.find('[data-testid="kanban-column"]')
    
    const dragEvent = new Event('dragstart')
    dragEvent.dataTransfer = new DataTransfer()
    await column.element.dispatchEvent(dragEvent)
    
    const dropEvent = new Event('drop')
    dropEvent.dataTransfer = new DataTransfer()
    dropEvent.dataTransfer.setData('text/plain', 'column-1')
    dropEvent.preventDefault = vi.fn()
    
    const targetColumn = wrapper.findAll('[data-testid="kanban-column"]')[1]
    await targetColumn.element.dispatchEvent(dropEvent)
    
    expect(wrapper.emitted('column-moved')).toBeTruthy()
  })

  it('shows loading state', async () => {
    await wrapper.setProps({ loading: true })
    expect(wrapper.find('.animate-spin').exists()).toBe(true)
  })

  it('handles empty columns', () => {
    const doneColumn = wrapper.findAll('[data-testid="kanban-column"]')[2]
    expect(doneColumn.text()).toContain('No tasks')
  })

  it('applies priority colors to task cards', () => {
    const taskCards = wrapper.findAll('[data-testid="task-card"]')
    
    // High priority task should have red border
    expect(taskCards[0].classes()).toContain('border-red-200')
    
    // Medium priority task should have yellow border
    expect(taskCards[1].classes()).toContain('border-yellow-200')
  })

  it('shows assignee avatars', () => {
    const taskCard = wrapper.find('[data-testid="task-card"]')
    expect(taskCard.text()).toContain('John')
  })

  it('handles column color customization', async () => {
    await wrapper.setProps({
      columns: [{
        ...mockColumns[0],
        color: '#purple'
      }]
    })
    
    const columnHeader = wrapper.find('[data-testid="column-header"]')
    expect(columnHeader.attributes('style')).toContain('border-color: #purple')
  })

  it('supports compact view mode', async () => {
    await wrapper.setProps({ compact: true })
    
    const board = wrapper.find('[data-testid="kanban-board"]')
    expect(board.classes()).toContain('compact-mode')
  })

  it('handles task filtering', async () => {
    await wrapper.setProps({
      filters: {
        assignee: 'John',
        priority: 'high'
      }
    })
    
    // Should only show tasks matching filters
    const visibleTasks = wrapper.findAll('[data-testid="task-card"]:not(.hidden)')
    expect(visibleTasks).toHaveLength(1)
  })

  it('shows task due dates', () => {
    const taskWithDueDate = {
      ...mockColumns[0].tasks[0],
      due_date: '2024-12-31'
    }
    
    wrapper.setProps({
      columns: [{
        ...mockColumns[0],
        tasks: [taskWithDueDate]
      }]
    })
    
    const taskCard = wrapper.find('[data-testid="task-card"]')
    expect(taskCard.text()).toContain('Dec 31')
  })

  it('handles task quick actions', async () => {
    const quickActionBtn = wrapper.find('[data-testid="task-quick-action"]')
    await quickActionBtn.trigger('click')
    
    expect(wrapper.find('[data-testid="quick-actions-menu"]').exists()).toBe(true)
  })

  it('supports keyboard navigation', async () => {
    const taskCard = wrapper.find('[data-testid="task-card"]')
    
    await taskCard.trigger('keydown', { key: 'Enter' })
    expect(wrapper.emitted('task-clicked')).toBeTruthy()
    
    await taskCard.trigger('keydown', { key: 'Delete' })
    expect(wrapper.emitted('task-delete')).toBeTruthy()
  })

  it('shows column limits', async () => {
    await wrapper.setProps({
      columns: [{
        ...mockColumns[0],
        limit: 3
      }]
    })
    
    const columnHeader = wrapper.find('[data-testid="column-header"]')
    expect(columnHeader.text()).toContain('2/3') // current/limit
  })

  it('prevents drop when column limit exceeded', async () => {
    await wrapper.setProps({
      columns: [{
        ...mockColumns[0],
        limit: 2 // Already has 2 tasks
      }]
    })
    
    const column = wrapper.find('[data-testid="kanban-column"]')
    const dropEvent = new Event('drop')
    dropEvent.dataTransfer = new DataTransfer()
    dropEvent.dataTransfer.setData('text/plain', '3') // task from another column
    
    await column.element.dispatchEvent(dropEvent)
    
    expect(wrapper.emitted('task-moved')).toBeFalsy()
  })
})