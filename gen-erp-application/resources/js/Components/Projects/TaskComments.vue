<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">
          Comments ({{ comments.length }})
        </h3>
        <button
          @click="collapsed = !collapsed"
          class="text-gray-400 hover:text-gray-600"
        >
          <ChevronUpIcon v-if="!collapsed" class="h-4 w-4" />
          <ChevronDownIcon v-else class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Add Comment Form -->
      <div class="mb-6">
        <div class="flex space-x-3">
          <div class="flex-shrink-0">
            <img
              class="h-8 w-8 rounded-full"
              :src="currentUser?.avatar || '/default-avatar.png'"
              :alt="currentUser?.name"
            />
          </div>
          <div class="flex-1">
            <form @submit.prevent="addComment">
              <textarea
                v-model="newComment"
                rows="3"
                placeholder="Add a comment..."
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                :disabled="loading"
              ></textarea>
              <div class="mt-2 flex justify-between items-center">
                <div class="flex items-center space-x-2">
                  <button
                    type="button"
                    @click="showEmojiPicker = !showEmojiPicker"
                    class="text-gray-400 hover:text-gray-600"
                  >
                    <FaceSmileIcon class="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    @click="$refs.fileInput.click()"
                    class="text-gray-400 hover:text-gray-600"
                  >
                    <PaperClipIcon class="h-4 w-4" />
                  </button>
                  <input
                    ref="fileInput"
                    type="file"
                    multiple
                    class="hidden"
                    @change="handleFileUpload"
                  />
                </div>
                <div class="flex space-x-2">
                  <button
                    type="button"
                    @click="newComment = ''"
                    :disabled="!newComment.trim() || loading"
                    class="text-sm text-gray-500 hover:text-gray-700 disabled:opacity-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="!newComment.trim() || loading"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-1 px-3 rounded-md"
                  >
                    {{ loading ? 'Posting...' : 'Comment' }}
                  </button>
                </div>
              </div>
            </form>
            
            <!-- File Attachments Preview -->
            <div v-if="attachments.length > 0" class="mt-2 flex flex-wrap gap-2">
              <div
                v-for="(file, index) in attachments"
                :key="index"
                class="flex items-center space-x-2 bg-gray-100 rounded-md px-2 py-1 text-xs"
              >
                <span>{{ file.name }}</span>
                <button
                  @click="removeAttachment(index)"
                  class="text-gray-400 hover:text-gray-600"
                >
                  <XMarkIcon class="h-3 w-3" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Comments List -->
      <div class="space-y-4">
        <div
          v-for="comment in comments"
          :key="comment.id"
          class="flex space-x-3"
        >
          <div class="flex-shrink-0">
            <img
              class="h-8 w-8 rounded-full"
              :src="comment.user?.avatar || '/default-avatar.png'"
              :alt="comment.user?.name"
            />
          </div>
          <div class="flex-1">
            <div class="bg-gray-50 rounded-lg px-3 py-2">
              <div class="flex items-center justify-between mb-1">
                <div class="flex items-center space-x-2">
                  <span class="text-sm font-medium text-gray-900">
                    {{ comment.user?.name }}
                  </span>
                  <span class="text-xs text-gray-500">
                    {{ formatDate(comment.created_at) }}
                  </span>
                </div>
                <div v-if="canEditComment(comment)" class="flex items-center space-x-1">
                  <button
                    @click="editComment(comment)"
                    class="text-gray-400 hover:text-gray-600"
                  >
                    <PencilIcon class="h-3 w-3" />
                  </button>
                  <button
                    @click="deleteComment(comment)"
                    class="text-gray-400 hover:text-red-600"
                  >
                    <TrashIcon class="h-3 w-3" />
                  </button>
                </div>
              </div>
              
              <!-- Comment Content -->
              <div v-if="editingComment?.id !== comment.id" class="text-sm text-gray-700">
                <div v-html="formatCommentContent(comment.content)"></div>
                
                <!-- Attachments -->
                <div v-if="comment.attachments && comment.attachments.length > 0" class="mt-2 space-y-1">
                  <div
                    v-for="attachment in comment.attachments"
                    :key="attachment.id"
                    class="flex items-center space-x-2 text-xs text-gray-600"
                  >
                    <PaperClipIcon class="h-3 w-3" />
                    <a
                      :href="attachment.url"
                      target="_blank"
                      class="hover:text-indigo-600"
                    >
                      {{ attachment.name }}
                    </a>
                    <span class="text-gray-400">({{ formatFileSize(attachment.size) }})</span>
                  </div>
                </div>
              </div>
              
              <!-- Edit Form -->
              <div v-else class="space-y-2">
                <textarea
                  v-model="editingContent"
                  rows="2"
                  class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                ></textarea>
                <div class="flex justify-end space-x-2">
                  <button
                    @click="cancelEdit"
                    class="text-xs text-gray-500 hover:text-gray-700"
                  >
                    Cancel
                  </button>
                  <button
                    @click="saveEdit"
                    :disabled="!editingContent.trim() || loading"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-xs font-medium py-1 px-2 rounded-md"
                  >
                    Save
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Reactions -->
            <div class="mt-1 flex items-center space-x-2">
              <button
                v-for="reaction in comment.reactions"
                :key="reaction.emoji"
                @click="toggleReaction(comment, reaction.emoji)"
                class="flex items-center space-x-1 text-xs text-gray-500 hover:text-gray-700"
                :class="{ 'text-indigo-600': reaction.user_reacted }"
              >
                <span>{{ reaction.emoji }}</span>
                <span>{{ reaction.count }}</span>
              </button>
              <button
                @click="showReactionPicker(comment)"
                class="text-xs text-gray-400 hover:text-gray-600"
              >
                <FaceSmileIcon class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="comments.length === 0" class="text-center py-6">
        <ChatBubbleLeftIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No comments yet</h3>
        <p class="mt-1 text-sm text-gray-500">Be the first to comment on this task.</p>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="mt-4 text-center">
        <button
          @click="loadMore"
          :disabled="loading"
          class="text-sm text-indigo-600 hover:text-indigo-500 disabled:opacity-50"
        >
          {{ loading ? 'Loading...' : 'Load More Comments' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  FaceSmileIcon,
  PaperClipIcon,
  XMarkIcon,
  PencilIcon,
  TrashIcon,
  ChatBubbleLeftIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useAuth } from '@/Composables/useAuth'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  taskId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['comment-added', 'comment-updated', 'comment-deleted'])

