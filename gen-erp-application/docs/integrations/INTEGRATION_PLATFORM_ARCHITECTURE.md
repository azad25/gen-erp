# Integration Platform Architecture

**Version:** 1.0  
**Date:** 2026-03-06  
**Status:** Architecture Design & Implementation Plan

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Current Architecture](#current-architecture)
3. [Domain Overview](#domain-overview)
4. [Integration Patterns](#integration-patterns)
5. [Visual Automation Builder](#visual-automation-builder)
6. [Third-Party Integration Guide](#third-party-integration-guide)
7. [Implementation Roadmap](#implementation-roadmap)
8. [Technical Specifications](#technical-specifications)

---

## Executive Summary

This document outlines the architecture for a comprehensive integration and automation platform built into the ERP system. The platform combines three core domains (Integration, Workflow, Plugin) to provide users with:

- **No-code automation builder** (similar to Zapier/n8n/Make.com)
- **Pre-built integrations** for popular platforms (WooCommerce, Shopify, Facebook)
- **Custom integration capabilities** for any third-party API
- **Visual workflow designer** for business process automation
- **Plugin marketplace** for extensibility

**Key Differentiator:** Unlike standalone automation tools, this platform is deeply integrated with the ERP's business logic, providing seamless data flow between external platforms and internal operations.

---

## Current Architecture

### Three-Domain Foundation

The integration platform is built on three interconnected domains:


#### 1. Integration Domain (Connection Layer)

**Purpose:** Manages external service connections and data synchronization

**Current Implementation:**
- ✅ Integration catalog (browse available integrations)
- ✅ Company-specific installations
- ✅ Configuration storage (API keys, endpoints, credentials)
- ✅ Sync scheduling and status tracking
- ✅ Multi-tenancy support
- ✅ Event system (IntegrationInstalled, IntegrationUninstalled)
- ❌ Actual API client implementations (WooCommerce, Shopify, etc.)
- ❌ Webhook handlers
- ❌ OAuth flows

**Database Tables:**
- `integrations` - Master catalog of available integrations
- `company_integrations` - Company-specific installations and configs
- `integration_hooks` - Webhook configurations
- `integration_credentials` - Encrypted OAuth tokens
- `integration_logs` - Sync history and errors
- `sync_schedules` - Scheduled sync jobs
- `iot_devices` - Connected hardware devices

**Key Models:**
- `Integration` - Integration definition
- `CompanyIntegration` - Installed integration instance
- `SyncEngine` - Handles scheduled syncs
- `DeviceManager` - Manages IoT devices


#### 2. Workflow Domain (Business Process Automation)

**Purpose:** Defines approval flows and state transitions for business documents

**Current Implementation:**
- ✅ Workflow definitions (per document type)
- ✅ Status management with transitions
- ✅ Role-based permissions
- ✅ Approval workflows
- ✅ Auto-actions on transitions
- ✅ Workflow history tracking
- ❌ Visual workflow designer UI
- ❌ Integration with Integration domain
- ❌ Advanced auto-actions (integration triggers)

**Database Tables:**
- `workflow_definitions` - Workflow templates
- `workflow_statuses` - Available statuses per workflow
- `workflow_transitions` - Allowed state changes
- `workflow_instances` - Active workflow for a document
- `workflow_history` - Audit trail of transitions
- `workflow_approvals` - Pending approval requests

**Key Models:**
- `WorkflowDefinition` - Workflow template
- `WorkflowStatus` - Status definition
- `WorkflowTransition` - Transition rules
- `WorkflowInstance` - Active workflow
- `WorkflowService` - Core workflow engine

**Supported Document Types:**
- Sales Orders
- Purchase Orders
- Invoices
- Expenses
- Goods Receipts


#### 3. Plugin Domain (Extensibility Layer)

**Purpose:** Allows custom code to extend system functionality via hooks

**Current Implementation:**
- ✅ Plugin installation/uninstallation
- ✅ Enable/disable plugins
- ✅ Manifest-based configuration
- ✅ Hook registration system
- ✅ Security validation (no raw SQL)
- ❌ Hook dispatcher implementation
- ❌ Plugin marketplace
- ❌ Visual plugin builder

**Database Tables:**
- `plugins` - Installed plugins

**Key Models:**
- `Plugin` - Plugin definition
- `PluginManager` - Plugin lifecycle management

**Plugin Manifest Structure:**
```json
{
  "name": "WooCommerce Connector",
  "slug": "woocommerce-connector",
  "version": "1.0.0",
  "author": "Company Name",
  "description": "Sync products and orders with WooCommerce",
  "hooks": {
    "integration.sync.woocommerce": "WooCommercePlugin@syncOrder",
    "integration.webhook.woocommerce": "WooCommercePlugin@handleWebhook",
    "workflow.transition.approved": "WooCommercePlugin@onOrderApproved"
  }
}
```


---

## Domain Overview

### How the Three Domains Work Together

```
┌─────────────────────────────────────────────────────────────┐
│                    USER INTERFACE                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Integration  │  │   Workflow   │  │    Plugin    │      │
│  │   Manager    │  │   Designer   │  │  Marketplace │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              AUTOMATION ENGINE (To Be Built)                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Visual Builder: Drag-and-drop automation canvas     │  │
│  │  - Trigger nodes (ERP events, webhooks, schedules)   │  │
│  │  - Action nodes (API calls, data transforms)         │  │
│  │  - Condition nodes (if/else logic)                   │  │
│  │  - Plugin nodes (custom code execution)              │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        ▼                   ▼                   ▼
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│ Integration  │   │   Workflow   │   │    Plugin    │
│   Domain     │◄──┤    Domain    │──►│    Domain    │
│              │   │              │   │              │
│ - Connections│   │ - Approvals  │   │ - Custom     │
│ - Sync       │   │ - Transitions│   │   Logic      │
│ - Webhooks   │   │ - Auto-actions│   │ - Hooks      │
└──────────────┘   └──────────────┘   └──────────────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    ERP CORE DOMAINS                          │
│  Sales │ Purchase │ Inventory │ Accounting │ CRM │ HR │...  │
└─────────────────────────────────────────────────────────────┘
```


### Domain Interaction Patterns

#### Pattern 1: Workflow Triggers Integration
```
User Action → Workflow Transition → Auto-Action → Integration Sync → External API
```

**Example:** When sales order is approved, sync to WooCommerce
1. User approves sales order
2. Workflow transitions to "Approved" status
3. Auto-action triggers integration sync
4. Integration domain calls WooCommerce API
5. Order created in WooCommerce

#### Pattern 2: Integration Triggers Workflow
```
External Webhook → Integration Handler → Create Document → Start Workflow
```

**Example:** New order from Shopify creates sales order in ERP
1. Shopify sends webhook for new order
2. Integration domain receives webhook
3. Creates sales order in ERP
4. Workflow automatically starts for approval
5. Manager approves/rejects via workflow UI

#### Pattern 3: Plugin Provides Integration Logic
```
Integration Event → Plugin Hook → Custom Code → API Call
```

**Example:** WooCommerce plugin handles actual API communication
1. Integration domain fires sync event
2. Plugin hook catches event
3. Plugin executes custom WooCommerce API logic
4. Returns result to Integration domain


---

## Integration Patterns

### 1. Pre-Built Integrations (Marketplace)

**Concept:** Official integrations developed and maintained by the core team

**Examples:**
- WooCommerce (E-commerce)
- Shopify (E-commerce)
- Facebook Commerce (Social Commerce)
- QuickBooks (Accounting)
- Stripe (Payments)
- Twilio (SMS)
- SendGrid (Email)

**Implementation:**
```php
// 1. Create Integration record
Integration::create([
    'slug' => 'woocommerce',
    'name' => 'WooCommerce',
    'category' => IntegrationCategory::ECOMMERCE,
    'tier' => IntegrationTier::NATIVE,
    'config_schema' => [
        'store_url' => ['type' => 'url', 'required' => true],
        'consumer_key' => ['type' => 'string', 'required' => true],
        'consumer_secret' => ['type' => 'password', 'required' => true],
    ],
    'capabilities' => ['sync', 'webhook', 'realtime'],
]);

// 2. Create Plugin with API logic
$plugin = PluginManager::install($companyId, [
    'name' => 'WooCommerce Connector',
    'slug' => 'woocommerce-connector',
    'hooks' => [
        'integration.sync.woocommerce' => 'WooCommercePlugin@sync',
        'integration.webhook.woocommerce' => 'WooCommercePlugin@handleWebhook',
    ],
]);

// 3. User installs and configures
CompanyIntegration::create([
    'company_id' => $companyId,
    'integration_id' => $integration->id,
    'config' => [
        'store_url' => 'https://mystore.com',
        'consumer_key' => 'ck_xxx',
        'consumer_secret' => 'cs_xxx',
    ],
]);
```


### 2. Custom Integrations (User-Built)

**Concept:** Users can create integrations to their own APIs or custom platforms

**Use Cases:**
- Internal company systems
- Custom-built e-commerce platforms
- Proprietary inventory systems
- Legacy ERP systems
- Custom CRM platforms

**Implementation via Visual Builder:**
```json
{
  "name": "My Custom Store",
  "type": "custom",
  "authentication": {
    "type": "api_key",
    "header": "X-API-Key",
    "value": "{{config.api_key}}"
  },
  "endpoints": {
    "create_order": {
      "method": "POST",
      "url": "{{config.base_url}}/api/orders",
      "body": {
        "order_number": "{{order.number}}",
        "customer": "{{order.customer.name}}",
        "items": "{{order.items}}",
        "total": "{{order.total}}"
      }
    },
    "get_inventory": {
      "method": "GET",
      "url": "{{config.base_url}}/api/inventory"
    }
  }
}
```

**Visual Builder UI:**
1. User enters API base URL
2. Selects authentication method (API Key, OAuth, Basic Auth)
3. Defines endpoints with HTTP method and URL
4. Maps ERP fields to API fields using drag-and-drop
5. Tests connection
6. Saves as custom integration


### 3. Third-Party Plugin Integrations

**Concept:** Developers can build and publish plugins to the marketplace

**Developer Workflow:**
1. Create plugin manifest
2. Implement hook handlers
3. Test locally
4. Submit to marketplace
5. Users install from marketplace

**Example: Facebook Commerce Plugin**
```php
// plugins/facebook-commerce/FacebookCommercePlugin.php
class FacebookCommercePlugin
{
    public function syncProduct($product)
    {
        $config = $this->getConfig();
        $accessToken = $config['access_token'];
        $catalogId = $config['catalog_id'];
        
        $response = Http::post("https://graph.facebook.com/v18.0/{$catalogId}/products", [
            'access_token' => $accessToken,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'currency' => 'USD',
            'availability' => $product->stock > 0 ? 'in stock' : 'out of stock',
            'image_url' => $product->image_url,
        ]);
        
        return $response->json();
    }
    
    public function handleWebhook($payload)
    {
        // Handle Facebook webhook events
        if ($payload['entry'][0]['changes'][0]['field'] === 'product_catalog_update') {
            // Sync product from Facebook to ERP
        }
    }
}
```


---

## Visual Automation Builder

### Overview

The Visual Automation Builder is an n8n-style workflow designer that allows users to create complex automations without writing code.

**Key Features:**
- Drag-and-drop canvas
- Node-based workflow design
- Real-time execution preview
- Error handling and retry logic
- Conditional branching
- Data transformation
- Template library

### Node Types

#### 1. Trigger Nodes (Start Points)

**ERP Event Triggers:**
- Order Created
- Order Status Changed
- Product Stock Low
- Invoice Paid
- Customer Created
- Payment Received

**Schedule Triggers:**
- Every Hour
- Daily at specific time
- Weekly
- Monthly
- Custom cron expression

**Webhook Triggers:**
- Receive HTTP POST
- Receive HTTP GET
- Custom webhook URL

**Integration Triggers:**
- WooCommerce Order
- Shopify Order
- Facebook Lead
- Stripe Payment


#### 2. Action Nodes (Operations)

**ERP Actions:**
- Create Order
- Update Product
- Create Customer
- Create Invoice
- Update Inventory
- Create Journal Entry

**Integration Actions:**
- Send to WooCommerce
- Sync to Shopify
- Post to Facebook
- Send Email (SendGrid)
- Send SMS (Twilio)
- Create Stripe Charge

**HTTP Actions:**
- HTTP Request (GET/POST/PUT/DELETE)
- GraphQL Query
- SOAP Request

**Data Actions:**
- Transform Data
- Filter Array
- Map Fields
- Aggregate Data
- Split Array

#### 3. Logic Nodes (Control Flow)

**Condition Node:**
- If/Else branching
- Multiple conditions (AND/OR)
- Comparison operators (=, !=, >, <, contains, etc.)

**Switch Node:**
- Multiple branches based on value
- Default fallback branch

**Loop Node:**
- Iterate over array
- Execute actions for each item

**Delay Node:**
- Wait for specific duration
- Wait until specific time


#### 4. Plugin Nodes (Custom Code)

**Execute Plugin:**
- Select installed plugin
- Choose plugin method
- Pass parameters
- Receive result

**Custom Code:**
- JavaScript/PHP code editor
- Access to ERP data
- Sandboxed execution
- Return transformed data

### Workflow Canvas Example

```
┌─────────────────────────────────────────────────────────────┐
│                    Automation Canvas                         │
│                                                              │
│  ┌──────────────┐                                           │
│  │   Trigger    │                                           │
│  │ Order Created│                                           │
│  └──────┬───────┘                                           │
│         │                                                    │
│         ▼                                                    │
│  ┌──────────────┐                                           │
│  │  Condition   │                                           │
│  │ Total > $100 │                                           │
│  └──┬────────┬──┘                                           │
│     │ Yes    │ No                                           │
│     ▼        ▼                                               │
│  ┌─────┐  ┌─────┐                                          │
│  │Sync │  │Send │                                          │
│  │ to  │  │Email│                                          │
│  │WooC.│  └─────┘                                          │
│  └──┬──┘                                                    │
│     │                                                        │
│     ▼                                                        │
│  ┌──────────────┐                                           │
│  │   Workflow   │                                           │
│  │Start Approval│                                           │
│  └──────────────┘                                           │
└─────────────────────────────────────────────────────────────┘
```


### Workflow Storage Format

Workflows are stored as JSON in a new `automation_workflows` table:

```json
{
  "id": "wf_123",
  "name": "Sync High-Value Orders to WooCommerce",
  "description": "When order > $100, sync to WooCommerce and start approval",
  "trigger": {
    "type": "erp_event",
    "event": "order.created",
    "filters": {
      "total": {"operator": ">", "value": 100}
    }
  },
  "nodes": [
    {
      "id": "node_1",
      "type": "condition",
      "config": {
        "field": "{{trigger.order.total}}",
        "operator": ">",
        "value": 100
      },
      "position": {"x": 100, "y": 200}
    },
    {
      "id": "node_2",
      "type": "integration_action",
      "config": {
        "integration": "woocommerce",
        "action": "create_order",
        "mapping": {
          "order_number": "{{trigger.order.number}}",
          "customer_email": "{{trigger.order.customer.email}}",
          "items": "{{trigger.order.items}}"
        }
      },
      "position": {"x": 300, "y": 200}
    },
    {
      "id": "node_3",
      "type": "workflow_action",
      "config": {
        "action": "start_workflow",
        "document_type": "sales_order",
        "document_id": "{{trigger.order.id}}"
      },
      "position": {"x": 500, "y": 200}
    }
  ],
  "connections": [
    {"from": "trigger", "to": "node_1"},
    {"from": "node_1", "to": "node_2", "condition": "true"},
    {"from": "node_2", "to": "node_3"}
  ]
}
```


---

## Third-Party Integration Guide

### How to Integrate Any Platform

#### Step 1: Identify Integration Type

**Option A: Pre-Built Integration (If Available)**
- Browse marketplace
- Install integration
- Configure credentials
- Done!

**Option B: Custom Integration (DIY)**
- Use Visual Builder
- Define API endpoints
- Map fields
- Test and save

**Option C: Plugin Development (Advanced)**
- Write custom plugin
- Implement API logic
- Publish to marketplace
- Share with community

#### Step 2: Authentication Methods

**Supported Auth Types:**

1. **API Key**
   - Header-based: `X-API-Key: your_key`
   - Query parameter: `?api_key=your_key`

2. **OAuth 2.0**
   - Authorization Code flow
   - Client Credentials flow
   - Automatic token refresh

3. **Basic Auth**
   - Username + Password
   - Base64 encoded

4. **Bearer Token**
   - JWT tokens
   - Custom bearer tokens

5. **Custom Headers**
   - Any custom authentication scheme


#### Step 3: Platform-Specific Examples

### WooCommerce Integration

**Authentication:** Consumer Key + Consumer Secret

**Endpoints:**
- `GET /wp-json/wc/v3/products` - List products
- `POST /wp-json/wc/v3/products` - Create product
- `GET /wp-json/wc/v3/orders` - List orders
- `POST /wp-json/wc/v3/orders` - Create order

**Field Mapping:**
```
ERP Product → WooCommerce Product
├─ name → name
├─ description → description
├─ price → regular_price
├─ sku → sku
├─ stock_quantity → stock_quantity
└─ images[0].url → images[0].src
```

**Automation Example:**
```
Trigger: Product Updated in ERP
↓
Condition: Product is_published = true
↓
Action: Update WooCommerce Product
↓
Result: Product synced to online store
```

### Shopify Integration

**Authentication:** API Key + Password (Private App) or OAuth

**Endpoints:**
- `GET /admin/api/2024-01/products.json` - List products
- `POST /admin/api/2024-01/products.json` - Create product
- `GET /admin/api/2024-01/orders.json` - List orders

**Webhook Support:**
- `orders/create` - New order created
- `products/update` - Product updated
- `inventory_levels/update` - Stock changed

**Automation Example:**
```
Trigger: Shopify Webhook (Order Created)
↓
Action: Create Sales Order in ERP
↓
Action: Start Approval Workflow
↓
Condition: Workflow Status = Approved
↓
Action: Update Shopify Order Status to "Processing"
```


### Facebook Commerce Integration

**Authentication:** OAuth 2.0 (Facebook Login)

**Endpoints:**
- `GET /v18.0/{catalog-id}/products` - List products
- `POST /v18.0/{catalog-id}/products` - Create product
- `GET /v18.0/{page-id}/commerce_orders` - List orders

**Required Permissions:**
- `catalog_management`
- `pages_manage_metadata`
- `pages_read_engagement`

**Automation Example:**
```
Trigger: Product Created in ERP
↓
Transform: Map ERP fields to Facebook format
↓
Action: Create Facebook Catalog Product
↓
Action: Post to Facebook Page
↓
Result: Product available on Facebook Shop
```

### Custom Platform Integration

**Example: Internal Warehouse System**

**Step 1: Define API in Visual Builder**
```
Base URL: https://warehouse.company.com/api
Auth: API Key in header (X-Warehouse-Key)

Endpoints:
- GET /inventory → Get stock levels
- POST /shipments → Create shipment
- PUT /inventory/{sku} → Update stock
```

**Step 2: Create Automation**
```
Trigger: Sales Order Status = "Ready to Ship"
↓
Action: HTTP POST to /shipments
  Body: {
    "order_id": "{{order.number}}",
    "items": "{{order.items}}",
    "address": "{{order.shipping_address}}"
  }
↓
Action: Update Order Status to "Shipped"
↓
Action: Send Email to Customer
```


---

## Implementation Roadmap

### Phase 1: Foundation (Current - Complete)

**Status:** ✅ Complete

- [x] Integration domain DDD architecture
- [x] Workflow domain with approval flows
- [x] Plugin domain with manifest system
- [x] Database schema for all three domains
- [x] Basic UI for integration management
- [x] Multi-tenancy support

### Phase 2: Automation Engine (Next - 4-6 weeks)

**Priority:** High

**Backend Tasks:**
1. Create `automation_workflows` table
2. Build `AutomationEngine` service
3. Implement workflow execution engine
4. Add event dispatcher for triggers
5. Create node executor classes
6. Implement data transformation engine
7. Add error handling and retry logic
8. Create webhook receiver endpoint

**Frontend Tasks:**
1. Build workflow canvas component (Vue Flow)
2. Create node library (trigger, action, logic nodes)
3. Implement drag-and-drop functionality
4. Add field mapping UI
5. Create workflow testing interface
6. Build execution log viewer

**Deliverables:**
- Users can create basic automations
- Trigger on ERP events
- Execute simple actions
- View execution history


### Phase 3: Pre-Built Integrations (8-12 weeks)

**Priority:** High

**Integrations to Build:**

1. **WooCommerce** (Week 1-2)
   - Product sync (bidirectional)
   - Order sync (bidirectional)
   - Inventory sync
   - Webhook support

2. **Shopify** (Week 3-4)
   - Product sync
   - Order sync
   - Inventory sync
   - Webhook support

3. **Facebook Commerce** (Week 5-6)
   - Catalog sync
   - Order import
   - Page posting

4. **Stripe** (Week 7-8)
   - Payment processing
   - Webhook handling
   - Refund support

5. **SendGrid** (Week 9)
   - Email sending
   - Template support

6. **Twilio** (Week 10)
   - SMS sending
   - WhatsApp messaging

**Each Integration Includes:**
- Plugin with API client
- OAuth flow (if required)
- Field mapping templates
- Automation templates
- Documentation
- Test suite


### Phase 4: Custom Integration Builder (4-6 weeks)

**Priority:** Medium

**Features:**
1. Visual API endpoint builder
2. Authentication configuration UI
3. Field mapping drag-and-drop
4. Request/response testing
5. Custom header support
6. GraphQL query builder
7. SOAP request builder
8. Data transformation functions

**User Flow:**
1. Click "Create Custom Integration"
2. Enter API base URL
3. Select authentication method
4. Define endpoints (GET/POST/PUT/DELETE)
5. Map ERP fields to API fields
6. Test connection
7. Save and activate

### Phase 5: Plugin Marketplace (6-8 weeks)

**Priority:** Medium

**Features:**
1. Plugin submission portal
2. Code review system
3. Marketplace UI
4. Plugin ratings and reviews
5. Version management
6. Automatic updates
7. Revenue sharing (optional)
8. Developer documentation

**Marketplace Categories:**
- E-commerce
- Accounting
- Communication
- Marketing
- Custom Integrations
- Utilities


### Phase 6: Advanced Features (8-12 weeks)

**Priority:** Low

**Features:**
1. AI-powered field mapping suggestions
2. Workflow templates library
3. Multi-step approval workflows
4. Conditional workflow branching
5. Parallel execution
6. Workflow versioning
7. A/B testing for automations
8. Performance analytics
9. Cost tracking per integration
10. SLA monitoring

---

## Technical Specifications

### New Database Tables Required

#### 1. automation_workflows
```sql
CREATE TABLE automation_workflows (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    trigger_type VARCHAR(50) NOT NULL,
    trigger_config JSON NOT NULL,
    nodes JSON NOT NULL,
    connections JSON NOT NULL,
    is_active BOOLEAN DEFAULT true,
    execution_count INT DEFAULT 0,
    last_executed_at TIMESTAMP NULL,
    created_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```


#### 2. automation_executions
```sql
CREATE TABLE automation_executions (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    workflow_id BIGINT NOT NULL,
    trigger_data JSON,
    status VARCHAR(20) NOT NULL, -- pending, running, completed, failed
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    error_message TEXT NULL,
    execution_log JSON,
    created_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (workflow_id) REFERENCES automation_workflows(id),
    INDEX idx_workflow_status (workflow_id, status),
    INDEX idx_company_created (company_id, created_at)
);
```

#### 3. integration_api_clients
```sql
CREATE TABLE integration_api_clients (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    integration_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) NOT NULL,
    auth_type VARCHAR(50) NOT NULL,
    auth_config JSON NOT NULL,
    headers JSON,
    endpoints JSON NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (integration_id) REFERENCES integrations(id)
);
```


### Core Services to Build

#### 1. AutomationEngine
```php
class AutomationEngine
{
    public function execute(AutomationWorkflow $workflow, array $triggerData): AutomationExecution
    {
        // 1. Create execution record
        $execution = AutomationExecution::create([...]);
        
        // 2. Parse workflow nodes
        $nodes = $workflow->nodes;
        
        // 3. Execute nodes in order
        foreach ($nodes as $node) {
            $result = $this->executeNode($node, $triggerData);
            
            // 4. Handle errors
            if ($result->failed()) {
                $execution->markAsFailed($result->error);
                return $execution;
            }
            
            // 5. Pass data to next node
            $triggerData = array_merge($triggerData, $result->data);
        }
        
        // 6. Mark as completed
        $execution->markAsCompleted();
        return $execution;
    }
    
    private function executeNode(array $node, array $data): NodeResult
    {
        return match($node['type']) {
            'condition' => $this->executeCondition($node, $data),
            'integration_action' => $this->executeIntegrationAction($node, $data),
            'workflow_action' => $this->executeWorkflowAction($node, $data),
            'http_request' => $this->executeHttpRequest($node, $data),
            'transform' => $this->executeTransform($node, $data),
            'plugin' => $this->executePlugin($node, $data),
            default => throw new \Exception("Unknown node type: {$node['type']}")
        };
    }
}
```


#### 2. IntegrationClient (Generic HTTP Client)
```php
class IntegrationClient
{
    public function __construct(
        private string $baseUrl,
        private AuthStrategy $auth,
        private array $headers = []
    ) {}
    
    public function get(string $endpoint, array $params = []): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->get($this->baseUrl . $endpoint, $params);
    }
    
    public function post(string $endpoint, array $data): Response
    {
        return Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl . $endpoint, $data);
    }
    
    private function getHeaders(): array
    {
        return array_merge(
            $this->headers,
            $this->auth->getAuthHeaders()
        );
    }
}

// Auth strategies
interface AuthStrategy
{
    public function getAuthHeaders(): array;
}

class ApiKeyAuth implements AuthStrategy
{
    public function __construct(private string $key, private string $header = 'X-API-Key') {}
    
    public function getAuthHeaders(): array
    {
        return [$this->header => $this->key];
    }
}

class OAuth2Auth implements AuthStrategy
{
    public function getAuthHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->getAccessToken()];
    }
}
```


#### 3. FieldMapper (Data Transformation)
```php
class FieldMapper
{
    public function map(array $data, array $mapping): array
    {
        $result = [];
        
        foreach ($mapping as $targetField => $sourceField) {
            // Support dot notation: customer.name
            $value = data_get($data, $sourceField);
            
            // Support transformations: {{field | uppercase}}
            if (is_string($sourceField) && str_contains($sourceField, '|')) {
                $value = $this->applyTransformation($value, $sourceField);
            }
            
            data_set($result, $targetField, $value);
        }
        
        return $result;
    }
    
    private function applyTransformation($value, string $expression): mixed
    {
        // Parse: {{field | uppercase | trim}}
        $parts = explode('|', $expression);
        $transformations = array_slice($parts, 1);
        
        foreach ($transformations as $transform) {
            $value = match(trim($transform)) {
                'uppercase' => strtoupper($value),
                'lowercase' => strtolower($value),
                'trim' => trim($value),
                'json_encode' => json_encode($value),
                'json_decode' => json_decode($value, true),
                default => $value
            };
        }
        
        return $value;
    }
}
```


### Frontend Components

#### 1. Workflow Canvas (Vue Component)
```vue
<template>
  <div class="workflow-canvas">
    <VueFlow
      v-model="nodes"
      v-model:edges="edges"
      @node-drag-stop="onNodeDragStop"
      @edge-update="onEdgeUpdate"
    >
      <template #node-trigger="{ data }">
        <TriggerNode :data="data" />
      </template>
      
      <template #node-action="{ data }">
        <ActionNode :data="data" />
      </template>
      
      <template #node-condition="{ data }">
        <ConditionNode :data="data" />
      </template>
    </VueFlow>
    
    <NodePalette @add-node="addNode" />
    <WorkflowToolbar @save="saveWorkflow" @test="testWorkflow" />
  </div>
</template>

<script setup>
import { VueFlow } from '@vue-flow/core'
import { ref } from 'vue'

const nodes = ref([])
const edges = ref([])

const addNode = (nodeType) => {
  nodes.value.push({
    id: `node_${Date.now()}`,
    type: nodeType,
    position: { x: 100, y: 100 },
    data: {}
  })
}

const saveWorkflow = async () => {
  await axios.post('/api/v1/automation/workflows', {
    nodes: nodes.value,
    edges: edges.value
  })
}
</script>
```


---

## Workflow Domain as Automation Builder

### Current State Analysis

**Question:** Is the Workflow domain a true automation builder?

**Answer:** Partially. The Workflow domain provides:

✅ **What it HAS:**
- State machine for document approval flows
- Role-based transitions
- Auto-actions on status changes
- Approval workflows
- History tracking

❌ **What it LACKS:**
- Visual designer UI
- Arbitrary event triggers (only document-based)
- External API integration
- Conditional branching beyond status
- Data transformation
- Loop/iteration support
- Error handling and retries

### Evolution Path

The Workflow domain should evolve into two separate but connected systems:

#### 1. Workflow Domain (Keep Current Focus)
**Purpose:** Document approval and state management

**Use Cases:**
- Sales order approval
- Purchase order approval
- Expense approval
- Invoice approval

**Features:**
- Status definitions
- Transitions with permissions
- Approval requests
- Auto-actions (limited)


#### 2. Automation Domain (New - To Be Built)
**Purpose:** General-purpose automation and integration orchestration

**Use Cases:**
- Sync products to WooCommerce when published
- Create invoice when order is shipped
- Send SMS when payment is received
- Update inventory from external warehouse
- Post to Facebook when product is added

**Features:**
- Visual workflow designer
- Any event trigger (not just documents)
- External API calls
- Complex conditional logic
- Data transformation
- Error handling
- Retry logic
- Parallel execution

### Integration Between Workflow and Automation

**Pattern 1: Workflow Triggers Automation**
```
Document Status Changed (Workflow)
    ↓
Trigger Automation (Automation Domain)
    ↓
Execute Integration Actions
```

**Pattern 2: Automation Starts Workflow**
```
External Event (Webhook)
    ↓
Create Document (Automation)
    ↓
Start Workflow (Workflow Domain)
```

**Pattern 3: Workflow Auto-Action Calls Automation**
```
Workflow Transition
    ↓
Auto-Action: trigger_automation
    ↓
Automation Executes
```


---

## Real-World Use Cases

### Use Case 1: E-commerce Order Sync

**Scenario:** Sync orders from WooCommerce to ERP with approval workflow

**Automation Flow:**
```
1. Trigger: WooCommerce Webhook (Order Created)
   ↓
2. Action: Create Customer (if not exists)
   ↓
3. Action: Create Sales Order in ERP
   ↓
4. Action: Start Approval Workflow
   ↓
5. Condition: Workflow Status = Approved?
   ├─ Yes → Sync back to WooCommerce (status = processing)
   └─ No → Send email to customer (order on hold)
```

**Benefits:**
- Automatic order import
- Manual approval for quality control
- Two-way sync with WooCommerce
- Customer notifications

### Use Case 2: Multi-Channel Inventory Sync

**Scenario:** Keep inventory synchronized across ERP, WooCommerce, and Shopify

**Automation Flow:**
```
1. Trigger: Product Stock Updated in ERP
   ↓
2. Parallel Execution:
   ├─ Action: Update WooCommerce Stock
   └─ Action: Update Shopify Stock
   ↓
3. Condition: Stock < Reorder Level?
   └─ Yes → Create Purchase Order
```

**Benefits:**
- Real-time inventory sync
- Prevent overselling
- Automatic reordering


### Use Case 3: Facebook Commerce Integration

**Scenario:** Automatically post new products to Facebook Shop and Page

**Automation Flow:**
```
1. Trigger: Product Created in ERP
   ↓
2. Condition: Product Category = "Featured"?
   └─ Yes:
       ├─ Action: Create Facebook Catalog Product
       ├─ Action: Post to Facebook Page
       └─ Action: Send to Facebook Shop
```

**Benefits:**
- Automatic product publishing
- Selective posting based on category
- Multi-channel presence

### Use Case 4: Custom Warehouse Integration

**Scenario:** Integrate with proprietary warehouse management system

**Steps:**
1. Create custom integration in Visual Builder
2. Define API endpoints:
   - `POST /api/shipments` - Create shipment
   - `GET /api/inventory` - Get stock levels
3. Create automation:
```
Trigger: Order Status = "Ready to Ship"
   ↓
Action: HTTP POST to Warehouse API
   Body: {
     "order_id": "{{order.number}}",
     "items": "{{order.items}}",
     "address": "{{order.shipping_address}}"
   }
   ↓
Action: Update Order Status to "Shipped"
   ↓
Action: Send Email to Customer with tracking
```

**Benefits:**
- No coding required
- Flexible field mapping
- Easy to modify


---

## Security Considerations

### 1. API Credential Storage

**Requirements:**
- Encrypt all API keys and secrets
- Use Laravel's encryption
- Store OAuth tokens securely
- Implement token refresh logic

**Implementation:**
```php
// In CompanyIntegration model
protected $casts = [
    'config' => 'encrypted:array',
];
```

### 2. Plugin Security

**Validation Rules:**
- No raw SQL queries
- No file system access (except temp)
- No shell commands
- Sandboxed execution
- Code review before marketplace approval

**Sandbox Implementation:**
```php
class PluginSandbox
{
    public function execute(string $code, array $data): mixed
    {
        // Disable dangerous functions
        ini_set('disable_functions', 'exec,shell_exec,system,passthru');
        
        // Execute in isolated scope
        return (function() use ($code, $data) {
            extract($data);
            return eval($code);
        })();
    }
}
```

### 3. Webhook Security

**Requirements:**
- Verify webhook signatures
- Rate limiting
- IP whitelisting (optional)
- HTTPS only

**Implementation:**
```php
public function handleWebhook(Request $request)
{
    // Verify signature
    $signature = $request->header('X-Webhook-Signature');
    $payload = $request->getContent();
    
    if (!$this->verifySignature($payload, $signature)) {
        abort(403, 'Invalid signature');
    }
    
    // Process webhook
}
```


### 4. Rate Limiting

**Per Integration:**
- Limit API calls per minute
- Queue requests if limit exceeded
- Retry with exponential backoff

**Implementation:**
```php
class RateLimitedClient
{
    public function request(string $method, string $url, array $data = [])
    {
        $key = "integration:{$this->integrationId}:requests";
        
        if (Cache::get($key, 0) >= $this->rateLimit) {
            // Queue for later
            dispatch(new RetryIntegrationRequest($method, $url, $data))
                ->delay(now()->addMinutes(1));
            
            throw new RateLimitExceededException();
        }
        
        Cache::increment($key);
        Cache::expire($key, 60); // 1 minute window
        
        return Http::$method($url, $data);
    }
}
```

---

## Performance Optimization

### 1. Queue-Based Execution

**All automations should run in background jobs:**

```php
// When trigger fires
dispatch(new ExecuteAutomationJob($workflow, $triggerData));

// Job class
class ExecuteAutomationJob implements ShouldQueue
{
    public function handle(AutomationEngine $engine)
    {
        $engine->execute($this->workflow, $this->triggerData);
    }
}
```

### 2. Caching

**Cache frequently accessed data:**
- Integration configurations
- Workflow definitions
- Plugin manifests
- API responses (when appropriate)


### 3. Database Optimization

**Indexes:**
```sql
-- automation_executions
CREATE INDEX idx_workflow_status ON automation_executions(workflow_id, status);
CREATE INDEX idx_company_created ON automation_executions(company_id, created_at);

-- company_integrations
CREATE INDEX idx_company_status ON company_integrations(company_id, status);

-- integration_logs
CREATE INDEX idx_integration_created ON integration_logs(integration_id, created_at);
```

**Partitioning:**
- Partition `automation_executions` by month
- Partition `integration_logs` by month
- Archive old data after 6 months
is
---

## Monitoring and Analytics

### 1. Execution Metrics

**Track:**
- Total executions per workflow
- Success/failure rate
- Average execution time
- Error frequency
- Most used integrations

**Dashboard:**
```
┌─────────────────────────────────────────────────────────┐
│  Automation Analytics                                    │
├─────────────────────────────────────────────────────────┤
│  Total Executions (30d):  12,543                        │
│  Success Rate:            98.2%                          │
│  Avg Execution Time:      2.3s                           │
│  Failed Executions:       226                            │
│                                                          │
│  Top Workflows:                                          │
│  1. WooCommerce Order Sync      (3,421 executions)      │
│  2. Inventory Update            (2,876 executions)      │
│  3. Customer Email              (1,932 executions)      │
└─────────────────────────────────────────────────────────┘
```


### 2. Error Tracking

**Implement:**
- Detailed error logs
- Error categorization
- Automatic retry for transient errors
- Alert notifications for critical failures

**Error Categories:**
- Authentication errors (401, 403)
- Rate limit errors (429)
- Server errors (500, 502, 503)
- Validation errors (400, 422)
- Network errors (timeout, connection refused)

---

## Testing Strategy

### 1. Unit Tests

**Test Coverage:**
- AutomationEngine node execution
- FieldMapper transformations
- IntegrationClient HTTP calls
- Auth strategies
- Data validation

### 2. Integration Tests

**Test Scenarios:**
- End-to-end workflow execution
- External API mocking
- Webhook handling
- Error scenarios
- Retry logic

### 3. User Acceptance Testing

**Test Cases:**
- Create automation via UI
- Configure integration
- Test workflow execution
- View execution logs
- Handle errors

---

## Documentation Requirements

### 1. User Documentation

**Topics:**
- Getting started guide
- Integration setup tutorials
- Workflow builder guide
- Field mapping guide
- Troubleshooting guide


### 2. Developer Documentation

**Topics:**
- Plugin development guide
- API reference
- Hook system documentation
- Custom integration guide
- Best practices

### 3. API Documentation

**Generate with Swagger/OpenAPI:**
- All automation endpoints
- Integration endpoints
- Webhook endpoints
- Authentication flows

---

## Success Metrics

### Key Performance Indicators (KPIs)

1. **Adoption Metrics:**
   - Number of active automations per company
   - Number of integrations installed
   - Number of plugins in marketplace

2. **Usage Metrics:**
   - Daily automation executions
   - API calls per integration
   - Webhook events received

3. **Quality Metrics:**
   - Automation success rate (target: >95%)
   - Average execution time (target: <5s)
   - Error rate (target: <5%)

4. **Business Metrics:**
   - Time saved per automation
   - Manual tasks eliminated
   - User satisfaction score

---

## Conclusion

This integration platform architecture provides a comprehensive foundation for building a powerful, flexible, and user-friendly automation system. By combining the Integration, Workflow, and Plugin domains with a visual automation builder, users can:


✅ **Connect to any platform** (WooCommerce, Shopify, Facebook, custom APIs)  
✅ **Automate business processes** without writing code  
✅ **Extend functionality** via plugins  
✅ **Integrate internal systems** with external services  
✅ **Scale operations** with automated workflows  

### Next Steps

1. **Immediate (Week 1-2):**
   - Review and approve architecture
   - Set up development environment
   - Create database migrations for new tables

2. **Short-term (Month 1):**
   - Build AutomationEngine core
   - Implement basic node types
   - Create workflow canvas UI

3. **Medium-term (Month 2-3):**
   - Build first pre-built integration (WooCommerce)
   - Implement webhook system
   - Add error handling and retries

4. **Long-term (Month 4-6):**
   - Complete all pre-built integrations
   - Launch plugin marketplace
   - Build custom integration builder

---

**Document Version:** 1.0  
**Last Updated:** 2026-03-06  
**Status:** Architecture Design - Ready for Implementation  
**Next Review:** After Phase 2 completion

---

## Appendix A: Glossary

- **Automation:** A workflow that executes automatically based on triggers
- **Integration:** A connection to an external service or API
- **Plugin:** Custom code that extends system functionality
- **Workflow:** A series of steps that define a business process
- **Node:** A single step in an automation workflow
- **Trigger:** An event that starts an automation
- **Action:** An operation performed by an automation
- **Hook:** An extension point where plugins can inject code
- **Manifest:** A JSON file that describes a plugin's capabilities


## Appendix B: Comparison with Existing Tools

### vs. Zapier
**Similarities:**
- Visual workflow builder
- Pre-built integrations
- Trigger-action model

**Advantages:**
- Deeply integrated with ERP data
- No per-execution pricing
- Custom integrations without limits
- Full control over data

### vs. n8n
**Similarities:**
- Node-based workflow design
- Self-hosted option
- Extensible via custom nodes

**Advantages:**
- Built specifically for ERP workflows
- Native access to business data
- Integrated with approval workflows
- Multi-tenancy built-in

### vs. Make.com (Integromat)
**Similarities:**
- Visual scenario builder
- Complex branching logic
- Data transformation

**Advantages:**
- No external service dependency
- Unlimited executions
- Direct database access
- Custom business logic

---

## Appendix C: Technology Stack

**Backend:**
- Laravel 11 (PHP 8.3)
- MySQL 8.0
- Redis (caching & queues)
- Laravel Horizon (queue monitoring)

**Frontend:**
- Vue 3 (Composition API)
- Inertia.js
- Vue Flow (workflow canvas)
- Tailwind CSS

**Infrastructure:**
- Docker (development)
- Laravel Forge (deployment)
- AWS S3 (file storage)
- Cloudflare (CDN)

---

**End of Document**
