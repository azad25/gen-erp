# Document Domain Analysis

## Overview
The Document domain provides comprehensive document management capabilities including file upload/download, folder organization, custom form builder, and PDF generation services. This analysis covers the current implementation, integration points, and recommendations for future development.

## Current Implementation

### Backend Implementation
**Status:** ✅ WELL-IMPLEMENTED - Core functionality complete

**Models:**
- `Document.php` - Main document entity with file metadata, storage path, and polymorphic relationships
- `DocumentFolder.php` - Hierarchical folder structure for organizing documents
- `Form.php` - Custom forms for data collection with public/private access
- `FormField.php` - Form field definitions with validation rules
- `FormSubmission.php` - Form submission records

**Services:**
- `DocumentService.php` - Upload, download, delete, move, storage quota enforcement
- `FormService.php` - Form CRUD, field management, submission handling
- `CustomFieldManagementService.php` - Custom field definitions and management
- `InvoicePDFService.php` - Invoice PDF generation
- `POSReceiptService.php` - POS receipt PDF generation
- `PayslipPDFService.php` - Employee payslip PDF generation
- `PurchaseOrderPDFService.php` - Purchase order PDF generation

**Routes:**
```php
// Document Management
GET  /documents/dashboard - Documents Dashboard
GET  /documents - All Documents
GET  /documents/folders - Folder Management
GET  /documents/recent - Recent Documents
GET  /documents/forms - Forms Management
GET  /documents/custom-fields - Custom Fields

// Document Access (Signed URLs)
GET  /documents/{document}/download - Download document
GET  /documents/{document}/thumbnail - Get thumbnail
GET  /documents/{document}/preview - Preview document

// Public Forms
GET  /forms/{slug} - Public form view
POST /forms/{slug}/submit - Submit public form
```

**Key Features:**
- ✅ File upload with MIME type validation
- ✅ Storage quota enforcement (50MB free, 1GB pro, 5GB enterprise)
- ✅ EXIF stripping from images
- ✅ Hierarchical folder structure
- ✅ Document preview (images and PDFs)
- ✅ Polymorphic documentable relationships
- ✅ Soft delete support
- ✅ Company-scoped multi-tenancy
- ✅ Form builder with custom fields
- ✅ Public form submissions
- ✅ PDF generation for invoices, receipts, payslips, POs

### Frontend Implementation
**Status:** ✅ COMPLETE - All pages implemented

**Pages Created:**
- `resources/js/Pages/Documents/Dashboard.vue` - Dashboard with stats and quick actions
- `resources/js/Pages/Documents/Index.vue` - All documents with upload, search, filters
- `resources/js/Pages/Documents/Folders.vue` - Folder management UI
- `resources/js/Pages/Documents/Recent.vue` - Recent documents list
- `resources/js/Pages/Documents/Forms/Index.vue` - Forms management
- `resources/js/Pages/Documents/CustomFields/Index.vue` - Custom field definitions

**Layout:** Uses `SidebarProvider` and `AppLayout` (NOT AdminLayout like Reports)

**Features Implemented:**
- ✅ File upload with drag & drop
- ✅ Storage usage indicator
- ✅ Folder creation and management
- ✅ Document search and filtering
- ✅ Thumbnail generation
- ✅ Document preview
- ✅ Download functionality
- ✅ Recent documents tracking
- ✅ Form builder UI
- ✅ Custom field management

### Sidebar Menu Integration
**Status:** ✅ FULLY INTEGRATED

**Location:** `resources/js/Components/Layout/AppSidebar.vue`

**Menu Structure:**
```javascript
{
  key: "documents",
  title: $t('sidebar.documents.title'),
  icon: FolderIcon,
  items: [
    {
      icon: FolderIcon,
      title: $t('sidebar.documents.all'),
      href: "/documents",
      routeName: "documents.index",
    },
    {
      icon: FolderIcon,
      title: $t('sidebar.documents.folders'),
      href: "/documents/folders",
      routeName: "documents.folders",
    },
    {
      icon: DocsIcon,
      title: $t('sidebar.documents.recent'),
      href: "/documents/recent",
      routeName: "documents.recent",
    },
    {
      icon: DocsIcon,
      title: $t('sidebar.documents.forms'),
      href: "/documents/forms",
      routeName: "documents.forms.index",
    },
    {
      icon: SettingsIcon,
      title: $t('sidebar.documents.custom_fields'),
      href: "/documents/custom-fields",
      routeName: "documents.custom-fields.index",
    },
  ],
}
```

