<?php

return [

    'actions' => [
        'view'         => 'দেখুন',
        'view_invoice' => 'চালান দেখুন',
        'view_product' => 'পণ্য দেখুন',
        'view_order'   => 'অর্ডার দেখুন',
        'view_leave'   => 'ছুটি দেখুন',
        'view_project' => 'প্রজেক্ট দেখুন',
        'view_task'    => 'টাস্ক দেখুন',
        'view_shipment' => 'শিপমেন্ট দেখুন',
        'view_lead'    => 'লিড দেখুন',
        'dismiss'      => 'বাতিল করুন',
        'track'        => 'ট্র্যাক করুন',
    ],

    // ─── Invoice Domain ─────────────────────────────
    'invoice' => [
        'paid' => [
            'title' => 'চালান পরিশোধিত',
            'body'  => 'চালান #:number পরিশোধ হয়েছে — :amount',
        ],
        'overdue' => [
            'title' => 'চালান মেয়াদোত্তীর্ণ',
            'body'  => 'চালান #:number এর মেয়াদ :days দিন আগে শেষ হয়েছে',
        ],
        'created' => [
            'title' => 'নতুন চালান তৈরি',
            'body'  => ':customer এর জন্য চালান #:number তৈরি হয়েছে',
        ],
        'sent' => [
            'title' => 'চালান পাঠানো হয়েছে',
            'body'  => 'চালান #:number :customer কে পাঠানো হয়েছে',
        ],
    ],

    // ─── Inventory Domain ────────────────────────────
    'inventory' => [
        'stock_low' => [
            'title' => 'স্টক সতর্কতা',
            'body'  => ':product এর স্টক কম — মাত্র :quantity টি বাকি',
        ],
        'stock_out' => [
            'title' => 'স্টক শেষ',
            'body'  => ':product এর স্টক শেষ হয়ে গেছে',
        ],
        'stock_received' => [
            'title' => 'স্টক প্রাপ্ত',
            'body'  => ':product এর :quantity টি নতুন স্টক যোগ হয়েছে',
        ],
    ],

    // ─── HR Domain ───────────────────────────────────
    'hr' => [
        'leave_applied' => [
            'title' => 'ছুটির আবেদন',
            'body'  => ':employee :days দিনের ছুটির আবেদন করেছেন',
        ],
        'leave_approved' => [
            'title' => 'ছুটি অনুমোদিত',
            'body'  => 'আপনার :days দিনের ছুটি অনুমোদন হয়েছে',
        ],
        'leave_rejected' => [
            'title' => 'ছুটি প্রত্যাখ্যাত',
            'body'  => 'আপনার ছুটির আবেদন প্রত্যাখ্যান করা হয়েছে',
        ],
        'payroll_processed' => [
            'title' => 'বেতন প্রক্রিয়াকৃত',
            'body'  => ':month মাসের বেতন প্রক্রিয়া সম্পন্ন হয়েছে',
        ],
    ],

    // ─── CRM Domain ──────────────────────────────────
    'crm' => [
        'lead_created' => [
            'title' => 'নতুন লিড',
            'body'  => ':name থেকে নতুন লিড এসেছে',
        ],
        'lead_converted' => [
            'title' => 'লিড রূপান্তরিত',
            'body'  => ':name কে সফলভাবে গ্রাহকে রূপান্তর করা হয়েছে',
        ],
        'lead_assigned' => [
            'title' => 'লিড অ্যাসাইন',
            'body'  => 'আপনাকে :name এর লিড অ্যাসাইন করা হয়েছে',
        ],
        'opportunity_won' => [
            'title' => 'সুযোগ জিতেছে',
            'body'  => ':name এর সুযোগ জিতেছে — :amount',
        ],
    ],

    // ─── Logistics Domain ────────────────────────────
    'logistics' => [
        'shipment' => [
            'created' => [
                'title' => 'শিপমেন্ট তৈরি হয়েছে',
                'body'  => ':recipient_name এর জন্য ট্র্যাকিং #:tracking_number তৈরি হয়েছে',
            ],
            'status_updated' => [
                'title' => 'শিপমেন্ট আপডেট',
                'body'  => 'ট্র্যাকিং #:tracking_number এখন :status অবস্থায় :location এ',
            ],
            'delivered' => [
                'title' => 'ডেলিভারি সম্পন্ন',
                'body'  => ':recipient_name এর শিপমেন্ট #:tracking_number সফলভাবে পৌঁছেছে',
            ],
            'failed' => [
                'title' => 'ডেলিভারি ব্যর্থ',
                'body'  => 'শিপমেন্ট #:tracking_number ডেলিভারি করা যায়নি',
            ],
            'cancelled' => [
                'title' => 'শিপমেন্ট বাতিল',
                'body'  => 'শিপমেন্ট #:tracking_number বাতিল করা হয়েছে',
            ],
        ],
        'return' => [
            'requested' => [
                'title' => 'রিটার্ন অনুরোধ',
                'body'  => 'শিপমেন্ট #:tracking_number এর জন্য রিটার্ন অনুরোধ - :reason',
            ],
            'approved' => [
                'title' => 'রিটার্ন অনুমোদিত',
                'body'  => 'শিপমেন্ট #:tracking_number এর রিটার্ন অনুমোদন হয়েছে',
            ],
            'rejected' => [
                'title' => 'রিটার্ন প্রত্যাখ্যাত',
                'body'  => 'শিপমেন্ট #:tracking_number এর রিটার্ন প্রত্যাখ্যান করা হয়েছে',
            ],
        ],
        'cod' => [
            'collected' => [
                'title' => 'COD সংগৃহীত',
                'body'  => 'শিপমেন্ট #:tracking_number এর COD পরিমাণ :amount সংগৃহীত হয়েছে',
            ],
            'settled' => [
                'title' => 'COD নিষ্পত্তি',
                'body'  => 'শিপমেন্ট #:tracking_number এর COD ক্যারিয়ারের সাথে নিষ্পত্তি হয়েছে',
            ],
        ],
    ],

    // ─── Purchasing Domain ───────────────────────────
    'purchasing' => [
        'po_approved' => [
            'title' => 'ক্রয় আদেশ অনুমোদিত',
            'body'  => 'ক্রয় আদেশ #:number অনুমোদন হয়েছে',
        ],
        'po_received' => [
            'title' => 'মাল প্রাপ্ত',
            'body'  => ':supplier থেকে মাল পাওয়া গেছে',
        ],
    ],

    // ─── Projects Domain ─────────────────────────────
    'projects' => [
        'task_assigned' => [
            'title' => 'টাস্ক অ্যাসাইন',
            'body'  => ':project প্রজেক্টে আপনাকে ":task" টাস্ক দেওয়া হয়েছে',
        ],
        'deadline_near' => [
            'title' => 'ডেডলাইন আসছে',
            'body'  => '":task" টাস্কের ডেডলাইন :days দিন বাকি',
        ],
        'project_completed' => [
            'title' => 'প্রজেক্ট সম্পন্ন',
            'body'  => '":project" প্রজেক্ট সফলভাবে সম্পন্ন হয়েছে',
        ],
    ],

    // ─── Accounting Domain ───────────────────────────
    'accounting' => [
        'payment_received' => [
            'title' => 'পেমেন্ট প্রাপ্ত',
            'body'  => ':amount পেমেন্ট পাওয়া গেছে',
        ],
        'expense_approved' => [
            'title' => 'ব্যয় অনুমোদিত',
            'body'  => ':amount এর ব্যয় অনুমোদন হয়েছে',
        ],
    ],

    // ─── System ──────────────────────────────────────
    'system' => [
        'alert' => [
            'title' => 'সিস্টেম সতর্কতা',
            'body'  => ':message',
        ],
        'job_completed' => [
            'title' => 'কাজ সম্পন্ন',
            'body'  => ':job সফলভাবে সম্পন্ন হয়েছে',
        ],
        'job_failed' => [
            'title' => 'কাজ ব্যর্থ',
            'body'  => ':job সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।',
        ],
        'export_ready' => [
            'title' => 'এক্সপোর্ট প্রস্তুত',
            'body'  => 'আপনার :file ফাইল ডাউনলোডের জন্য প্রস্তুত',
        ],
    ],

];