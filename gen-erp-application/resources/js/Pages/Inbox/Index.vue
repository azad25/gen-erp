<template>
  <AppLayout title="Inbox">
    <div class="p-4 md:p-6">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inbox</h1>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Manage your conversations and messages
          </p>
        </div>
        <button
          @click="showNewMessageModal = true"
          class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New Message
        </button>
      </div>

      <!-- Main Content -->
      <div class="bg-white dark:bg-gray-900 rounded-lg shadow">
        <div class="grid grid-cols-12 divide-x divide-gray-200 dark:divide-gray-800">
          <!-- Sidebar -->
          <div class="col-span-12 lg:col-span-4 xl:col-span-3">
            <!-- Search & Filters -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
              <div class="relative mb-3">
                <input 
                  v-model="searchQuery" 
                  type="text" 
                  placeholder="Search conversations..." 
                  class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10"
                />
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <div class="flex gap-2">
                <button
                  @click="filter = 'all'"
                  :class="[
                    'px-3 py-1.5 rounded-md text-xs font-medium transition-colors',
                    filter === 'all'
                      ? 'bg-indigo-600 text-white'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                  ]"
                >
                  All
                </button>
                <button
                  @click="filter = 'starred'"
                  :class="[
                    'px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1',
                    filter === 'starred'
                      ? 'bg-indigo-600 text-white'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                  ]"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  Starred
                </button>
              </div>
            </div>

            <!-- Conversations List -->
            <div class="overflow-y-auto" style="max-height: calc(100vh - 300px)">
              <div v-if="loading" class="p-8 text-center">
                <div class="animate-spin h-8 w-8 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto"></div>
              </div>
              <div v-else-if="filteredConversations.length === 0" class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No conversations</p>
              </div>
              <div v-else>
                <div
                  v-for="conv in filteredConversations"
                  :key="conv.id"
                  @click="selectConversation(conv)"
                  :class="[
                    'p-4 border-b border-gray-200 dark:border-gray-800 cursor-pointer transition-colors',
                    selectedConversation?.id === conv.id
                      ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-l-indigo-600'
                      : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                  ]"
                >
                  <div class="flex items-start gap-3">
                    <div class="relative flex-shrink-0">
                      <div class="h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold">
                        {{ getInitials(conv.title) }}
                      </div>
                      <span v-if="conv.unread_count > 0" class="absolute -top-1 -right-1 h-5 min-w-[20px] px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-xs font-semibold">
                        {{ conv.unread_count > 99 ? '99+' : conv.unread_count }}
                      </span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between mb-1">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ conv.title }}</h3>
                        <span v-if="conv.last_message" class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ formatTime(conv.last_message_at) }}</span>
                      </div>
                      <p v-if="conv.last_message" class="text-sm text-gray-600 dark:text-gray-400 truncate">
                        <span v-if="conv.last_message.sender.id === $page.props.auth.user.id" class="font-medium">You: </span>
                        {{ conv.last_message.content }}
                      </p>
                      <div class="flex items-center gap-2 mt-1">
                        <svg v-if="conv.is_starred" class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg v-if="conv.is_muted" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <!-- Chat Area -->
          <div v-if="selectedConversation" class="col-span-12 lg:col-span-8 xl:col-span-9 flex flex-col">
            <!-- Chat Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold">
                  {{ getInitials(selectedConversation.title) }}
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedConversation.title }}</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedConversation.participants.length }} participants</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button
                  @click="toggleStar"
                  class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                  :title="selectedConversation.is_starred ? 'Unstar' : 'Star'"
                >
                  <svg v-if="selectedConversation.is_starred" class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                  </svg>
                </button>
                <button
                  @click="toggleMute"
                  class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                  :title="selectedConversation.is_muted ? 'Unmute' : 'Mute'"
                >
                  <svg v-if="selectedConversation.is_muted" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                  </svg>
                  <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                  </svg>
                </button>
                <button
                  v-if="selectedConversation.is_group"
                  @click="showParticipantsModal = true"
                  class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors flex items-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  Manage
                </button>
                <button
                  @click="deleteConversation"
                  class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
                  title="Delete conversation"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Messages -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50 dark:bg-gray-800/50" style="max-height: calc(100vh - 350px)">
              <div v-if="loadingMessages" class="flex items-center justify-center h-full">
                <div class="animate-spin h-8 w-8 border-4 border-indigo-600 border-t-transparent rounded-full"></div>
              </div>
              <div v-else-if="messages.length === 0" class="flex items-center justify-center h-full">
                <div class="text-center">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                  <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No messages yet. Start the conversation!</p>
                </div>
              </div>
              <div v-else v-for="msg in messages" :key="msg.id" :class="msg.sender.id === $page.props.auth.user.id ? 'flex justify-end' : 'flex justify-start'">
                <div :class="[
                  'max-w-[70%] rounded-lg p-3 shadow-sm',
                  msg.sender.id === $page.props.auth.user.id
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700'
                ]">
                  <div v-if="msg.sender.id !== $page.props.auth.user.id" class="text-xs font-semibold mb-1 opacity-70">{{ msg.sender.name }}</div>
                  <p class="text-sm whitespace-pre-wrap break-words">{{ msg.content }}</p>
                  <div v-if="msg.attachments && msg.attachments.length > 0" class="mt-2 space-y-1">
                    <a
                      v-for="att in msg.attachments"
                      :key="att.id"
                      :href="att.download_url"
                      target="_blank"
                      :class="[
                        'flex items-center gap-2 p-2 rounded transition-colors text-xs',
                        msg.sender.id === $page.props.auth.user.id
                          ? 'bg-indigo-700 hover:bg-indigo-800'
                          : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700'
                      ]"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                      </svg>
                      <span class="flex-1 truncate">{{ att.file_name }}</span>
                      <span class="text-xs opacity-70">{{ att.human_size }}</span>
                    </a>
                  </div>
                  <div class="flex items-center justify-between mt-1 text-xs opacity-70">
                    <span>{{ formatTime(msg.created_at) }}</span>
                    <span v-if="msg.is_edited">(edited)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Message Input -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
              <form @submit.prevent="sendMessage" class="flex items-end gap-3">
                <div class="flex-1">
                  <textarea
                    v-model="newMessage"
                    @keydown.enter.exact.prevent="sendMessage"
                    placeholder="Type a message..."
                    rows="1"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"
                    style="min-height: 40px; max-height: 120px;"
                  ></textarea>
                  <div v-if="selectedFiles.length > 0" class="mt-2 flex flex-wrap gap-2">
                    <div
                      v-for="(file, index) in selectedFiles"
                      :key="index"
                      class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 rounded-md text-xs"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                      </svg>
                      <span>{{ file.name }}</span>
                      <button @click="removeFile(index)" type="button" class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
                <input
                  ref="fileInput"
                  type="file"
                  multiple
                  @change="handleFileSelect"
                  class="hidden"
                  accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                />
                <button
                  @click="$refs.fileInput.click()"
                  type="button"
                  class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                >
                  <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                  </svg>
                </button>
                <button
                  type="submit"
                  :disabled="!newMessage.trim() && selectedFiles.length === 0"
                  class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                >
                  Send
                </button>
              </form>
            </div>
          </div>


          <!-- Empty State -->
          <div v-else class="col-span-12 lg:col-span-8 xl:col-span-9 flex items-center justify-center bg-gray-50 dark:bg-gray-800/50">
            <div class="text-center p-12">
              <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Select a conversation</h3>
              <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Choose a conversation from the list or start a new one</p>
              <button
                @click="showNewMessageModal = true"
                class="mt-6 px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors"
              >
                New Message
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Message Modal -->
    <div v-if="showNewMessageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New Message</h3>
            <button @click="showNewMessageModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message Type</label>
              <div class="grid grid-cols-2 gap-3">
                <button
                  @click="newMessageType = 'direct'"
                  :class="[
                    'py-2 rounded-md font-medium transition-colors',
                    newMessageType === 'direct'
                      ? 'bg-indigo-600 text-white'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                  ]"
                >
                  Direct Message
                </button>
                <button
                  @click="newMessageType = 'group'"
                  :class="[
                    'py-2 rounded-md font-medium transition-colors',
                    newMessageType === 'group'
                      ? 'bg-indigo-600 text-white'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                  ]"
                >
                  Group Chat
                </button>
              </div>
            </div>
            
            <div v-if="newMessageType === 'group'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Group Name</label>
              <input
                v-model="newGroupTitle"
                type="text"
                placeholder="Enter group name"
                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ newMessageType === 'direct' ? 'Select User' : 'Select Participants' }}
              </label>
              <select
                v-if="newMessageType === 'direct'"
                v-model="selectedUserId"
                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Choose a user...</option>
                <option v-for="user in companyUsers" :key="user.id" :value="user.id">
                  {{ user.name }} ({{ user.email }})
                </option>
              </select>
              <div v-else class="border border-gray-300 dark:border-gray-700 rounded-md p-3 max-h-60 overflow-y-auto space-y-2">
                <label
                  v-for="user in companyUsers"
                  :key="user.id"
                  class="flex items-center gap-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :value="user.id"
                    v-model="selectedUserIds"
                    class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500"
                  />
                  <span class="text-sm text-gray-900 dark:text-white">{{ user.name }} ({{ user.email }})</span>
                </label>
              </div>
            </div>
            
            <div class="flex gap-3 pt-4">
              <button
                @click="showNewMessageModal = false"
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="createConversation"
                :disabled="!canCreateConversation"
                class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Create
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const conversations = ref([])
const selectedConversation = ref(null)
const messages = ref([])
const newMessage = ref('')
const selectedFiles = ref([])
const searchQuery = ref('')
const filter = ref('all')
const loading = ref(false)
const loadingMessages = ref(false)
const showNewMessageModal = ref(false)
const showParticipantsModal = ref(false)
const newMessageType = ref('direct')
const selectedUserId = ref('')
const selectedUserIds = ref([])
const newGroupTitle = ref('')
const companyUsers = ref([])
const messagesContainer = ref(null)
const fileInput = ref(null)

