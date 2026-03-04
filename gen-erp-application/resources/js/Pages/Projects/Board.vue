<template>
  <AppLayout title="Project Board">
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ project?.name }} - Kanban Board
          </h2>
          <p class="text-sm text-gray-600 mt-1">{{ project?.description }}</p>
        </div>
        <div class="flex space-x-3">
          <button @click="showCreateTaskModal = true"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Add Task
          </button>
          <Link :href="route('projects.show', projectId)" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Back to Project
          </Link>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-500">Loading board...</p>
        </div>

        <div v-else class="bg-white rounded-lg shadow overflow-hidden">
          <!-- Board Selector -->
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <h3 class="text-lg font-medium text-gray-900">{{ board?.name || 'Project Board' }}</h3>
                <span class="text-sm text-gray-500">{{ tasks.length }} tasks</span>
              </div>
              <div class="flex items-center space-x-2">
                <select v-model="selectedBoard" @change="loadBoard" 
                        class="text-sm border-gray-300 rounded-md">
                  <option v-for="b in boards" :key="b.id" :value="b.id">
                    {{ b.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Kanban Board Component -->
          <div class="p-6">
            <KanbanBoard
              :title="board?.name || 'Project Board'"
              :subtitle="`${tasks.length} tasks across ${columns.length} columns`"
              :columns="formattedColumns"
              :items="formattedTasks"
              item-type="task"
              column-key="board_column_id"
              @item-moved="handleTaskMoved"
              @item-add="handleAddTask"
              @column-add="handleAddColumn"
              @column-edit="handleEditColumn"
              @column-delete="handleDeleteColumn"
            >
              <!-- Custom Task Item -->
              <template #item="{ item, column }">
                <TaskCard
                  :task="item"
                  :project-id="projectId"
                  @click="openTaskModal"
                  @updated="loadBoard"
                  @deleted="loadBoard"
                  @edit="openTaskEditModal"
                  @move="handleMoveTask"
                />
              </template>

              <!-- Board Actions -->
              <template #actions>
                <button
                  @click="showCreateTaskModal = true"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                >
                  Add Task
                </button>
                <button
                  @click="showBoardSettings = true"
                  class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                >
                  Board Settings
                </button>
              </template>
            </KanbanBoard>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Task Modal -->
    <CreateTaskModal 
      :show="showCreateTaskModal"
      :project-id="projectId"
      :column-id="selectedColumn"
      @close="showCreateTaskModal = false"
      @created="handleTaskCreated"
    />

    <!-- Task Detail Modal -->
    <TaskDetailModal 
      :show="showTaskModal"
      :task="selectedTask"
      @close="showTaskModal = false"
      @updated="handleTaskUpdated"
    />

    <!-- Board Settings Modal -->
    <BoardSettingsModal
      :show="showBoardSettings"
      :board="board"
      :columns="columns"
      @close="showBoardSettings = false"
      @updated="loadBoard"
    />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import KanbanBoard from '@/Components/Projects/KanbanBoard.vue'
import TaskCard from '@/Components/Projects/TaskCard.vue'
import CreateTaskModal from '@/Components/Projects/CreateTaskModal.vue'
import TaskDetailModal from '@/Components/Projects/TaskDetailModal.vue'
import BoardSettingsModal from '@/Components/Projects/BoardSettingsModal.vue'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  projectId: [String, Number]
})

const { get, post, put, delete: del } = useApi()
const { showToast } = useToast()

const project = ref(null)
const boards = ref([])
const board = ref(null)
const columns = ref([])
const tasks = ref([])
const loading = ref(true)
const selectedBoard = ref(null)
const selectedColumn = ref(null)
const selectedTask = ref(null)
const showCreateTaskModal = ref(false)
const showTaskModal = ref(false)
const showBoardSettings = ref(false)

// Computed properties for formatted data
const formattedColumns = computed(() => {
  return columns.value.map(column => ({
    id: column.id,
    title: column.name,
    description: column.description,
    color: column.color || 'bg-gray-100',
    wip_limit: column.wip_limit
  }))
})

