<?php

return [
    // Lead Management
    'lead_created_successfully' => 'Lead created successfully',
    'lead_updated_successfully' => 'Lead updated successfully',
    'lead_deleted_successfully' => 'Lead deleted successfully',
    'lead_not_found' => 'Lead not found',
    'lead_assigned_successfully' => 'Lead assigned successfully',
    'lead_score_updated_successfully' => 'Lead score updated successfully',
    'lead_qualified_successfully' => 'Lead qualified successfully',
    'lead_scored_successfully' => 'Lead scored successfully',
    'leads_assigned_successfully' => ':count leads assigned successfully',
    'leads_status_updated_successfully' => ':count leads status updated successfully',
    'leads_scored_successfully' => ':count leads scored successfully',
    'leads_qualified_successfully' => ':count leads qualified successfully',

    // Opportunity Management
    'opportunity_created_successfully' => 'Opportunity created successfully',
    'opportunity_updated_successfully' => 'Opportunity updated successfully',
    'opportunity_deleted_successfully' => 'Opportunity deleted successfully',
    'opportunity_not_found' => 'Opportunity not found',
    'opportunity_moved_successfully' => 'Opportunity moved to new stage successfully',
    'opportunity_won_successfully' => 'Opportunity marked as won successfully',
    'opportunity_lost_successfully' => 'Opportunity marked as lost successfully',
    'opportunities_moved_successfully' => ':count opportunities moved successfully',

    // Pipeline Management
    'pipeline_created_successfully' => 'Pipeline created successfully',
    'pipeline_updated_successfully' => 'Pipeline updated successfully',
    'pipeline_deleted_successfully' => 'Pipeline deleted successfully',
    'pipeline_not_found' => 'Pipeline not found',
    'pipeline_set_as_default_successfully' => 'Pipeline set as default successfully',
    'pipeline_activated_successfully' => 'Pipeline activated successfully',
    'pipeline_deactivated_successfully' => 'Pipeline deactivated successfully',
    'cannot_delete_pipeline_with_opportunities' => 'Cannot delete pipeline with existing opportunities',
    'cannot_deactivate_default_pipeline' => 'Cannot deactivate the default pipeline',

    // Stage Management
    'stage_created_successfully' => 'Stage created successfully',
    'stage_updated_successfully' => 'Stage updated successfully',
    'stage_deleted_successfully' => 'Stage deleted successfully',
    'stage_not_found' => 'Stage not found',
    'stages_reordered_successfully' => 'Stages reordered successfully',
    'cannot_delete_stage_with_opportunities' => 'Cannot delete stage with existing opportunities',

    // Activity Management
    'activity_created_successfully' => 'Activity created successfully',
    'activity_updated_successfully' => 'Activity updated successfully',
    'activity_deleted_successfully' => 'Activity deleted successfully',
    'activity_not_found' => 'Activity not found',
    'activity_started_successfully' => 'Activity started successfully',
    'activity_completed_successfully' => 'Activity completed successfully',
    'activity_cancelled_successfully' => 'Activity cancelled successfully',
    'activity_rescheduled_successfully' => 'Activity rescheduled successfully',
    'activities_completed_successfully' => ':count activities completed successfully',
    'activities_rescheduled_successfully' => ':count activities rescheduled successfully',

    // Notes and Tags
    'note_added_successfully' => 'Note added successfully',
    'note_updated_successfully' => 'Note updated successfully',
    'note_deleted_successfully' => 'Note deleted successfully',
    'tag_added_successfully' => 'Tag added successfully',
    'tag_removed_successfully' => 'Tag removed successfully',
    'tag_created_successfully' => 'Tag created successfully',
    'tag_updated_successfully' => 'Tag updated successfully',
    'tag_deleted_successfully' => 'Tag deleted successfully',

    // Contact Management
    'contact_created_successfully' => 'Contact created successfully',
    'contact_updated_successfully' => 'Contact updated successfully',
    'contact_deleted_successfully' => 'Contact deleted successfully',
    'contact_not_found' => 'Contact not found',

    // Validation Messages
    'validation' => [
        'first_name_required' => 'First name is required',
        'last_name_required' => 'Last name is required',
        'email_invalid' => 'Please provide a valid email address',
        'expected_close_date_future' => 'Expected close date must be in the future',
        'score_min' => 'Score must be at least 0',
        'score_max' => 'Score cannot be more than 100',
        'estimated_value_min' => 'Estimated value must be at least 0',
        'amount_required' => 'Amount is required',
        'amount_min' => 'Amount must be greater than 0',
        'pipeline_required' => 'Pipeline is required',
        'stage_required' => 'Stage is required',
        'title_required' => 'Title is required',
        'activity_type_required' => 'Activity type is required',
        'subject_required' => 'Subject is required',
    ],

    // Status Labels
    'status' => [
        'new' => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'unqualified' => 'Unqualified',
        'converted' => 'Converted',
        'open' => 'Open',
        'won' => 'Won',
        'lost' => 'Lost',
        'cancelled' => 'Cancelled',
        'scheduled' => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ],

    // Source Labels
    'source' => [
        'website' => 'Website',
        'referral' => 'Referral',
        'social_media' => 'Social Media',
        'advertisement' => 'Advertisement',
        'email_campaign' => 'Email Campaign',
        'cold_call' => 'Cold Call',
        'trade_show' => 'Trade Show',
        'partner' => 'Partner',
        'organic_search' => 'Organic Search',
        'paid_search' => 'Paid Search',
        'direct' => 'Direct',
        'other' => 'Other',
    ],

    // Activity Types
    'activity_type' => [
        'call' => 'Phone Call',
        'email' => 'Email',
        'meeting' => 'Meeting',
        'task' => 'Task',
        'note' => 'Note',
        'sms' => 'SMS',
        'follow_up' => 'Follow Up',
        'demo' => 'Demo',
        'proposal_sent' => 'Proposal Sent',
        'contract_sent' => 'Contract Sent',
        'payment_received' => 'Payment Received',
        'complaint' => 'Complaint',
        'support' => 'Support',
    ],

    // Priority Labels
    'priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    // General
    'dashboard' => 'CRM Dashboard',
    'leads' => 'Leads',
    'opportunities' => 'Opportunities',
    'pipelines' => 'Pipelines',
    'activities' => 'Activities',
    'contacts' => 'Contacts',
    'reports' => 'Reports',
    'settings' => 'CRM Settings',
];