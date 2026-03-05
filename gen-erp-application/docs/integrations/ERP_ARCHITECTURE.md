# Integration Domain Features

**Core Purpose:** Connect the ERP with external services and manage third-party integrations.

## Key Features:

1. **Integration Catalog** - Browse and manage available integrations (e-commerce, accounting, IoT, marketing, etc.)

2. **Install/Uninstall** - Add or remove integrations for your company

3. **Configuration** - Set up API keys, endpoints, and field mappings via JSON config

4. **Activate/Deactivate** - Enable or pause integrations without uninstalling

5. **Sync Management** - Trigger manual syncs or schedule automatic data synchronization

6. **Status Tracking** - Monitor integration health (active/paused/error) and last sync time

7. **Multi-Tier Support** - Native (built-in), Connector (app-to-app), Plugin (third-party)

8. **Plan Eligibility** - Control which integrations are available based on subscription tier (free/pro/enterprise)

9. **Multi-Tenancy** - Each company manages their own integrations independently

10. **Error Logging** - Track sync failures and integration issues

**Categories Supported:** E-commerce, Accounting, Finance, Communication, IoT/Hardware, Marketing, Google Workspace, Social Media, Custom
The Integration domain provides the **framework and infrastructure** for connecting to external APIs like WooCommerce, Shopify, and Facebook, but the actual API connectors are **not yet implemented**.

## Current Status:

**✅ What's Built:**
- Database structure for storing integration configs
- UI to browse, install, and configure integrations
- Configuration storage (API keys, endpoints, credentials)
- Sync scheduling and status tracking
- Event system for integration lifecycle
- Multi-tenancy support

**❌ What's Missing:**
- Actual API client implementations for WooCommerce, Shopify, Facebook, etc.
- Data transformation logic (mapping ERP products to WooCommerce products)
- Webhook handlers for real-time updates
- OAuth flows for authentication
- Specific sync logic for each platform

## To Connect to WooCommerce/Shopify/Facebook:

You would need to create:

1. **Service Classes** - e.g., `WooCommerceService.php`, `ShopifyService.php`
2. **API Clients** - HTTP clients with authentication
3. **Data Mappers** - Transform ERP data ↔ External platform data
4. **Webhook Handlers** - Listen for events from external platforms
5. **Sync Jobs** - Queue jobs to push/pull data

The Integration domain provides the **management layer** (install, configure, schedule), but the **actual integration logic** needs to be built per platform.

**Think of it as:** The domain built the garage, but you still need to build the cars (individual integrations).
Yes! A visual integration builder would be an excellent addition. The current Integration domain already has the foundation to support this. Here's what you could build:

## Visual Integration Builder Concept

**Think:** Zapier/Make.com/n8n style workflow builder for your ERP

### What You Could Build:

**1. Workflow Canvas**
- Drag-and-drop nodes (triggers, actions, conditions)
- Visual connections between steps
- Real-time validation

**2. Node Types:**
- **Triggers:** "When new order created", "When product updated", "On schedule"
- **Actions:** "Create WooCommerce product", "Send to Shopify", "Post to Facebook"
- **Transformers:** "Map fields", "Filter data", "Format values"
- **Conditions:** "If price > 100", "If stock < 10"

**3. Field Mapping UI:**
- Visual mapper: ERP fields → External API fields
- Drag ERP field to API field
- Data preview and testing

**4. Template Library:**
- Pre-built workflows: "Sync products to WooCommerce"
- One-click install and customize

### How It Would Work with Current System:

The existing `config` and `field_maps` JSON fields in `company_integrations` table can store the workflow definition:

```json
{
  "workflow": {
    "nodes": [
      {"id": "trigger1", "type": "order_created"},
      {"id": "map1", "type": "field_mapper", "mappings": {...}},
      {"id": "action1", "type": "woocommerce_create_order"}
    ],
    "connections": [...]
  }
}
```

### Implementation Approach:

**Frontend:**
- Use a library like **React Flow** or **Vue Flow** for the canvas
- Build custom node components for each integration type
- Store workflow as JSON

**Backend:**
- Create a `WorkflowEngine` service to execute workflows
- Parse JSON workflow definition
- Execute nodes in sequence
- Handle errors and retries

### Benefits:
- Non-technical users can build integrations
- Reusable workflow templates
- Easy to test and debug
- No code changes needed for new integrations

**Would you like me to create a proof-of-concept for the visual builder?** I can build the basic canvas UI and workflow execution engine.
Let me explore the Workflow and Plugin domains to see how they can work with the Integration domain.

Perfect! Now I can see how these three domains can work together. Let me create a comprehensive explanation:

# Integration + Workflow + Plugin Domain Synergy

## How They Work Together:

### 1. **Integration Domain** (Foundation)
- Manages external service connections (WooCommerce, Shopify, Facebook)
- Stores API credentials and configuration
- Handles sync scheduling and status tracking