const { get, post, put, delete: del, loading } = useApi()
const { user: currentUser } = useAuth()
const { showSuccess, showError } = useToast()

// Reactive data
const collapsed = ref(false)
const comments = ref([])
const newComment = ref('')
const attachments = ref([])
const editingComment = ref(null)
const editingContent = ref('')
const currentPage = ref(1)
const hasMore = ref(false)
const showEmojiPicker = ref(false)

// Methods
const fetchComments = async (page = 1) => {
  try {
    const data = await get(`/api/v1/tasks/${props.taskId}/comments`, {
      page,
      per_page: 10
    })
    
    if (page === 1) {
      comments.value = data.data
    } else {
      comments.value.push(...data.data)
    }
    
    currentPage.value = page
    hasMore.value = data.meta?.has_more_pages || false
  } catch (err) {
    console.error('Failed to fetch comments:', err)
  }
}

const addComment = async () => {
  if (!newComment.value.trim()) return
  
  try {
    const formData = new FormData()
    formData.append('content', newComment.value)
    
    attachments.value.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file)
    })
    
    const data = await post(`/api/v1/tasks/${props.taskId}/comments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    comments.value.unshift(data.data)
    newComment.value = ''
    attachments.value = []
    
    showSuccess('Comment added successfully')
    emit('comment-added', data.data)
  } catch (err) {
    console.error('Failed to add comment:', err)
    showError('Failed to add comment')
  }
}

const editComment = (comment) => {
  editingComment.value = comment
  editingContent.value = comment.content
}

const cancelEdit = () => {
  editingComment.value = null
  editingContent.value = ''
}

const saveEdit = async () => {
  if (!editingContent.value.trim()) return
  
  try {
    const data = await put(`/api/v1/comments/${editingComment.value.id}`, {
      content: editingContent.value
    })
    
    const index = comments.value.findIndex(c => c.id === editingComment.value.id)
    if (index > -1) {
      comments.value[index] = data.data
    }
    
    editingComment.value = null
    editingContent.value = ''
    
    showSuccess('Comment updated successfully')
    emit('comment-updated', data.data)
  } catch (err) {
    console.error('Failed to update comment:', err)
    showError('Failed to update comment')
  }
}

const deleteComment = async (comment) => {
  if (!confirm('Are you sure you want to delete this comment?')) return
  
  try {
    await del(`/api/v1/comments/${comment.id}`)
    
    const index = comments.value.findIndex(c => c.id === comment.id)
    if (index > -1) {
      comments.value.splice(index, 1)
    }
    
    showSuccess('Comment deleted successfully')
    emit('comment-deleted', comment)
  } catch (err) {
    console.error('Failed to delete comment:', err)
    showError('Failed to delete comment')
  }
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  attachments.value.push(...files)
  event.target.value = '' // Reset input
}

const removeAttachment = (index) => {
  attachments.value.splice(index, 1)
}

const loadMore = () => {
  fetchComments(currentPage.value + 1)
}

const canEditComment = (comment) => {
  return currentUser.value?.id === comment.user_id
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else {
    return date.toLocaleDateString()
  }
}

const formatCommentContent = (content) => {
  // Simple formatting: convert URLs to links and line breaks to <br>
  return content
    .replace(/\n/g, '<br>')
    .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-indigo-600 hover:text-indigo-500">$1</a>')
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const toggleReaction = async (comment, emoji) => {
  try {
    await post(`/api/v1/comments/${comment.id}/reactions`, { emoji })
    // Refresh comments to get updated reactions
    fetchComments()
  } catch (err) {
    console.error('Failed to toggle reaction:', err)
  }
}

const showReactionPicker = (comment) => {
  // Simple emoji reactions
  const emojis = ['👍', '👎', '❤️', '😄', '😮', '😢', '😡']
  // In a real implementation, you'd show a proper emoji picker
  const emoji = prompt('Choose an emoji: ' + emojis.join(' '))
  if (emoji && emojis.includes(emoji)) {
    toggleReaction(comment, emoji)
  }
}

// Lifecycle
onMounted(() => {
  fetchComments()
})
</script>