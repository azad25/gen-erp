# Custom Fields
### 🏗️ **Core Architecture**

#### **Models & Database Structure**
1. **CustomFieldDefinition** (`app/Domain/Shared/Models/CustomFieldDefinition.php`)
   - Defines custom field schemas for different entity types
   - Company-scoped with multi-tenant isolation
   - Supports 12 field types (text, textarea, number, decimal, boolean, date, datetime, select, multiselect, url, email, phone)
   - Features: required, filterable, searchable, show_in_list, validation rules, display order

2. **CustomFieldValue** (`app/Domain/Shared/Models/CustomFieldValue.php`)
   - Stores actual field values using EAV (Entity-Attribute-Value) pattern
   - Multiple typed columns: `value_text`, `value_number`, `value_boolean`, `value_date`, `value_json`
   - Company-scoped with entity_type + entity_id polymorphic relationship

#### **Field Types** (`app/Support/Enums/CustomFieldType.php`)
- **Text Types**: TEXT, TEXTAREA, URL, EMAIL, PHONE
- **Numeric Types**: NUMBER, DECIMAL  
- **Boolean**: BOOLEAN
- **Date Types**: DATE, DATETIME
- **Selection Types**: SELECT, MULTISELECT
- Each type maps to specific storage columns and Filament form components

### 🔧 **Services & Business Logic**

#### **CustomFieldService** (`app/Domain/Shared/Services/CustomFieldService.php`)
- **Core Methods**:
  - `getDefinitions(string $entityType)` - Cached retrieval of field definitions
  - `getValues(string $entityType, int $entityId)` - Get all values for an entity
  - `saveValues(string $entityType, int $entityId, array $data)` - Upsert field values
  - `buildValidationRules(string $entityType)` - Generate Laravel validation rules

- **Features**:
  - Redis/Cache integration for performance
  - Automatic type casting and validation
  - Multi-tenant company isolation

### 🔗 **Model Integration**

#### **HasCustomFields Trait** (`app/Domain/Auth/Models/Concerns/HasCustomFields.php`)
**Integrated Models**:
- ✅ **Product** - `customFieldEntityType(): 'product'`
- ✅ **Customer** - `customFieldEntityType(): 'customer'`  
- ✅ **Supplier** - `customFieldEntityType(): 'supplier'`
- ✅ **Invoice** - `customFieldEntityType(): 'invoice'`
- ✅ **SalesOrder** - `customFieldEntityType(): 'sales_order'`
- ✅ **PurchaseOrder** - `customFieldEntityType(): 'purchase_order'`
- ✅ **Employee** - `customFieldEntityType(): 'employee'`

**Trait Methods**:
- `getCustomField(string $key)` - Retrieve field value
- `setCustomField(string $key, mixed $value)` - Set field value (in-memory)
- `saveCustomFields()` - Persist pending changes
- `loadCustomFields()` - Bulk load to prevent N+1 queries

### 🚀 **Background Processing**

#### **FilterableCustomFieldJob** (`app/Jobs/FilterableCustomFieldJob.php`)
- **Purpose**: Creates MySQL generated columns + indexes for filterable custom fields
- **Process**: 
  1. Generates virtual column from JSON path: `JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.field_key'))`
  2. Creates database index for performance
  3. Updates definition with `generated_column_name`
- **Requirements**: Entity tables must have `custom_fields` JSON column

#### **CustomFieldDefinitionObserver** (`app/Observers/CustomFieldDefinitionObserver.php`)
- **Cache Invalidation**: Clears Redis cache when definitions change
- **Cache Key Pattern**: `custom_fields:{company_id}:{entity_type}`

### 🌐 **API Integration**

#### **REST API** (`app/Http/Controllers/Api/V1/CustomFieldController.php`)
- **Endpoints**:
  - `GET /api/v1/custom-fields` - List with filtering by entity_type
  - `GET /api/v1/custom-fields/{id}` - Show specific definition
  - `POST /api/v1/custom-fields` - Create new definition
  - `PUT /api/v1/custom-fields/{id}` - Update definition
  - `DELETE /api/v1/custom-fields/{id}` - Delete definition

- **Features**:
  - OpenAPI/Swagger documentation
  - Pagination support
  - Search functionality
  - Company-scoped access

#### **SystemService Integration** (`app/Domain/System/Services/SystemService.php`)
- Provides CRUD operations for custom field definitions
- Used by API controllers and admin interfaces

### 🔄 **Usage Patterns**

#### **In SalesOrder Service** (Example Integration)
```php
public function createOrder(Company $company, array $data, array $items, array $customFields = []): SalesOrder
{
    return DB::transaction(function () use ($company, $data, $items, $customFields): SalesOrder {
        // ... create order logic
        
        foreach ($customFields as $key => $value) {
            $order->setCustomField($key, $value);
        }
        
        return $order;
    });
}
```

### 🧪 **Testing Coverage**

#### **Feature Tests** (`tests/Feature/CustomFieldTest.php`)
- ✅ Definition creation and retrieval
- ✅ Value storage and retrieval  
- ✅ Multi-tenant isolation
- ✅ Validation rule generation
- ✅ Background job dispatching
- ✅ Cache invalidation

### 🚫 **Missing Integrations**

#### **Frontend Components**
- ❌ No Vue.js components found for custom field rendering
- ❌ No form builders or dynamic field generators
- ❌ No admin UI for managing custom field definitions

#### **Advanced Features**
- ❌ No conditional field logic (show/hide based on other fields)
- ❌ No field dependencies or cascading dropdowns
- ❌ No bulk import/export of custom field data
- ❌ No field history/audit trail
- ❌ No field templates or presets

#### **Integration Gaps**
- ❌ No webhook triggers for custom field changes
- ❌ No search engine integration for custom field values
- ❌ No reporting/analytics on custom field usage
- ❌ No API endpoints for bulk field value operations

### 📊 **Performance Considerations**

#### **Optimizations**
- ✅ Redis caching for field definitions
- ✅ Generated columns for filterable fields
- ✅ Database indexes on common query patterns
- ✅ Bulk loading to prevent N+1 queries

#### **Potential Issues**
- ⚠️ EAV pattern can be slow for complex queries
- ⚠️ JSON column queries may not scale well
- ⚠️ No pagination for field values (could be memory intensive)

### 🔮 **Recommended Enhancements**

1. **Frontend Integration**: Build Vue.js components for dynamic form rendering
2. **Admin Interface**: Create management UI for custom field definitions
3. **Advanced Validation**: Add conditional logic and field dependencies  
4. **Bulk Operations**: API endpoints for batch field value updates
5. **Search Integration**: Full-text search across custom field values
6. **Audit Trail**: Track changes to custom field values
7. **Import/Export**: CSV/Excel support for custom field data
8. **Performance**: Consider denormalization for frequently queried fields

The custom field system is well-architected with solid foundations but lacks frontend integration and advanced features that would make it production-ready for end users.