const filteredConversations = computed(() => {
  let filtered = conversations.value

  if (filter.value === 'starred') {
    filtered = filtered.filter(c => c.is_starred)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(c => c.title.toLowerCase().includes(query))
  }

  return filtered
})

const canCreateConversation = computed(() => {
  if (newMessageType.value === 'direct') {
    return selectedUserId.value !== ''
  } else {
    return newGroupTitle.value.trim() !== '' && selectedUserIds.value.length > 0
  }
})

onMounted(() => {
  loadConversations()
  loadCompanyUsers()
})

watch(selectedConversation, () => {
  if (selectedConversation.value) {
    loadMessages()
    markAsRead()
  }
})

async function loadConversations() {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/inbox/conversations')
    conversations.value = response.data.data
  } catch (error) {
    console.error('Failed to load conversations:', error)
  } finally {
    loading.value = false
  }
}

async function loadCompanyUsers() {
  try {
    const response = await axios.get('/api/v1/inbox/users')
    companyUsers.value = response.data.data
  } catch (error) {
    console.error('Failed to load users:', error)
  }
}

async function loadMessages() {
  if (!selectedConversation.value) return
  
  loadingMessages.value = true
  try {
    const response = await axios.get(`/api/v1/inbox/conversations/${selectedConversation.value.id}/messages`)
    messages.value = response.data.data.reverse()
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to load messages:', error)
  } finally {
    loadingMessages.value = false
  }
}