const formattedTasks = computed(() => {
  return tasks.value.map(task => ({
    ...task,
    board_column_id: task.board_column_id,
    assignee: task.assignee ? {
      name: `${task.assignee.first_name} ${task.assignee.last_name}`,
      first_name: task.assignee.first_name,
      last_name: task.assignee.last_name
    } : null
  }))
})

onMounted(async () => {
  await fetchProject()
  await fetchBoards()
  if (boards.value.length > 0) {
    selectedBoard.value = boards.value[0].id
    await loadBoard()
  }
})

const fetchProject = async () => {
  try {
    const data = await get(`/api/v1/projects/${props.projectId}`)
    project.value = data.data
  } catch (error) {
    console.error('Failed to fetch project:', error)
    showToast('Failed to load project', 'error')
  }
}

const fetchBoards = async () => {
  try {
    const data = await get(`/api/v1/projects/${props.projectId}/boards`)
    boards.value = data.data
  } catch (error) {
    console.error('Failed to fetch boards:', error)
    showToast('Failed to load boards', 'error')
  }
}

const loadBoard = async () => {
  if (!selectedBoard.value) return
  
  loading.value = true
  try {
    const data = await get(`/api/v1/boards/${selectedBoard.value}`)
    board.value = data.data
    columns.value = data.data.columns || []
    tasks.value = data.data.tasks || []
  } catch (error) {
    console.error('Failed to load board:', error)
    showToast('Failed to load board', 'error')
  } finally {
    loading.value = false
  }
}

// Kanban Board Event Handlers
const handleTaskMoved = async ({ item, fromColumn, toColumn }) => {
  try {
    await post(`/api/v1/tasks/${item.id}/move`, {
      board_column_id: toColumn
    })
    showToast('Task moved successfully', 'success')
    await loadBoard()
  } catch (error) {
    console.error('Failed to move task:', error)
    showToast('Failed to move task', 'error')
  }
}

const handleAddTask = (columnId) => {
  selectedColumn.value = columnId
  showCreateTaskModal.value = true
}

const handleAddColumn = async (columnData) => {
  try {
    await post(`/api/v1/boards/${selectedBoard.value}/columns`, {
      name: columnData.title,
      description: columnData.description,
      color: columnData.color
    })
    showToast('Column added successfully', 'success')
    await loadBoard()
  } catch (error) {
    console.error('Failed to add column:', error)
    showToast('Failed to add column', 'error')
  }
}

const handleEditColumn = async (columnData) => {
  try {
    await put(`/api/v1/board-columns/${columnData.id}`, {
      name: columnData.title,
      description: columnData.description,
      color: columnData.color
    })
    showToast('Column updated successfully', 'success')
    await loadBoard()
  } catch (error) {
    console.error('Failed to update column:', error)
    showToast('Failed to update column', 'error')
  }
}

const handleDeleteColumn = async (columnId) => {
  try {
    await del(`/api/v1/board-columns/${columnId}`)
    showToast('Column deleted successfully', 'success')
    await loadBoard()
  } catch (error) {
    console.error('Failed to delete column:', error)
    showToast('Failed to delete column', 'error')
  }
}

const openTaskModal = (task) => {
  selectedTask.value = task
  showTaskModal.value = true
}

const openTaskEditModal = (task) => {
  // TODO: Implement task edit modal
  showToast('Task edit functionality coming soon', 'info')
}

const handleMoveTask = (task) => {
  // TODO: Implement task move modal
  showToast('Task move functionality coming soon', 'info')
}

const handleTaskCreated = () => {
  showCreateTaskModal.value = false
  selectedColumn.value = null
  loadBoard()
  showToast('Task created successfully', 'success')
}

const handleTaskUpdated = () => {
  showTaskModal.value = false
  selectedTask.value = null
  loadBoard()
  showToast('Task updated successfully', 'success')
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>