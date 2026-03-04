import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import DataTable from '@/Components/Common/DataTable.vue'

describe('DataTable', () => {
  let wrapper
  
  const mockData = [
    { id: 1, name: 'John Doe', email: 'john@example.com', status: 'active' },
    { id: 2, name: 'Jane Smith', email: 'jane@example.com', status: 'inactive' },
    { id: 3, name: 'Bob Johnson', email: 'bob@example.com', status: 'active' }
  ]
  
  const mockColumns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'status', label: 'Status', sortable: false }
  ]

  beforeEach(() => {
    wrapper = mount(DataTable, {
      props: {
        data: mockData,
        columns: mockColumns
      }
    })
  })

  it('renders table with correct data', () => {
    expect(wrapper.find('table').exists()).toBe(true)
    expect(wrapper.findAll('tbody tr')).toHaveLength(3)
    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('jane@example.com')
  })

  it('renders column headers correctly', () => {
    const headers = wrapper.findAll('thead th')
    expect(headers).toHaveLength(4) // 3 columns + checkbox column
    expect(headers[1].text()).toContain('Name')
    expect(headers[2].text()).toContain('Email')
    expect(headers[3].text()).toContain('Status')
  })

  it('shows sortable indicators for sortable columns', () => {
    const nameHeader = wrapper.findAll('thead th')[1]
    const emailHeader = wrapper.findAll('thead th')[2]
    const statusHeader = wrapper.findAll('thead th')[3]
    
    expect(nameHeader.find('button').exists()).toBe(true)
    expect(emailHeader.find('button').exists()).toBe(true)
    expect(statusHeader.find('button').exists()).toBe(false)
  })

  it('handles sorting correctly', async () => {
    const nameHeaderButton = wrapper.findAll('thead th')[1].find('button')
    
    // Click to sort ascending
    await nameHeaderButton.trigger('click')
    expect(wrapper.emitted('sort')).toBeTruthy()
    expect(wrapper.emitted('sort')[0]).toEqual([{ column: 'name', direction: 'asc' }])
    
    // Click again to sort descending
    await nameHeaderButton.trigger('click')
    expect(wrapper.emitted('sort')[1]).toEqual([{ column: 'name', direction: 'desc' }])
  })

  it('handles row selection', async () => {
    const firstRowCheckbox = wrapper.findAll('tbody tr')[0].find('input[type="checkbox"]')
    
    await firstRowCheckbox.setChecked(true)
    expect(wrapper.emitted('selection-changed')).toBeTruthy()
    expect(wrapper.emitted('selection-changed')[0][0]).toContain(1)
  })

  it('handles select all functionality', async () => {
    const selectAllCheckbox = wrapper.find('thead input[type="checkbox"]')
    
    await selectAllCheckbox.setChecked(true)
    expect(wrapper.emitted('selection-changed')).toBeTruthy()
    expect(wrapper.emitted('selection-changed')[0][0]).toEqual([1, 2, 3])
  })

  it('shows loading state', async () => {
    await wrapper.setProps({ loading: true })
    expect(wrapper.find('.animate-spin').exists()).toBe(true)
    expect(wrapper.text()).toContain('Loading')
  })

  it('shows empty state when no data', async () => {
    await wrapper.setProps({ data: [] })
    expect(wrapper.text()).toContain('No data available')
  })

  it('handles pagination', async () => {
    await wrapper.setProps({
      pagination: {
        currentPage: 1,
        totalPages: 3,
        perPage: 10,
        total: 25
      }
    })
    
    expect(wrapper.find('.pagination').exists()).toBe(true)
    expect(wrapper.text()).toContain('Page 1 of 3')
  })

  it('emits page change events', async () => {
    await wrapper.setProps({
      pagination: {
        currentPage: 1,
        totalPages: 3,
        perPage: 10,
        total: 25
      }
    })
    
    const nextButton = wrapper.find('[data-testid="next-page"]')
    await nextButton.trigger('click')
    
    expect(wrapper.emitted('page-changed')).toBeTruthy()
    expect(wrapper.emitted('page-changed')[0]).toEqual([2])
  })

  it('handles search functionality', async () => {
    await wrapper.setProps({ searchable: true })
    
    const searchInput = wrapper.find('input[placeholder="Search..."]')
    expect(searchInput.exists()).toBe(true)
    
    await searchInput.setValue('john')
    expect(wrapper.emitted('search')).toBeTruthy()
    expect(wrapper.emitted('search')[0]).toEqual(['john'])
  })

  it('shows bulk actions when items are selected', async () => {
    await wrapper.setProps({
      bulkActions: [
        { id: 'delete', label: 'Delete', icon: 'trash' },
        { id: 'export', label: 'Export', icon: 'download' }
      ]
    })
    
    // Select first row
    const firstRowCheckbox = wrapper.findAll('tbody tr')[0].find('input[type="checkbox"]')
    await firstRowCheckbox.setChecked(true)
    
    expect(wrapper.find('[data-testid="bulk-actions"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Delete')
    expect(wrapper.text()).toContain('Export')
  })

  it('emits bulk action events', async () => {
    await wrapper.setProps({
      bulkActions: [
        { id: 'delete', label: 'Delete', icon: 'trash' }
      ]
    })
    
    // Select first row
    const firstRowCheckbox = wrapper.findAll('tbody tr')[0].find('input[type="checkbox"]')
    await firstRowCheckbox.setChecked(true)
    
    const deleteButton = wrapper.find('[data-testid="bulk-action-delete"]')
    await deleteButton.trigger('click')
    
    expect(wrapper.emitted('bulk-action')).toBeTruthy()
    expect(wrapper.emitted('bulk-action')[0]).toEqual(['delete', [1]])
  })

  it('handles custom cell rendering', async () => {
    const customColumns = [
      {
        key: 'status',
        label: 'Status',
        render: (value) => `<span class="status-${value}">${value}</span>`
      }
    ]
    
    await wrapper.setProps({ columns: customColumns })
    
    expect(wrapper.find('.status-active').exists()).toBe(true)
    expect(wrapper.find('.status-inactive').exists()).toBe(true)
  })

  it('handles row click events', async () => {
    const firstRow = wrapper.findAll('tbody tr')[0]
    await firstRow.trigger('click')
    
    expect(wrapper.emitted('row-clicked')).toBeTruthy()
    expect(wrapper.emitted('row-clicked')[0]).toEqual([mockData[0]])
  })

  it('applies custom CSS classes', async () => {
    await wrapper.setProps({
      tableClass: 'custom-table',
      rowClass: 'custom-row'
    })
    
    expect(wrapper.find('.custom-table').exists()).toBe(true)
    expect(wrapper.find('.custom-row').exists()).toBe(true)
  })

  it('handles responsive design', async () => {
    await wrapper.setProps({ responsive: true })
    
    expect(wrapper.find('.table-responsive').exists()).toBe(true)
  })

  it('shows column visibility controls', async () => {
    await wrapper.setProps({ columnToggle: true })
    
    expect(wrapper.find('[data-testid="column-toggle"]').exists()).toBe(true)
  })

  it('handles export functionality', async () => {
    await wrapper.setProps({
      exportable: true,
      exportFormats: ['csv', 'excel', 'pdf']
    })
    
    expect(wrapper.find('[data-testid="export-dropdown"]').exists()).toBe(true)
    
    const csvExport = wrapper.find('[data-testid="export-csv"]')
    await csvExport.trigger('click')
    
    expect(wrapper.emitted('export')).toBeTruthy()
    expect(wrapper.emitted('export')[0]).toEqual(['csv'])
  })
})