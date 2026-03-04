<template>
    <AppLayout title="Skills Management">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Skills Management</h1>
                        <p class="text-gray-600">Manage employee skills and competencies</p>
                    </div>
                    <button 
                        @click="showAddModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                    >
                        Add Skill
                    </button>
                </div>

                <!-- Skills Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <AcademicCapIcon class="h-6 w-6 text-blue-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Skills</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.total_skills }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <UsersIcon class="h-6 w-6 text-green-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Skilled Employees</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.skilled_employees }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <ChartBarIcon class="h-6 w-6 text-purple-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Avg Skills per Employee</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ stats.avg_skills_per_employee }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills Matrix -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">Skills Matrix</h2>
                            <div class="flex space-x-2">
                                <select v-model="filters.category" class="rounded-md border-gray-300 text-sm">
                                    <option value="">All Categories</option>
                                    <option value="technical">Technical</option>
                                    <option value="soft">Soft Skills</option>
                                    <option value="management">Management</option>
                                    <option value="design">Design</option>
                                </select>
                                <select v-model="filters.level" class="rounded-md border-gray-300 text-sm">
                                    <option value="">All Levels</option>
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                    <option value="expert">Expert</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                                        Employee
                                    </th>
                                    <th 
                                        v-for="skill in filteredSkills" 
                                        :key="skill.id"
                                        class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        style="writing-mode: vertical-rl; text-orientation: mixed;"
                                    >
                                        {{ skill.name }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="employee in employees" :key="employee.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img 
                                                :src="employee.avatar || '/default-avatar.png'" 
                                                :alt="employee.name"
                                                class="h-8 w-8 rounded-full mr-3"
                                            />
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ employee.name }}</div>
                                                <div class="text-sm text-gray-500">{{ employee.position }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td 
                                        v-for="skill in filteredSkills" 
                                        :key="skill.id"
                                        class="px-3 py-4 text-center"
                                    >
                                        <div 
                                            v-if="getEmployeeSkillLevel(employee, skill)"
                                            :class="getSkillLevelClass(getEmployeeSkillLevel(employee, skill))"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium"
                                            :title="`${skill.name}: ${getEmployeeSkillLevel(employee, skill)}`"
                                        >
                                            {{ getSkillLevelAbbr(getEmployeeSkillLevel(employee, skill)) }}
                                        </div>
                                        <div v-else class="w-8 h-8 flex items-center justify-center">
                                            <div class="w-2 h-2 bg-gray-200 rounded-full"></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Skills List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">All Skills</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Skill
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Category
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employees
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Avg Level
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="skill in skills" :key="skill.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ skill.name }}</div>
                                            <div class="text-sm text-gray-500">{{ skill.description }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ skill.category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ skill.employees_count }} employees
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getSkillLevelClass(skill.avg_level)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ skill.avg_level }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button 
                                            @click="editSkill(skill)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            @click="deleteSkill(skill)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Skill Modal -->
        <SkillModal 
            :show="showAddModal"
            :skill="selectedSkill"
            @close="closeModal"
            @saved="handleSkillSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SkillModal from '@/Components/HR/SkillModal.vue'
import { 
    AcademicCapIcon, 
    UsersIcon, 
    ChartBarIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    skills: Array,
    employees: Array,
    stats: Object
})

const showAddModal = ref(false)
const selectedSkill = ref(null)

const filters = ref({
    category: '',
    level: ''
})

const filteredSkills = computed(() => {
    let filtered = props.skills

    if (filters.value.category) {
        filtered = filtered.filter(skill => skill.category === filters.value.category)
    }

    return filtered
})

const getEmployeeSkillLevel = (employee, skill) => {
    const employeeSkill = employee.skills?.find(s => s.id === skill.id)
    return employeeSkill?.pivot?.level
}

const getSkillLevelClass = (level) => {
    const classes = {
        beginner: 'bg-gray-100 text-gray-800',
        intermediate: 'bg-blue-100 text-blue-800',
        advanced: 'bg-green-100 text-green-800',
        expert: 'bg-purple-100 text-purple-800'
    }
    return classes[level] || 'bg-gray-100 text-gray-800'
}

const getSkillLevelAbbr = (level) => {
    const abbr = {
        beginner: 'B',
        intermediate: 'I',
        advanced: 'A',
        expert: 'E'
    }
    return abbr[level] || '-'
}

const editSkill = (skill) => {
    selectedSkill.value = skill
    showAddModal.value = true
}

const deleteSkill = (skill) => {
    if (confirm('Are you sure you want to delete this skill?')) {
        router.delete(`/api/v1/hr/skills/${skill.id}`)
    }
}

const closeModal = () => {
    showAddModal.value = false
    selectedSkill.value = null
}

const handleSkillSaved = () => {
    closeModal()
    router.reload()
}
</script>