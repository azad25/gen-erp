// Common Types
export interface User {
  id: number
  name: string
  email: string
  avatar?: string
  created_at: string
  updated_at: string
}

export interface Company {
  id: number
  name: string
  slug: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface ApiResponse<T = any> {
  data: T
  message?: string
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    has_more_pages: boolean
  }
}

export interface PaginatedResponse<T = any> extends ApiResponse<T[]> {
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    has_more_pages: boolean
  }
}

// Project Management Types
export interface Project {
  id: number
  name: string
  description?: string
  status: 'active' | 'completed' | 'on_hold' | 'cancelled'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  start_date?: string
  end_date?: string
  budget?: number
  progress: number
  client_id?: number
  manager_id: number
  created_at: string
  updated_at: string
  tasks_count?: number
  completed_tasks_count?: number
  members?: User[]
  client?: any
  manager?: User
}

export interface Task {
  id: number
  title: string
  description?: string
  status: 'todo' | 'in_progress' | 'review' | 'completed'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  project_id: number
  assigned_to?: number
  parent_id?: number
  due_date?: string
  estimated_hours?: number
  actual_hours?: number
  progress: number
  created_at: string
  updated_at: string
  project?: Project
  assignee?: User
  parent?: Task
  subtasks?: Task[]
  watchers?: User[]
}

export interface TimeEntry {
  id: number
  task_id: number
  user_id: number
  date: string
  duration: number
  description?: string
  is_billable: boolean
  created_at: string
  updated_at: string
  task?: Task
  user?: User
}

// CRM Types
export interface Lead {
  id: number
  first_name: string
  last_name: string
  email: string
  phone?: string
  company?: string
  job_title?: string
  status: 'new' | 'contacted' | 'qualified' | 'proposal' | 'negotiation' | 'closed_won' | 'closed_lost'
  source: string
  score: number
  assigned_to?: number
  created_at: string
  updated_at: string
  assignee?: User
  activities?: Activity[]
  notes?: Note[]
  tags?: Tag[]
}

export interface Activity {
  id: number
  type: 'call' | 'email' | 'meeting' | 'note' | 'task'
  title: string
  description?: string
  date: string
  time?: string
  status: 'completed' | 'pending' | 'scheduled' | 'cancelled'
  lead_id?: number
  user_id: number
  metadata?: {
    duration?: number
    outcome?: string
    next_action?: string
  }
  created_at: string
  updated_at: string
  lead?: Lead
  user?: User
  attachments?: Attachment[]
}

export interface Pipeline {
  id: number
  name: string
  stages: PipelineStage[]
  is_default: boolean
  created_at: string
  updated_at: string
}

export interface PipelineStage {
  id: number
  name: string
  pipeline_id: number
  order: number
  color: string
  probability: number
  created_at: string
  updated_at: string
}

export interface Opportunity {
  id: number
  title: string
  description?: string
  value: number
  currency: string
  probability: number
  expected_close_date?: string
  stage_id: number
  lead_id?: number
  assigned_to?: number
  created_at: string
  updated_at: string
  stage?: PipelineStage
  lead?: Lead
  assignee?: User
}

// CMS Types
export interface Site {
  id: number
  name: string
  domain: string
  is_active: boolean
  theme: string
  settings: Record<string, any>
  created_at: string
  updated_at: string
  pages?: Page[]
}

export interface Page {
  id: number
  title: string
  slug: string
  content?: string
  meta_title?: string
  meta_description?: string
  is_published: boolean
  is_homepage: boolean
  site_id: number
  template: string
  created_at: string
  updated_at: string
  site?: Site
  sections?: PageSection[]
}

export interface PageSection {
  id: number
  type: string
  content: Record<string, any>
  order: number
  page_id: number
  created_at: string
  updated_at: string
}

// Logistics Types
export interface Shipment {
  id: number
  tracking_number: string
  status: 'pending' | 'picked_up' | 'in_transit' | 'delivered' | 'returned' | 'cancelled'
  origin: Address
  destination: Address
  weight: number
  dimensions: {
    length: number
    width: number
    height: number
  }
  carrier_id: number
  service_type: string
  cost: number
  currency: string
  created_at: string
  updated_at: string
  carrier?: Carrier
  tracking_events?: TrackingEvent[]
}

export interface Address {
  name: string
  company?: string
  address_line_1: string
  address_line_2?: string
  city: string
  state: string
  postal_code: string
  country: string
  phone?: string
  email?: string
}

export interface Carrier {
  id: number
  name: string
  code: string
  is_active: boolean
  settings: Record<string, any>
  created_at: string
  updated_at: string
}

export interface TrackingEvent {
  id: number
  shipment_id: number
  status: string
  description: string
  location?: string
  timestamp: string
  created_at: string
  updated_at: string
}

// Common UI Types
export interface Toast {
  id: number
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
  duration: number
  startTime: number
  visible: boolean
  actions?: ToastAction[]
  persistent: boolean
}

export interface ToastAction {
  label: string
  handler: (toast: Toast) => void
  dismissOnClick?: boolean
}

export interface FilterOption {
  label: string
  value: string | number
  count?: number
}

export interface SortOption {
  label: string
  value: string
  direction: 'asc' | 'desc'
}

export interface TableColumn {
  key: string
  label: string
  sortable?: boolean
  width?: string
  align?: 'left' | 'center' | 'right'
  render?: (value: any, row: any) => string
}

// Common Utility Types
export interface Note {
  id: number
  content: string
  user_id: number
  created_at: string
  updated_at: string
  user?: User
}

export interface Tag {
  id: number
  name: string
  color: string
  created_at: string
  updated_at: string
}

export interface Attachment {
  id: number
  name: string
  file_path: string
  file_size: number
  mime_type: string
  created_at: string
  updated_at: string
}