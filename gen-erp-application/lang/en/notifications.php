<?php

return [

    'actions' => [
        'view'         => 'View',
        'view_invoice' => 'View Invoice',
        'view_product' => 'View Product',
        'view_order'   => 'View Order',
        'view_leave'   => 'View Leave',
        'view_project' => 'View Project',
        'view_task'    => 'View Task',
        'view_shipment' => 'View Shipment',
        'view_lead'    => 'View Lead',
        'dismiss'      => 'Dismiss',
        'track'        => 'Track',
    ],

    'invoice' => [
        'paid' => [
            'title' => 'Invoice Paid',
            'body'  => 'Invoice #:number has been paid — :amount',
        ],
        'overdue' => [
            'title' => 'Invoice Overdue',
            'body'  => 'Invoice #:number is :days days overdue',
        ],
        'created' => [
            'title' => 'Invoice Created',
            'body'  => 'Invoice #:number created for :customer',
        ],
        'sent' => [
            'title' => 'Invoice Sent',
            'body'  => 'Invoice #:number sent to :customer',
        ],
    ],

    'inventory' => [
        'stock_low' => [
            'title' => 'Low Stock Alert',
            'body'  => ':product is running low — only :quantity left',
        ],
        'stock_out' => [
            'title' => 'Out of Stock',
            'body'  => ':product is now out of stock',
        ],
        'stock_received' => [
            'title' => 'Stock Received',
            'body'  => 'Received :quantity units of :product',
        ],
    ],

    'hr' => [
        'leave_applied' => [
            'title' => 'Leave Application',
            'body'  => ':employee applied for :days days of leave',
        ],
        'leave_approved' => [
            'title' => 'Leave Approved',
            'body'  => 'Your :days day leave request has been approved',
        ],
        'leave_rejected' => [
            'title' => 'Leave Rejected',
            'body'  => 'Your leave request has been rejected',
        ],
        'payroll_processed' => [
            'title' => 'Payroll Processed',
            'body'  => ':month payroll has been processed successfully',
        ],
    ],

    'crm' => [
        'lead_created' => [
            'title' => 'New Lead',
            'body'  => 'New lead received from :name',
        ],
        'lead_converted' => [
            'title' => 'Lead Converted',
            'body'  => ':name has been successfully converted to a customer',
        ],
        'lead_assigned' => [
            'title' => 'Lead Assigned',
            'body'  => 'You have been assigned a lead from :name',
        ],
        'opportunity_won' => [
            'title' => 'Opportunity Won',
            'body'  => 'Won opportunity from :name — :amount',
        ],
    ],

    'logistics' => [
        'shipment' => [
            'created' => [
                'title' => 'Shipment Created',
                'body'  => 'Tracking #:tracking_number created for :recipient_name',
            ],
            'status_updated' => [
                'title' => 'Shipment Update',
                'body'  => 'Tracking #:tracking_number is now :status at :location',
            ],
            'delivered' => [
                'title' => 'Delivered Successfully',
                'body'  => 'Shipment #:tracking_number has been delivered to :recipient_name',
            ],
            'failed' => [
                'title' => 'Delivery Failed',
                'body'  => 'Shipment #:tracking_number could not be delivered',
            ],
            'cancelled' => [
                'title' => 'Shipment Cancelled',
                'body'  => 'Shipment #:tracking_number has been cancelled',
            ],
        ],
        'return' => [
            'requested' => [
                'title' => 'Return Requested',
                'body'  => 'Return requested for shipment #:tracking_number - :reason',
            ],
            'approved' => [
                'title' => 'Return Approved',
                'body'  => 'Return for shipment #:tracking_number has been approved',
            ],
            'rejected' => [
                'title' => 'Return Rejected',
                'body'  => 'Return for shipment #:tracking_number has been rejected',
            ],
        ],
        'cod' => [
            'collected' => [
                'title' => 'COD Collected',
                'body'  => 'COD amount :amount collected for shipment #:tracking_number',
            ],
            'settled' => [
                'title' => 'COD Settled',
                'body'  => 'COD for shipment #:tracking_number has been settled with carrier',
            ],
        ],
    ],

    'purchasing' => [
        'po_approved' => [
            'title' => 'Purchase Order Approved',
            'body'  => 'Purchase order #:number has been approved',
        ],
        'po_received' => [
            'title' => 'Goods Received',
            'body'  => 'Goods received from :supplier',
        ],
    ],

    'projects' => [
        'task_assigned' => [
            'title' => 'Task Assigned',
            'body'  => 'You have been assigned ":task" in project :project',
        ],
        'deadline_near' => [
            'title' => 'Deadline Approaching',
            'body'  => '":task" is due in :days days',
        ],
        'project_completed' => [
            'title' => 'Project Completed',
            'body'  => '":project" has been completed successfully',
        ],
    ],

    'accounting' => [
        'payment_received' => [
            'title' => 'Payment Received',
            'body'  => 'Payment of :amount received',
        ],
        'expense_approved' => [
            'title' => 'Expense Approved',
            'body'  => 'Expense of :amount has been approved',
        ],
    ],

    'system' => [
        'alert' => [
            'title' => 'System Alert',
            'body'  => ':message',
        ],
        'job_completed' => [
            'title' => 'Task Complete',
            'body'  => ':job completed successfully',
        ],
        'job_failed' => [
            'title' => 'Task Failed',
            'body'  => ':job could not be completed. Please try again.',
        ],
        'export_ready' => [
            'title' => 'Export Ready',
            'body'  => 'Your :file is ready to download',
        ],
    ],

];