### 2. **Workflow Domain** (Business Process Automation)
- Defines approval flows for documents (orders, invoices, expenses)
- Manages state transitions with role-based permissions
- Triggers auto-actions on status changes

### 3. **Plugin Domain** (Extensibility Layer)
- Allows custom code to hook into system events
- Enables third-party developers to extend functionality
- Provides a marketplace for pre-built integrations

---

## Integration Patterns:

### Pattern 1: **Workflow-Triggered Integration Sync**

**Use Case:** When a sales order is approved, automatically sync it to WooCommerce

```php
// In WorkflowTransition auto_actions:
{
  "type": "trigger_integration_sync",
  "integration_slug": "woocommerce",
  "action": "sync_order",
  "document_type": "sales_order"
}
```

**How it works:**
1. Sales order moves to "Approved" status via Workflow
2. Workflow auto_action triggers Integration sync
3. Integration domain pushes order to WooCommerce API

---

### Pattern 2: **Plugin-Based Integration Connector**

**Use Case:** Install a WooCommerce plugin that provides the actual API integration logic

```php
// WooCommerce Plugin Manifest:
{
  "name": "WooCommerce Connector",
  "slug": "woocommerce-connector",
  "hooks": {
    "integration.sync.woocommerce": "WooCommercePlugin@syncOrder",
    "integration.webhook.woocommerce": "WooCommercePlugin@handleWebhook"
  }
}
```

**How it works:**
1. Install WooCommerce plugin from marketplace
2. Plugin registers hooks for integration events
3. Integration domain fires events → Plugin handles API calls
4. Plugin can be enabled/disabled without code changes

---

### Pattern 3: **Integration-Triggered Workflow**

**Use Case:** When a new order comes from Shopify webhook, start an approval workflow

```php
// In CompanyIntegration webhook handler:
public function handleWebhook($payload) {
    // Create sales order from Shopify data
    $order = SalesOrder::create([...]);
    
    // Start workflow for approval
    app(WorkflowService::class)->initialise('sales_order', $order->id);
}
```

**How it works:**
1. Shopify sends webhook to Integration domain
2. Integration creates sales order in ERP
3. Workflow automatically starts for the new order
4. Managers approve/reject via Workflow UI

---

## Practical Implementation:

### Step 1: Create Integration Record
```php
// Admin creates WooCommerce integration in catalog
Integration::create([
    'slug' => 'woocommerce',
    'name' => 'WooCommerce',
    'category' => 'ecommerce',
    'tier' => 'connector',
]);
```

### Step 2: Install Plugin for Logic
```php
// Company installs WooCommerce plugin
$plugin = app(PluginManager::class)->install($companyId, [
    'name' => 'WooCommerce Connector',
    'slug' => 'woocommerce-connector',
    'hooks' => [
        'integration.sync' => 'WooCommercePlugin@sync',
    ],
]);
```

### Step 3: Configure Integration
```php
// Company configures their WooCommerce credentials
CompanyIntegration::create([
    'company_id' => $companyId,
    'integration_id' => $integration->id,
    'config' => [
        'api_url' => 'https://mystore.com',
        'api_key' => 'ck_xxx',
        'api_secret' => 'cs_xxx',
    ],
]);
```

### Step 4: Link to Workflow
```php
// Create workflow with integration auto-action
WorkflowTransition::create([
    'from_status_key' => 'approved',
    'to_status_key' => 'synced',
    'auto_actions' => [
        [
            'type' => 'trigger_integration',
            'integration_slug' => 'woocommerce',
            'action' => 'sync_order',
        ],
    ],
]);
```

---

## Visual Builder Integration:

You can build a **no-code integration builder** that combines all three:

**UI Components:**
1. **Integration Canvas** - Drag-and-drop workflow + integration nodes
2. **Trigger Node** - "When order status = Approved"
3. **Action Node** - "Sync to WooCommerce"
4. **Condition Node** - "If total > $1000"
5. **Plugin Node** - "Run custom plugin logic"

**Stored as JSON:**
```json
{
  "workflow_id": 123,
  "nodes": [
    {"type": "trigger", "event": "workflow.status_changed", "status": "approved"},
    {"type": "condition", "field": "total", "operator": ">", "value": 1000},
    {"type": "integration", "slug": "woocommerce", "action": "sync_order"},
    {"type": "plugin", "slug": "custom-notifier", "method": "sendSlack"}
  ]
}
```

---

## Summary:

**Integration Domain** = Connection infrastructure  
**Workflow Domain** = Business process automation  
**Plugin Domain** = Custom extensibility  

**Together:** You get a powerful, flexible integration platform where:
- Workflows trigger integrations
- Integrations trigger workflows
- Plugins provide the actual API logic
- Everything is configurable without code changes

Want me to build a proof-of-concept that connects these three domains?