**Translations:**
- English: `lang/en/sidebar.php` (lines 115-121)
- English: `lang/en/documents.php` (comprehensive translations)
- Bengali: Need to verify if exists

## Integration with Other Domains

### Sales Domain
**Integration Points:**
- Invoice PDF generation via `InvoicePDFService`
- Documents attached to invoices via polymorphic `documentable` relationship

**Data Flow:**
```
Sales (Invoices) → InvoicePDFService → Document (stored as PDF)
```

### POS Domain
**Integration Points:**
- POS receipt PDF generation via `POSReceiptService`
- Receipts stored as documents for audit trail

**Data Flow:**
```
POS (Sales) → POSReceiptService → Document (receipt PDF)
```

### HR Domain
**Integration Points:**
- Employee payslip PDF generation via `PayslipPDFService`
- Payslips stored as documents for employee access

**Data Flow:**
```
HR (Payroll) → PayslipPDFService → Document (payslip PDF)
```

### Purchase Domain
**Integration Points:**
- Purchase order PDF generation via `PurchaseOrderPDFService`
- POs stored as documents for tracking

**Data Flow:**
```
Purchase (Orders) → PurchaseOrderPDFService → Document (PO PDF)
```

### Inventory Domain
**Integration Points:**
- Product images stored as documents
- Product specifications as documents

**Data Flow:**
```
Inventory (Products) → DocumentService → Document (product images)
```

## What's Missing

### Critical Features (Required for Complete Functionality)
1. **Document Sharing**
   - Share documents with other users
   - Public document links with expiration
   - Permission-based access control

2. **Document Versioning**
   - Track document versions
   - Version history comparison
   - Rollback to previous versions

3. **Advanced Search**
   - Full-text search within documents
   - Metadata-based search
   - Saved search queries

4. **Document Collaboration**
   - Comments on documents
   - Document annotations
   - Real-time collaboration

### Important Features (Enhanced Functionality)
5. **Document Workflows**
   - Approval workflows
   - Review processes
   - Status tracking

6. **Document Templates**
   - Template management
   - Template-based document creation
   - Variable substitution

7. **Bulk Operations**
   - Bulk upload
   - Bulk download
   - Bulk move/delete

8. **Document Analytics**
   - Usage tracking
   - Popular documents
   - Storage analytics

### Nice-to-Have Features
9. **Document OCR**
   - Text extraction from images
   - Searchable scanned documents
   - Handwriting recognition

10. **Document Conversion**
    - Convert between formats
    - PDF generation from Office docs
    - Image format conversion

11. **Document Backup**
    - Automated backups
    - Disaster recovery
    - Archive management

12. **Document Signing**
    - Digital signatures
    - Electronic signatures
    - Signature workflows

## Recommended Implementation Plan

### Phase 1: Document Sharing & Versioning (3-4 weeks)
**Week 1-2: Sharing Infrastructure**
- Create `DocumentShare` model
- Implement share link generation
- Add permission checks
- Create sharing UI

**Week 3-4: Versioning System**
- Create `DocumentVersion` model
- Implement version tracking
- Add version comparison UI
- Implement rollback functionality

### Phase 2: Advanced Search & Collaboration (3-4 weeks)
**Week 5-6: Advanced Search**
- Implement full-text search (Elasticsearch/Meilisearch)
- Add metadata search
- Create saved search feature

**Week 7-8: Collaboration Features**
- Add comments system
- Implement annotations
- Create notification system

### Phase 3: Workflows & Templates (3-4 weeks)
**Week 9-10: Workflow Engine**
- Create workflow models
- Implement approval processes
- Add status tracking

**Week 11-12: Template System**
- Create template models
- Implement template editor
- Add variable substitution

### Phase 4: Bulk Operations & Analytics (2-3 weeks)
**Week 13-14: Bulk Operations**
- Implement bulk upload
- Add bulk download
- Create bulk management UI

**Week 15: Analytics Dashboard**
- Usage tracking
- Storage analytics
- Popular documents

### Phase 5: OCR, Conversion & Signing (4-5 weeks)
**Week 16-17: OCR & Conversion**
- Implement OCR (Tesseract)
- Add document conversion
- Create conversion UI

**Week 18-20: Digital Signatures**
- Implement signature capture
- Add signature validation
- Create signing workflows

### Phase 6: Polish & Optimization (2 weeks)
**Week 21: Performance Optimization**
- Optimize file storage
- Implement CDN for downloads
- Add caching layer

