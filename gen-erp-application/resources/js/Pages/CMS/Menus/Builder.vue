<template>
  <div>
    <Head :title="`Menu Builder - ${menu.name}`" />
    
    <div class="h-screen flex flex-col bg-gray-100">
      <!-- Header -->
      <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Link
            :href="route('cms.menus.index')"
            class="text-gray-500 hover:text-gray-700"
          >
            <Icon name="heroicons:arrow-left" class="w-5 h-5" />
          </Link>
          <h1 class="text-xl font-semibold text-gray-900">{{ menu.name }}</h1>
          <span class="text-sm text-gray-500">{{ menu.location }}</span>
        </div>
        
        <div class="flex items-center space-x-3">
          <button
            @click="saveMenu"
            :disabled="saving"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save Menu' }}
          </button>
          
          <button
            @click="previewMenu"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Preview
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="flex-1 flex overflow-hidden">
        <!-- Left Sidebar - Menu Items Library -->
        <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto">
          <div class="p-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Menu Items</h2>
            
            <!-- Pages -->
            <div class="mb-6">
              <h3 class="text-sm font-medium text-gray-700 mb-2">Pages</h3>
              <div class="space-y-2">
                <div
                  v-for="page in pages"
                  :key="page.id"
                  @click="addPageItem(page)"
                  class="p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors"
                >
                  <div class="flex items-center space-x-2">
                    <Icon name="heroicons:document-text" class="w-4 h-4 text-gray-500" />
                    <span class="text-sm font-medium text-gray-900">{{ page.title }}</span>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">{{ page.slug }}</p>
                </div>
              </div>
            </div>

            <!-- Categories -->
            <div class="mb-6">
              <h3 class="text-sm font-medium text-gray-700 mb-2">Blog Categories</h3>
              <div class="space-y-2">
                <div
                  v-for="category in categories"
                  :key="category.id"
                  @click="addCategoryItem(category)"
                  class="p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors"
                >
                  <div class="flex items-center space-x-2">
                    <Icon name="heroicons:tag" class="w-4 h-4 text-gray-500" />
                    <span class="text-sm font-medium text-gray-900">{{ category.name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Custom Link -->
            <div class="mb-6">
              <h3 class="text-sm font-medium text-gray-700 mb-2">Custom Link</h3>
              <div class="space-y-3">
                <input
                  v-model="customLink.label"
                  type="text"
                  placeholder="Link Label"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                />
                <input
                  v-model="customLink.url"
                  type="text"
                  placeholder="URL"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                />
                <select
                  v-model="customLink.target"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                >
                  <option value="_self">Same Window</option>
                  <option value="_blank">New Window</option>
                </select>
                <button
                  @click="addCustomLink"
                  :disabled="!customLink.label || !customLink.url"
                  class="w-full px-3 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                >
                  Add Custom Link
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Center - Menu Structure -->
        <div class="flex-1 flex flex-col overflow-hidden">
          <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
            <div class="max-w-2xl mx-auto">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Menu Structure</h2>
              
              <!-- Menu Items -->
              <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <draggable
                  v-model="menuItems"
                  group="menu-items"
                  item-key="id"
                  @change="onMenuReorder"
                  class="min-h-12"
                >
                  <template #item="{ element: item, index }">
                    <MenuItemEditor
                      :key="item.id"
                      :item="item"
                      :index="index"
                      :level="0"
                      @update="updateMenuItem"
                      @delete="deleteMenuItem"
                      @add-child="addChildItem"
                    />
                  </template>
                </draggable>
                
                <!-- Empty State -->
                <div
                  v-if="menuItems.length === 0"
                  class="flex items-center justify-center h-32 text-gray-500"
                >
                  <div class="text-center">
                    <Icon name="heroicons:bars-3" class="w-8 h-8 mx-auto mb-2 text-gray-400" />
                    <p class="text-sm">No menu items yet</p>
                    <p class="text-xs text-gray-400">Add items from the sidebar</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Sidebar - Item Properties -->
        <div class="w-80 bg-white border-l border-gray-200 overflow-y-auto">
          <div class="p-4">
            <div v-if="selectedItem">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Item Properties</h2>
              
              <!-- Item Details -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                  <input
                    v-model="selectedItem.label"
                    @input="updateSelectedItem"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                  <input
                    v-model="selectedItem.url"
                    @input="updateSelectedItem"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Target</label>
                  <select
                    v-model="selectedItem.target"
                    @change="updateSelectedItem"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                  >
                    <option value="_self">Same Window</option>
                    <option value="_blank">New Window</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">CSS Classes</label>
                  <input
                    v-model="selectedItem.css_classes"
                    @input="updateSelectedItem"
                    type="text"
                    placeholder="custom-class another-class"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                  />
                </div>

                <div>
                  <label class="flex items-center">
                    <input
                      v-model="selectedItem.is_visible"
                      @change="updateSelectedItem"
                      type="checkbox"
                      class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    />
                    <span class="ml-2 text-sm text-gray-700">Visible</span>
                  </label>
                </div>
              </div>

              <!-- Delete Item -->
              <div class="mt-6 pt-6 border-t border-gray-200">
                <button
                  @click="deleteSelectedItem"
                  class="w-full px-3 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700"
                >
                  Delete Item
                </button>
              </div>
            </div>
            
            <div v-else class="text-center py-12">
              <Icon name="heroicons:cursor-arrow-rays" class="w-12 h-12 mx-auto mb-4 text-gray-400" />
              <h3 class="text-lg font-medium text-gray-900 mb-2">No item selected</h3>
              <p class="text-sm text-gray-500">Click on a menu item to edit its properties</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import Icon from '@/Components/UI/Icon.vue'
import MenuItemEditor from '@/Components/CMS/MenuItemEditor.vue'

const props = defineProps({
  menu: Object,
  menuItems: Array,
  pages: Array,
  categories: Array
})

// Reactive data
const menuItems = ref([...props.menuItems])
const selectedItem = ref(null)
const saving = ref(false)

const customLink = ref({
  label: '',
  url: '',
  target: '_self'
})

// Methods
const addPageItem = (page) => {
  const newItem = {
    id: Date.now(),
    label: page.title,
    url: `/pages/${page.slug}`,
    page_id: page.id,
    target: '_self',
    sort_order: menuItems.value.length,
    is_visible: true,
    children: []
  }
  
  menuItems.value.push(newItem)
}

const addCategoryItem = (category) => {
  const newItem = {
    id: Date.now(),
    label: category.name,
    url: `/blog/category/${category.slug}`,
    target: '_self',
    sort_order: menuItems.value.length,
    is_visible: true,
    children: []
  }
  
  menuItems.value.push(newItem)
}

const addCustomLink = () => {
  if (!customLink.value.label || !customLink.value.url) return
  
  const newItem = {
    id: Date.now(),
    label: customLink.value.label,
    url: customLink.value.url,
    target: customLink.value.target,
    sort_order: menuItems.value.length,
    is_visible: true,
    children: []
  }
  
  menuItems.value.push(newItem)
  
  // Reset form
  customLink.value = {
    label: '',
    url: '',
    target: '_self'
  }
}

const updateMenuItem = (itemId, updates) => {
  const updateItemRecursive = (items) => {
    for (let item of items) {
      if (item.id === itemId) {
        Object.assign(item, updates)
        return true
      }
      if (item.children && updateItemRecursive(item.children)) {
        return true
      }
    }
    return false
  }
  
  updateItemRecursive(menuItems.value)
}

const deleteMenuItem = (itemId) => {
  const deleteItemRecursive = (items) => {
    for (let i = 0; i < items.length; i++) {
      if (items[i].id === itemId) {
        items.splice(i, 1)
        return true
      }
      if (items[i].children && deleteItemRecursive(items[i].children)) {
        return true
      }
    }
    return false
  }
  
  deleteItemRecursive(menuItems.value)
  
  if (selectedItem.value?.id === itemId) {
    selectedItem.value = null
  }
}

const addChildItem = (parentId) => {
  const newItem = {
    id: Date.now(),
    label: 'New Item',
    url: '#',
    target: '_self',
    sort_order: 0,
    is_visible: true,
    children: []
  }
  
  const addChildRecursive = (items) => {
    for (let item of items) {
      if (item.id === parentId) {
        if (!item.children) item.children = []
        item.children.push(newItem)
        return true
      }
      if (item.children && addChildRecursive(item.children)) {
        return true
      }
    }
    return false
  }
  
  addChildRecursive(menuItems.value)
}

const onMenuReorder = () => {
  menuItems.value.forEach((item, index) => {
    item.sort_order = index
  })
}

const updateSelectedItem = () => {
  if (selectedItem.value) {
    updateMenuItem(selectedItem.value.id, selectedItem.value)
  }
}

const deleteSelectedItem = () => {
  if (selectedItem.value && confirm('Are you sure you want to delete this menu item?')) {
    deleteMenuItem(selectedItem.value.id)
  }
}

const saveMenu = async () => {
  saving.value = true
  
  try {
    await router.put(route('cms.menus.update', props.menu.id), {
      items: menuItems.value
    }, {
      preserveState: true,
      preserveScroll: true
    })
  } finally {
    saving.value = false
  }
}

const previewMenu = () => {
  const previewUrl = route('cms.menus.preview', props.menu.id)
  window.open(previewUrl, '_blank')
}
</script>