function selectConversation(conv) {
  selectedConversation.value = conv
}

async function sendMessage() {
  if (!newMessage.value.trim() && selectedFiles.value.length === 0) return

  const formData = new FormData()
  formData.append('content', newMessage.value)
  
  selectedFiles.value.forEach((file) => {
    formData.append('attachments[]', file)
  })

  try {
    const response = await axios.post(`/api/v1/inbox/conversations/${selectedConversation.value.id}/messages`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    messages.value.push(response.data.data)
    newMessage.value = ''
    selectedFiles.value = []
    
    await nextTick()
    scrollToBottom()
    
    // Update conversation in list
    await loadConversations()
  } catch (error) {
    console.error('Failed to send message:', error)
    alert('Failed to send message')
  }
}

async function createConversation() {
  try {
    let response
    if (newMessageType.value === 'direct') {
      response = await axios.post('/api/v1/inbox/conversations/direct', {
        user_id: selectedUserId.value
      })
    } else {
      response = await axios.post('/api/v1/inbox/conversations/group', {
        title: newGroupTitle.value,
        participant_ids: selectedUserIds.value
      })
    }
    
    showNewMessageModal.value = false
    selectedUserId.value = ''
    selectedUserIds.value = []
    newGroupTitle.value = ''
    
    await loadConversations()
    selectedConversation.value = response.data.data
  } catch (error) {
    console.error('Failed to create conversation:', error)
    alert('Failed to create conversation')
  }
}

async function toggleStar() {
  try {
    const response = await axios.post(`/api/v1/inbox/conversations/${selectedConversation.value.id}/star`)
    selectedConversation.value.is_starred = response.data.data.is_starred
    await loadConversations()
  } catch (error) {
    console.error('Failed to toggle star:', error)
  }
}

async function toggleMute() {
  try {
    const response = await axios.post(`/api/v1/inbox/conversations/${selectedConversation.value.id}/mute`)
    selectedConversation.value.is_muted = response.data.data.is_muted
    await loadConversations()
  } catch (error) {
    console.error('Failed to toggle mute:', error)
  }
}

async function markAsRead() {
  if (!selectedConversation.value) return
  
  try {
    await axios.post(`/api/v1/inbox/conversations/${selectedConversation.value.id}/read`)
    selectedConversation.value.unread_count = 0
    await loadConversations()
  } catch (error) {
    console.error('Failed to mark as read:', error)
  }
}

async function deleteConversation() {
  if (!confirm('Are you sure you want to delete this conversation?')) return
  
  try {
    await axios.delete(`/api/v1/inbox/conversations/${selectedConversation.value.id}`)
    selectedConversation.value = null
    await loadConversations()
  } catch (error) {
    console.error('Failed to delete conversation:', error)
  }
}

function handleFileSelect(event) {
  const files = Array.from(event.target.files)
  selectedFiles.value.push(...files)
}

function removeFile(index) {
  selectedFiles.value.splice(index, 1)
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

function getInitials(name) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function formatTime(timestamp) {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date
  
  if (diff < 60000) return 'Just now'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
  if (diff < 86400000) return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  if (diff < 604800000) return date.toLocaleDateString('en-US', { weekday: 'short' })
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>
