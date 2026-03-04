# API Reference Documentation

## Table of Contents
- [Overview](#overview)
- [Authentication](#authentication)
- [API Conventions](#api-conventions)
- [Auth Endpoints](#auth-endpoints)
- [CRM Endpoints](#crm-endpoints)
- [Logistics Endpoints](#logistics-endpoints)
- [Invoice & Sales Endpoints](#invoice--sales-endpoints)
- [Product & Inventory Endpoints](#product--inventory-endpoints)
- [HR & Payroll Endpoints](#hr--payroll-endpoints)
- [Accounting Endpoints](#accounting-endpoints)
- [CMS Endpoints](#cms-endpoints)
- [Project Management Endpoints](#project-management-endpoints)
- [Public API Endpoints](#public-api-endpoints)
- [Error Responses](#error-responses)

---

## Overview

Gen-ERP provides a comprehensive RESTful API with 200+ endpoints organized by business domain. All endpoints follow consistent patterns for authentication, pagination, filtering, and error handling.

**Base URL**: `/api/v1`
**API Version**: v1
**Response Format**: JSON
**Authentication**: Laravel Sanctum (Bearer token)

---

## Authentication

### Authentication Methods

**1. Session-Based (Web)**
- Used by Inertia.js frontend
- CSRF token required
- Cookie-based authentication

**2. Token-Based (API)**
- Laravel Sanctum tokens
- Bearer token in Authorization header
- Stateless authentication

### Required Headers

```http
Authorization: Bearer {token}
X-Company-ID: {company_id}
Accept: application/json
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

---

## API Conventions

### Standard Response Format

**Success Response:**
```json
{
    "success": true,
    "data": { /* resource data */ },
    "message": "Operation successful"
}
```

**Error Response:**
```json
{
    "success": false,
    "data": null,
    "message": "Error message",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Pagination

All list endpoints support pagination:

**Query Parameters:**
- `page` — Page number (default: 1)
- `per_page` — Items per page (default: 15, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "data": [ /* items */ ],
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7,
        "from": 1,
        "to": 15
    }
}
```

### Filtering & Sorting

**Common Query Parameters:**
- `search` — Full-text search
- `status` — Filter by status
- `sort_by` — Sort field (default: created_at)
- `sort_order` — asc/desc (default: desc)
- `date_from` — Start date filter
- `date_to` — End date filter

### HTTP Status Codes

- `200 OK` — Success
- `201 Created` — Resource created
- `204 No Content` — Success with no response body
- `400 Bad Request` — Invalid request
- `401 Unauthorized` — Authentication required
- `403 Forbidden` — Insufficient permissions
- `404 Not Found` — Resource not found
- `422 Unprocessable Entity` — Validation errors
- `429 Too Many Requests` — Rate limit exceeded
- `500 Internal Server Error` — Server error

---

## Auth Endpoints

### POST /v1/auth/login
**Description**: Authenticate user and receive token
**Authentication**: None (public)

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "token": "1|abc123...",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com"
        },
        "active_company": {
            "id": 1,
            "name": "Demo Company"
        },
        "two_factor_required": false
    },
    "message": "Login successful"
}
```

**With 2FA Response (200):**
```json
{
    "success": true,
    "data": {
        "temp_token": "temp|xyz789...",
        "two_factor_required": true
    },
    "message": "2FA code required"
}
```

**Error Response (401):**
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

---

### POST /v1/auth/two-factor/challenge
**Description**: Complete 2FA authentication
**Authentication**: Temporary token

**Request Body:**
```json
{
    "code": "123456"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "token": "1|abc123...",
        "user": { /* user data */ },
        "active_company": { /* company data */ }
    }
}
```

---

### POST /v1/auth/register
**Description**: Register new user
**Authentication**: None (public)

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

---

### POST /v1/auth/logout
**Description**: Logout and revoke token
**Authentication**: Required

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

### GET /v1/auth/user
**Description**: Get authenticated user details
**Authentication**: Required

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "companies": [
            {
                "id": 1,
                "name": "Demo Company",
                "role": "admin"
            }
        ]
    }
}
```

---

### POST /v1/auth/switch-company/{companyId}
**Description**: Switch active company context
**Authentication**: Required

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "company": {
            "id": 2,
            "name": "Another Company"
        }
    },
    "message": "Company switched successfully"
}
```

---

## CRM Endpoints

### Lead Management (63+ endpoints)

#### GET /v1/crm/leads
**Description**: List all leads with filtering
**Authentication**: Required
**Authorization**: View leads permission

**Query Parameters:**
- `search` — Search by name, email, phone, company
- `status` — Filter by status (new, contacted, qualified, converted, lost)
- `source` — Filter by source
- `assigned_to` — Filter by assigned user ID
- `score_min` — Minimum lead score
- `score_max` — Maximum lead score
- `page` — Page number
- `per_page` — Items per page

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "first_name": "John",
                "last_name": "Doe",
                "email": "john@example.com",
                "phone": "+8801712345678",
                "company_name": "ABC Corp",
                "status": "qualified",
                "source": "website",
                "score": 85,
                "estimated_value": 50000.00,
                "assigned_to": {
                    "id": 5,
                    "name": "Sales Rep"
                },
                "created_at": "2026-03-01T10:00:00Z"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 100
    }
}
```

---

#### POST /v1/crm/leads
**Description**: Create new lead
**Authentication**: Required
**Authorization**: Create leads permission

**Request Body:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+8801712345678",
    "company_name": "ABC Corp",
    "job_title": "CEO",
    "source": "website",
    "estimated_value": 50000.00,
    "expected_close_date": "2026-04-01",
    "notes": "Interested in ERP solution"
}
```

**Validation Rules:**
- `first_name` — required, string, max:255
- `last_name` — required, string, max:255
- `email` — nullable, email, max:255
- `phone` — nullable, string, max:50
- `company_name` — nullable, string, max:255
- `source` — nullable, in:website,referral,social_media,email_campaign,cold_call,trade_show,partner
- `estimated_value` — nullable, numeric, min:0
- `expected_close_date` — nullable, date, after:today

**Success Response (201):**
```json
{
    "success": true,
    "data": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "first_name": "John",
        "last_name": "Doe",
        "status": "new",
        "score": 0,
        /* ... other fields ... */
    },
    "message": "Lead created successfully"
}
```

---

#### GET /v1/crm/leads/{uuid}
**Description**: Get lead details
**Authentication**: Required
**Authorization**: View lead permission

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "first_name": "John",
        "last_name": "Doe",
        "full_name": "John Doe",
        "email": "john@example.com",
        "phone": "+8801712345678",
        "company_name": "ABC Corp",
        "job_title": "CEO",
        "status": "qualified",
        "source": "website",
        "score": 85,
        "estimated_value": 50000.00,
        "expected_close_date": "2026-04-01",
        "last_contacted_at": "2026-03-02T14:30:00Z",
        "qualified_at": "2026-03-03T10:00:00Z",
        "assigned_to": {
            "id": 5,
            "name": "Sales Rep",
            "email": "sales@company.com"
        },
        "notes": [
            {
                "id": 1,
                "content": "Initial contact made",
                "created_by": "Sales Rep",
                "created_at": "2026-03-01T10:00:00Z"
            }
        ],
        "tags": [
            {
                "id": 1,
                "name": "Hot Lead",
                "color": "#FF0000"
            }
        ],
        "activities": [
            {
                "id": 1,
                "type": "call",
                "title": "Follow-up call",
                "scheduled_at": "2026-03-05T14:00:00Z",
                "status": "scheduled"
            }
        ],
        "created_at": "2026-03-01T10:00:00Z",
        "updated_at": "2026-03-03T10:00:00Z"
    }
}
```

---

#### PUT /v1/crm/leads/{uuid}
**Description**: Update lead
**Authentication**: Required
**Authorization**: Update lead permission

**Request Body:** (all fields optional)
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+8801712345678",
    "company_name": "ABC Corp",
    "job_title": "CEO",
    "status": "qualified",
    "estimated_value": 75000.00
}
```

---

#### POST /v1/crm/leads/{uuid}/assign
**Description**: Assign lead to user
**Authentication**: Required

**Request Body:**
```json
{
    "assigned_to": 5
}
```

---

#### POST /v1/crm/leads/{uuid}/score
**Description**: Update lead score
**Authentication**: Required

**Request Body:**
```json
{
    "score": 85
}
```

**Validation:**
- `score` — required, integer, min:0, max:100

---

#### POST /v1/crm/leads/{uuid}/qualify
**Description**: Mark lead as qualified
**Authentication**: Required

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "status": "qualified",
        "qualified_at": "2026-03-04T10:00:00Z"
    },
    "message": "Lead qualified successfully"
}
```

---

#### POST /v1/crm/leads/{uuid}/notes
**Description**: Add note to lead
**Authentication**: Required

**Request Body:**
```json
{
    "content": "Follow-up call completed. Customer interested in demo.",
    "metadata": {
        "type": "call",
        "duration": "15 minutes"
    }
}
```

---

#### GET /v1/crm/leads/statistics
**Description**: Get lead statistics
**Authentication**: Required

**Query Parameters:**
- `date_from` — Start date
- `date_to` — End date
- `assigned_to` — Filter by user

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "total_leads": 150,
        "by_status": {
            "new": 45,
            "contacted": 30,
            "qualified": 40,
            "converted": 25,
            "lost": 10
        },
        "by_source": {
            "website": 60,
            "referral": 40,
            "social_media": 30,
            "email_campaign": 20
        },
        "average_score": 65,
        "conversion_rate": 16.67,
        "total_estimated_value": 5000000.00
    }
}
```

---

#### POST /v1/crm/leads/bulk-assign
**Description**: Bulk assign leads to user
**Authentication**: Required

**Request Body:**
```json
{
    "lead_uuids": [
        "550e8400-e29b-41d4-a716-446655440000",
        "660e8400-e29b-41d4-a716-446655440001"
    ],
    "assigned_to": 5
}
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "updated_count": 2
    },
    "message": "2 leads assigned successfully"
}
```

---

### Opportunity Management

#### GET /v1/crm/opportunities
**Description**: List opportunities
**Authentication**: Required

**Query Parameters:**
- `search` — Search by name
- `pipeline_id` — Filter by pipeline
- `stage_id` — Filter by stage
- `status` — Filter by status (open, won, lost)
- `assigned_to` — Filter by assigned user

---

#### POST /v1/crm/opportunities
**Description**: Create opportunity
**Authentication**: Required

**Request Body:**
```json
{
    "lead_id": 1,
    "pipeline_id": 1,
    "stage_id": 1,
    "name": "ABC Corp - ERP Implementation",
    "value": 500000.00,
    "probability": 60,
    "expected_close_date": "2026-06-01",
    "assigned_to": 5
}
```

---

#### POST /v1/crm/opportunities/{uuid}/move-to-stage
**Description**: Move opportunity to different stage
**Authentication**: Required

**Request Body:**
```json
{
    "stage_id": 3
}
```

---

#### POST /v1/crm/opportunities/{uuid}/mark-as-won
**Description**: Mark opportunity as won
**Authentication**: Required

**Request Body:**
```json
{
    "won_reason": "Competitive pricing and features",
    "actual_value": 480000.00
}
```

---

#### GET /v1/crm/opportunities/forecast
**Description**: Revenue forecast
**Authentication**: Required

**Query Parameters:**
- `date_from` — Start date
- `date_to` — End date
- `pipeline_id` — Filter by pipeline

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "total_value": 5000000.00,
        "weighted_value": 3200000.00,
        "by_stage": [
            {
                "stage": "Prospecting",
                "count": 20,
                "total_value": 1000000.00,
                "weighted_value": 200000.00
            },
            {
                "stage": "Proposal",
                "count": 15,
                "total_value": 2000000.00,
                "weighted_value": 1200000.00
            }
        ],
        "expected_wins": 12,
        "win_rate": 35.5
    }
}
```

---

## Logistics Endpoints

### Shipment Management

#### GET /v1/logistics/shipments
**Description**: List shipments
**Authentication**: Required

**Query Parameters:**
- `search` — Search by tracking number, recipient
- `status` — Filter by status
- `carrier_id` — Filter by carrier
- `date_from` — Start date
- `date_to` — End date

---

#### POST /v1/logistics/shipments
**Description**: Create shipment
**Authentication**: Required

**Request Body:**
```json
{
    "carrier_id": 1,
    "invoice_id": 123,
    "customer_id": 45,
    "sender_name": "Company Name",
    "sender_phone": "+8801712345678",
    "sender_address": "123 Main St, Dhaka",
    "recipient_name": "John Doe",
    "recipient_phone": "+8801798765432",
    "recipient_address": "456 Oak Ave, Chittagong",
    "weight": 2.5,
    "declared_value": 5000.00,
    "cod_amount": 5000.00,
    "items": [
        {
            "product_id": 10,
            "quantity": 2,
            "description": "Product Name"
        }
    ]
}
```

---

#### POST /v1/logistics/shipments/bulk
**Description**: Bulk create shipments
**Authentication**: Required

**Request Body:**
```json
{
    "shipments": [
        { /* shipment 1 data */ },
        { /* shipment 2 data */ }
    ]
}
```

---

#### GET /v1/logistics/tracking/{trackingNumber}
**Description**: Track shipment by tracking number
**Authentication**: Required

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "tracking_number": "PATH123456",
        "status": "in_transit",
        "current_location": "Dhaka Hub",
        "estimated_delivery": "2026-03-06",
        "events": [
            {
                "status": "dispatched",
                "location": "Dhaka",
                "description": "Shipment dispatched from origin",
                "occurred_at": "2026-03-04T10:00:00Z"
            },
            {
                "status": "in_transit",
                "location": "Dhaka Hub",
                "description": "In transit to destination",
                "occurred_at": "2026-03-04T14:30:00Z"
            }
        ]
    }
}
```

---

#### POST /v1/logistics/shipments/{id}/cod/mark-collected
**Description**: Mark COD as collected
**Authentication**: Required

**Request Body:**
```json
{
    "collected_amount": 5000.00,
    "collected_at": "2026-03-05T15:00:00Z"
}
```

---

## Invoice & Sales Endpoints

#### GET /v1/invoices
**Description**: List invoices
**Authentication**: Required

**Query Parameters:**
- `search` — Search by invoice number, customer
- `status` — Filter by status (draft, sent, paid, overdue, cancelled)
- `customer_id` — Filter by customer
- `date_from` — Start date
- `date_to` — End date

---

#### POST /v1/invoices
**Description**: Create invoice
**Authentication**: Required

**Request Body:**
```json
{
    "customer_id": 45,
    "warehouse_id": 1,
    "invoice_date": "2026-03-04",
    "due_date": "2026-04-04",
    "items": [
        {
            "product_id": 10,
            "quantity": 2,
            "unit_price": 1000.00,
            "discount_amount": 50.00,
            "tax_amount": 150.00
        }
    ],
    "discount_amount": 100.00,
    "shipping_amount": 50.00,
    "notes": "Thank you for your business"
}
```

---

## Product & Inventory Endpoints

#### GET /v1/products
**Description**: List products
**Authentication**: Required

---

#### POST /v1/products
**Description**: Create product
**Authentication**: Required

---

#### GET /v1/stock-movements
**Description**: List stock movements
**Authentication**: Required

---

## HR & Payroll Endpoints

#### GET /v1/employees
**Description**: List employees
**Authentication**: Required

---

#### POST /v1/attendance/bulk
**Description**: Bulk mark attendance
**Authentication**: Required

---

## Public API Endpoints

### Public Site Rendering

#### GET /api/public/{tenant}/
**Description**: Get homepage
**Authentication**: None

---

#### GET /api/public/{tenant}/pages/{slug}
**Description**: Get page by slug
**Authentication**: None

---

#### POST /api/public/{tenant}/cart/items
**Description**: Add item to cart
**Authentication**: None (session-based)

---

## Error Responses

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
    "success": false,
    "message": "This action is unauthorized"
}
```

### 422 Validation Error
```json
{
    "success": false,
    "message": "The given data was invalid",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

### 429 Rate Limit
```json
{
    "success": false,
    "message": "Too many requests"
}
```

---

**Last Updated**: March 4, 2026
**Total Endpoints**: 200+
**API Version**: v1
