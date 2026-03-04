# Design System

## Table of Contents
- [Overview](#overview)
- [Design Principles](#design-principles)
- [Color Palette](#color-palette)
- [Typography](#typography)
- [Spacing & Layout](#spacing--layout)
- [Components](#components)
- [Icons](#icons)
- [Forms](#forms)
- [Status & Feedback](#status--feedback)
- [Dark Mode](#dark-mode)
- [Accessibility](#accessibility)

---

## Overview

Gen-ERP uses a modern, clean design system built on **TailwindCSS 4.2** and the **TailAdmin** theme. The design emphasizes clarity, efficiency, and accessibility, making it easy for users to navigate complex business workflows.

### Design Philosophy

1. **Clarity** - Clear visual hierarchy and intuitive layouts
2. **Efficiency** - Minimize clicks and cognitive load
3. **Consistency** - Unified design language across all modules
4. **Accessibility** - WCAG 2.1 AA compliant
5. **Responsiveness** - Works seamlessly on all devices

### Technology Stack

- **TailwindCSS 4.2** - Utility-first CSS framework
- **Plus Jakarta Sans** - Primary font family
- **Noto Sans Bengali** - Bangla language support
- **Heroicons** - Icon library
- **ApexCharts** - Data visualization

---

## Design Principles

### 1. Progressive Disclosure

Show only what users need, when they need it:
- Collapsed sidebars for more workspace
- Expandable sections for detailed information
- Modal dialogs for focused tasks
- Tooltips for contextual help

### 2. Visual Hierarchy

Guide users through clear visual structure:
- Bold headings for sections
- Subtle backgrounds for grouping
- Color for emphasis and status
- Whitespace for breathing room

### 3. Feedback & Confirmation

Keep users informed:
- Loading states for async operations
- Success/error messages
- Confirmation dialogs for destructive actions
- Progress indicators for long tasks

### 4. Consistency

Maintain predictable patterns:
- Consistent button styles
- Standard form layouts
- Uniform spacing
- Predictable navigation

---

## Color Palette

### Brand Colors

**Primary (Teal):**
```css
--color-primary: #0F766E        /* Teal-700 - Main brand color */
--color-primary-dark: #0D6B63   /* Darker shade for hover */
--color-primary-light: #14B8A6  /* Teal-500 - Lighter accent */
```

**Usage:**
- Primary buttons
- Active navigation items
- Links and interactive elements
- Focus states

**Accent (Teal):**
```css
--color-accent: #14B8A6         /* Teal-500 */
```

**Usage:**
- Secondary highlights
- Icons and badges
- Progress indicators

### Semantic Colors

**Success (Green):**
```css
--color-success: #16A34A        /* Green-600 */
```

**Usage:**
- Success messages
- Positive status indicators
- Confirmation buttons
- Completed states

**Warning (Yellow):**
```css
--color-warning: #CA8A04        /* Yellow-600 */
```

**Usage:**
- Warning messages
- Pending status
- Caution indicators
- Important notices

**Danger (Red):**
```css
--color-danger: #B91C1C         /* Red-700 */
```

**Usage:**
- Error messages
- Destructive actions
- Failed states
- Critical alerts

### Neutral Colors

**Backgrounds:**
```css
--color-bodybg: #FAFAFA         /* Light gray background */
--color-white: #FFFFFF          /* Pure white */
--color-whiten: #F1F5F9         /* Slate-100 */
```

**Text:**
```css
--color-black: #1F2937          /* Gray-800 - Primary text */
--color-black-2: #374151        /* Gray-700 - Secondary text */
--color-gray-1: #6B7280         /* Gray-500 - Tertiary text */
```

**Borders:**
```css
--color-stroke: #E5E7EB         /* Gray-200 - Default borders */
--color-gray-2: #D1D5DB         /* Gray-300 - Subtle borders */
--color-gray-3: #F3F4F6         /* Gray-100 - Very subtle */
```

### Dark Mode Colors

**Backgrounds:**
```css
--color-boxdark: #0D1F1E        /* Dark sidebar background */
--color-boxdark-2: #162221      /* Dark dropdown background */
```

**Usage:**
- Dark mode sidebar
- Dark mode dropdowns
- Dark mode cards
- Dark mode modals


### Color Usage Guidelines

**Do:**
- Use primary color for main actions
- Use semantic colors for status
- Maintain sufficient contrast ratios
- Test colors in both light and dark modes

**Don't:**
- Use too many colors in one view
- Use color as the only indicator
- Override brand colors
- Use low-contrast combinations

---

## Typography

### Font Families

**Primary Font:**
```css
font-family: 'Plus Jakarta Sans', sans-serif;
```

**Usage:**
- All English text
- UI elements
- Headings and body text

**Bangla Font:**
```css
font-family: 'Noto Sans Bengali', 'Plus Jakarta Sans', sans-serif;
```

**Usage:**
- Bangla language content
- Bangla numbers (০১২৩৪৫৬৭৮৯)
- Mixed language text

**Monospace Font:**
```css
font-family: 'JetBrains Mono', monospace;
```

**Usage:**
- Code snippets
- API keys
- Technical identifiers
- Log outputs

### Font Sizes

| Size | Class | Pixels | Usage |
|------|-------|--------|-------|
| **xs** | `text-xs` | 12px | Small labels, captions |
| **sm** | `text-sm` | 14px | Body text, form inputs |
| **base** | `text-base` | 16px | Default body text |
| **lg** | `text-lg` | 18px | Emphasized text |
| **xl** | `text-xl` | 20px | Section headings |
| **2xl** | `text-2xl` | 24px | Page headings |
| **3xl** | `text-3xl` | 30px | Hero headings |

### Font Weights

| Weight | Class | Value | Usage |
|--------|-------|-------|-------|
| **Normal** | `font-normal` | 400 | Body text |
| **Medium** | `font-medium` | 500 | Emphasized text |
| **Semibold** | `font-semibold` | 600 | Subheadings |
| **Bold** | `font-bold` | 700 | Headings |
| **Extrabold** | `font-extrabold` | 800 | Hero text |

### Line Heights

| Height | Class | Value | Usage |
|--------|-------|-------|-------|
| **Tight** | `leading-tight` | 1.25 | Headings |
| **Snug** | `leading-snug` | 1.375 | Subheadings |
| **Normal** | `leading-normal` | 1.5 | Body text |
| **Relaxed** | `leading-relaxed` | 1.625 | Long-form content |

### Typography Examples

**Page Heading:**
```html
<h1 class="text-2xl font-bold text-black dark:text-white">
  Dashboard
</h1>
```

**Section Heading:**
```html
<h2 class="text-xl font-semibold text-black dark:text-white mb-4">
  Recent Orders
</h2>
```

**Body Text:**
```html
<p class="text-sm text-gray-1 dark:text-gray-400">
  Your order has been successfully processed.
</p>
```

**Bangla Text:**
```html
<span class="font-bangla text-sm">
  ৳ ১,২৫,০০০
</span>
```

---

## Spacing & Layout

### Spacing Scale

Gen-ERP uses Tailwind's default spacing scale (4px base unit):

| Size | Class | Pixels | Usage |
|------|-------|--------|-------|
| **1** | `p-1`, `m-1` | 4px | Tight spacing |
| **2** | `p-2`, `m-2` | 8px | Small spacing |
| **3** | `p-3`, `m-3` | 12px | Default spacing |
| **4** | `p-4`, `m-4` | 16px | Medium spacing |
| **6** | `p-6`, `m-6` | 24px | Large spacing |
| **8** | `p-8`, `m-8` | 32px | Extra large spacing |

### Layout Patterns

**Container:**
```html
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <!-- Content -->
</div>
```

**Card:**
```html
<div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
  <!-- Card content -->
</div>
```

**Grid Layout:**
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <!-- Grid items -->
</div>
```

### Breakpoints

| Breakpoint | Min Width | Usage |
|------------|-----------|-------|
| **sm** | 640px | Small tablets |
| **md** | 768px | Tablets |
| **lg** | 1024px | Laptops |
| **xl** | 1280px | Desktops |
| **2xl** | 1536px | Large desktops |

---

## Components

### Buttons

**Primary Button:**
```html
<button class="inline-flex items-center justify-center px-5 py-3.5 text-sm font-medium 
               bg-primary text-white rounded-lg shadow-theme-xs 
               hover:bg-primary-dark transition">
  Save Changes
</button>
```

**Secondary Button:**
```html
<button class="inline-flex items-center justify-center px-5 py-3.5 text-sm font-medium 
               bg-white text-gray-700 rounded-lg ring-1 ring-inset ring-gray-300 
               hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
  Cancel
</button>
```

**Danger Button:**
```html
<button class="inline-flex items-center justify-center px-5 py-3.5 text-sm font-medium 
               bg-danger text-white rounded-lg hover:bg-red-800 transition">
  Delete
</button>
```

**Button Sizes:**
- Small: `px-4 py-3 text-sm`
- Medium: `px-5 py-3.5 text-sm` (default)
- Large: `px-6 py-4 text-base`

### Cards

**Basic Card:**
```html
<div class="bg-white dark:bg-gray-900 rounded-lg shadow border border-stroke dark:border-gray-800 p-6">
  <h3 class="text-lg font-semibold mb-4">Card Title</h3>
  <p class="text-sm text-gray-1">Card content goes here.</p>
</div>
```

**Stat Card:**
```html
<div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
  <div class="flex items-center justify-between">
    <div>
      <p class="text-sm text-gray-1">Total Revenue</p>
      <p class="text-2xl font-bold text-black dark:text-white">৳ 1,25,000</p>
    </div>
    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
      <svg class="w-6 h-6 text-primary"><!-- Icon --></svg>
    </div>
  </div>
  <div class="mt-4 text-xs text-success">
    +12.5% from last month
  </div>
</div>
```

### Tables

**Data Table:**
```html
<div class="overflow-x-auto">
  <table class="w-full">
    <thead class="bg-gray-3 dark:bg-gray-800">
      <tr>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-1 uppercase">
          Name
        </th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-1 uppercase">
          Status
        </th>
      </tr>
    </thead>
    <tbody class="divide-y divide-stroke dark:divide-gray-800">
      <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
        <td class="px-4 py-3 text-sm text-black dark:text-white">
          John Doe
        </td>
        <td class="px-4 py-3">
          <span class="px-2 py-1 text-xs font-semibold rounded-full bg-success/10 text-success">
            Active
          </span>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

### Modals

**Modal Structure:**
```html
<div class="fixed inset-0 flex items-center justify-center z-99999">
  <!-- Backdrop -->
  <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]"></div>
  
  <!-- Modal -->
  <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
    <h3 class="text-lg font-semibold mb-4">Modal Title</h3>
    <p class="text-sm text-gray-1 mb-6">Modal content goes here.</p>
    <div class="flex justify-end gap-3">
      <button class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
        Cancel
      </button>
      <button class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">
        Confirm
      </button>
    </div>
  </div>
</div>
```


---

## Icons

Gen-ERP uses **Heroicons** for consistent iconography.

### Icon Sizes

| Size | Class | Pixels | Usage |
|------|-------|--------|-------|
| **Small** | `w-4 h-4` | 16px | Inline icons, badges |
| **Medium** | `w-5 h-5` | 20px | Buttons, navigation |
| **Large** | `w-6 h-6` | 24px | Headers, emphasis |
| **XL** | `w-8 h-8` | 32px | Feature icons |

### Icon Usage

**In Buttons:**
```html
<button class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg">
  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
  </svg>
  Add New
</button>
```

**In Navigation:**
```html
<a href="/dashboard" class="flex items-center gap-3 px-4 py-2.5 rounded-lg">
  <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <!-- Icon path -->
  </svg>
  <span>Dashboard</span>
</a>
```

**Status Icons:**
```html
<!-- Success -->
<svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
</svg>

<!-- Warning -->
<svg class="w-5 h-5 text-warning" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92z"/>
</svg>

<!-- Error -->
<svg class="w-5 h-5 text-danger" fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
</svg>
```

---

## Forms

### Form Inputs

**Text Input:**
```html
<div class="mb-4">
  <label class="block text-sm font-medium text-black dark:text-white mb-2">
    Full Name
  </label>
  <input type="text" 
         class="w-full rounded-lg border border-stroke bg-white px-3 h-9 text-sm 
                text-black placeholder-gray-1/50 outline-none transition-all
                focus:border-primary focus:ring-2 focus:ring-primary/10
                dark:bg-gray-900 dark:border-gray-700 dark:text-white"
         placeholder="Enter your name">
</div>
```

**Input with Error:**
```html
<div class="mb-4">
  <label class="block text-sm font-medium text-black dark:text-white mb-2">
    Email
  </label>
  <input type="email" 
         class="w-full rounded-lg border border-danger bg-white px-3 h-9 text-sm 
                text-black outline-none"
         value="invalid-email">
  <p class="mt-1 text-xs text-danger">Please enter a valid email address</p>
</div>
```

**Select Dropdown:**
```html
<div class="mb-4">
  <label class="block text-sm font-medium text-black dark:text-white mb-2">
    Status
  </label>
  <select class="w-full rounded-lg border border-stroke bg-white px-3 h-9 text-sm 
                 text-black outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
    <option>Active</option>
    <option>Inactive</option>
    <option>Pending</option>
  </select>
</div>
```

**Textarea:**
```html
<div class="mb-4">
  <label class="block text-sm font-medium text-black dark:text-white mb-2">
    Description
  </label>
  <textarea rows="4" 
            class="w-full rounded-lg border border-stroke bg-white px-3 py-2 text-sm 
                   text-black placeholder-gray-1/50 outline-none transition-all
                   focus:border-primary focus:ring-2 focus:ring-primary/10"
            placeholder="Enter description"></textarea>
</div>
```

**Checkbox:**
```html
<label class="flex items-center gap-2 cursor-pointer">
  <input type="checkbox" 
         class="w-4 h-4 rounded border-stroke text-primary 
                focus:ring-2 focus:ring-primary/10">
  <span class="text-sm text-black dark:text-white">
    I agree to the terms and conditions
  </span>
</label>
```

**Radio Button:**
```html
<div class="space-y-2">
  <label class="flex items-center gap-2 cursor-pointer">
    <input type="radio" name="plan" value="free"
           class="w-4 h-4 border-stroke text-primary focus:ring-2 focus:ring-primary/10">
    <span class="text-sm text-black dark:text-white">Free Plan</span>
  </label>
  <label class="flex items-center gap-2 cursor-pointer">
    <input type="radio" name="plan" value="pro"
           class="w-4 h-4 border-stroke text-primary focus:ring-2 focus:ring-primary/10">
    <span class="text-sm text-black dark:text-white">Pro Plan</span>
  </label>
</div>
```

### Form Layout

**Horizontal Form:**
```html
<form class="space-y-4">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium mb-2">First Name</label>
      <input type="text" class="w-full rounded-lg border border-stroke px-3 h-9">
    </div>
    <div>
      <label class="block text-sm font-medium mb-2">Last Name</label>
      <input type="text" class="w-full rounded-lg border border-stroke px-3 h-9">
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium mb-2">Email</label>
    <input type="email" class="w-full rounded-lg border border-stroke px-3 h-9">
  </div>
  <div class="flex justify-end gap-3">
    <button type="button" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
      Cancel
    </button>
    <button type="submit" class="px-4 py-2 text-sm bg-primary text-white rounded-lg">
      Save
    </button>
  </div>
</form>
```

---

## Status & Feedback

### Status Badges

**Success:**
```html
<span class="px-2 py-1 text-xs font-semibold rounded-full bg-success/10 text-success">
  Active
</span>
```

**Warning:**
```html
<span class="px-2 py-1 text-xs font-semibold rounded-full bg-warning/10 text-warning">
  Pending
</span>
```

**Danger:**
```html
<span class="px-2 py-1 text-xs font-semibold rounded-full bg-danger/10 text-danger">
  Inactive
</span>
```

**Info:**
```html
<span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary/10 text-primary">
  Draft
</span>
```

### Alert Messages

**Success Alert:**
```html
<div class="flex items-center gap-3 px-4 py-3 rounded-lg border bg-success/10 border-success/30 text-success">
  <span class="text-lg">✓</span>
  <p class="text-sm font-medium">Your changes have been saved successfully.</p>
  <button class="ml-auto hover:opacity-70">✕</button>
</div>
```

**Error Alert:**
```html
<div class="flex items-center gap-3 px-4 py-3 rounded-lg border bg-danger/10 border-danger/30 text-danger">
  <span class="text-lg">✕</span>
  <p class="text-sm font-medium">An error occurred. Please try again.</p>
  <button class="ml-auto hover:opacity-70">✕</button>
</div>
```

### Loading States

**Spinner:**
```html
<div class="flex items-center justify-center">
  <div class="w-8 h-8 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
</div>
```

**Skeleton Loader:**
```html
<div class="animate-pulse space-y-4">
  <div class="h-4 bg-gray-200 rounded w-3/4"></div>
  <div class="h-4 bg-gray-200 rounded w-1/2"></div>
  <div class="h-4 bg-gray-200 rounded w-5/6"></div>
</div>
```

### Progress Bar:**
```html
<div class="w-full bg-gray-200 rounded-full h-2">
  <div class="bg-primary h-2 rounded-full" style="width: 65%"></div>
</div>
```

---

## Dark Mode

Gen-ERP supports system-wide dark mode using Tailwind's `dark:` variant.

### Implementation

**Toggle Dark Mode:**
```javascript
// Toggle dark mode
document.documentElement.classList.toggle('dark')

// Save preference
localStorage.setItem('theme', isDark ? 'dark' : 'light')
```

**Dark Mode Classes:**
```html
<!-- Background -->
<div class="bg-white dark:bg-gray-900">

<!-- Text -->
<p class="text-black dark:text-white">

<!-- Border -->
<div class="border border-stroke dark:border-gray-800">

<!-- Hover -->
<button class="hover:bg-gray-100 dark:hover:bg-gray-800">
```

### Dark Mode Color Mapping

| Light Mode | Dark Mode | Usage |
|------------|-----------|-------|
| `bg-white` | `bg-gray-900` | Backgrounds |
| `text-black` | `text-white` | Primary text |
| `text-gray-1` | `text-gray-400` | Secondary text |
| `border-stroke` | `border-gray-800` | Borders |
| `bg-gray-50` | `bg-gray-800` | Hover states |

---

## Accessibility

### WCAG 2.1 AA Compliance

**Color Contrast:**
- Text: Minimum 4.5:1 ratio
- Large text: Minimum 3:1 ratio
- UI components: Minimum 3:1 ratio

**Keyboard Navigation:**
- All interactive elements are keyboard accessible
- Visible focus indicators
- Logical tab order

**Screen Readers:**
- Semantic HTML elements
- ARIA labels where needed
- Alt text for images

### Accessibility Guidelines

**Focus States:**
```html
<button class="focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
  Click me
</button>
```

**ARIA Labels:**
```html
<button aria-label="Close modal">
  <svg class="w-5 h-5"><!-- X icon --></svg>
</button>
```

**Skip Links:**
```html
<a href="#main-content" class="sr-only focus:not-sr-only">
  Skip to main content
</a>
```

---

**Last Updated:** March 4, 2026  
**Version:** 1.0.0  
**Maintainer:** Gen-ERP Design Team