**Week 22: Testing & Documentation**
- Unit tests
- Integration tests
- API documentation

## Technical Recommendations

### Database Schema
```sql
-- Document Sharing
CREATE TABLE document_shares (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  document_id BIGINT NOT NULL,
  shared_by BIGINT NOT NULL,
  shared_with BIGINT,
  share_token VARCHAR(64),
  expires_at TIMESTAMP,
  permissions JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (document_id) REFERENCES documents(id),
  FOREIGN KEY (shared_by) REFERENCES users(id),
  FOREIGN KEY (shared_with) REFERENCES users(id)
);

-- Document Versions
CREATE TABLE document_versions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  document_id BIGINT NOT NULL,
  version_number INT NOT NULL,
  disk_path VARCHAR(512) NOT NULL,
  size_bytes BIGINT NOT NULL,
  uploaded_by BIGINT NOT NULL,
  uploaded_at TIMESTAMP,
  created_at TIMESTAMP,
  FOREIGN KEY (document_id) REFERENCES documents(id),
  FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Document Workflows
CREATE TABLE document_workflows (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  company_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  config JSON,
  created_by BIGINT NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- Document Workflow Steps
CREATE TABLE document_workflow_steps (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  workflow_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  order_index INT NOT NULL,
  assignee_type VARCHAR(50),
  assignee_id BIGINT,
  conditions JSON,
  created_at TIMESTAMP,
  FOREIGN KEY (workflow_id) REFERENCES document_workflows(id)
);

-- Document Workflow Instances
CREATE TABLE document_workflow_instances (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  document_id BIGINT NOT NULL,
  workflow_id BIGINT NOT NULL,
  current_step_id BIGINT,
  status VARCHAR(50),
  started_by BIGINT NOT NULL,
  started_at TIMESTAMP,
  completed_at TIMESTAMP,
  FOREIGN KEY (document_id) REFERENCES documents(id),
  FOREIGN KEY (workflow_id) REFERENCES document_workflows(id),
  FOREIGN KEY (current_step_id) REFERENCES document_workflow_steps(id)
);
```

### API Endpoints
```
// Document Management
GET  /api/v1/documents - List documents with filters
POST /api/v1/documents - Upload document
GET  /api/v1/documents/{id} - Get document details
PUT  /api/v1/documents/{id} - Update document
DELETE /api/v1/documents/{id} - Delete document
POST /api/v1/documents/{id}/move - Move to folder

// Document Sharing
POST /api/v1/documents/{id}/share - Create share link
GET  /api/v1/documents/{id}/shares - List shares
DELETE /api/v1/documents/{id}/shares/{shareId} - Revoke share

// Document Versions
GET  /api/v1/documents/{id}/versions - List versions
POST /api/v1/documents/{id}/versions/{versionId}/restore - Restore version

// Document Search
GET  /api/v1/documents/search?q={query} - Full-text search
POST /api/v1/documents/search/advanced - Advanced search
GET  /api/v1/documents/search/saved - Saved searches

// Document Workflows
GET  /api/v1/documents/{id}/workflows - Get workflow status
POST /api/v1/documents/{id}/workflows/submit - Submit for approval
POST /api/v1/documents/{id}/workflows/approve - Approve
POST /api/v1/documents/{id}/workflows/reject - Reject
```

### Libraries to Consider
- **Full-Text Search:** Laravel Scout with Meilisearch or Elasticsearch
- **OCR:** Tesseract PHP
- **Document Conversion:** LibreOffice headless, ImageMagick
- **Digital Signatures:** TCPDF with signature support, DocuSign API
- **File Storage:** Laravel Storage (S3, Wasabi, Backblaze B2)
- **Real-time:** Laravel Echo, Pusher
- **File Upload:** Dropzone.js, Uppy
- **Document Preview:** PDF.js, ViewerJS

## Summary

**Current Status:** ✅ WELL-IMPLEMENTED - Core functionality complete

**Completion:** ~70% (Backend 80%, Frontend 90%)

**Priority:** MEDIUM - Document domain is functional but missing advanced features

**Recommendation:** Focus on Phase 1 (Document Sharing & Versioning) to enhance collaboration capabilities. The current implementation provides solid document management but lacks sharing and versioning which are critical for enterprise use.

**Estimated Total Time:** 22 weeks for full implementation

**Quick Win:** Implement document sharing with public links (1-2 weeks) to immediately improve collaboration capabilities.
