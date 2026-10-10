<?php

declare(strict_types=1);

return [
    'navigation_groups' => [
        'members' => 'Members',
        'queues' => 'Queues',
    ],

    'definitions' => [
        'navigation_label' => 'Entitlements',
        'model_label' => 'entitlement definition',
        'plural_model_label' => 'entitlement definitions',
        'form' => [
            'key_help' => 'Stable machine key, for example: forums.read',
            'namespace_help' => 'Optional grouping, usually the key without its last segment.',
            'metadata' => 'Metadata',
        ],
        'table' => [
            'wildcard' => 'Wildcard',
        ],
    ],

    'default_grants' => [
        'navigation_label' => 'Default grants',
        'model_label' => 'default entitlement grant',
        'plural_model_label' => 'default entitlement grants',
        'form' => [
            'entitlement' => 'Entitlement',
            'metadata' => 'Metadata',
        ],
        'table' => [
            'entitlement' => 'Entitlement',
        ],
    ],

    'user_grants' => [
        'navigation_label' => 'User grants',
        'model_label' => 'user entitlement grant',
        'plural_model_label' => 'user entitlement grants',
        'form' => [
            'user' => 'User',
            'entitlement' => 'Entitlement',
            'granted_by' => 'Granted by',
            'revoked_by' => 'Revoked by',
            'metadata' => 'Metadata',
            'source_type_help' => 'Optional morph class for the source of this grant.',
            'source_id_help' => 'Optional record id for the source model.',
            'grant_type' => [
                'manual' => 'Manual',
                'approved_request' => 'Approved request',
                'promotion' => 'Promotion',
                'system' => 'System',
                'membership' => 'Membership',
            ],
        ],
        'table' => [
            'user' => 'User',
            'entitlement' => 'Entitlement',
            'granted_by' => 'Granted by',
        ],
    ],

    'requests' => [
        'navigation_label' => 'Entitlement requests',
        'model_label' => 'entitlement request',
        'plural_model_label' => 'entitlement requests',
        'table' => [
            'user' => 'User',
            'entitlement' => 'Entitlement',
            'reviewed_by' => 'Reviewed by',
        ],
        'infolist' => [
            'request_section' => 'Request',
            'metadata_section' => 'Metadata',
            'user' => 'User',
            'entitlement' => 'Entitlement',
            'reviewed_by' => 'Reviewed by',
            'request_reason' => 'Request reason',
            'review_notes' => 'Review notes',
        ],
        'actions' => [
            'approve' => 'Approve',
            'reject' => 'Reject',
            'revoke' => 'Revoke',
            'review_notes' => 'Review notes',
            'revocation_notes' => 'Revocation notes',
            'grant_expires_at' => 'Grant expires at',
        ],
        'notifications' => [
            'approved' => 'Entitlement request approved',
            'rejected' => 'Entitlement request rejected',
            'revoked' => 'Entitlement request revoked',
        ],
    ],

    'relation_managers' => [
        'grants' => [
            'title' => 'Entitlement grants',
            'description' => 'Manage direct entitlement grants for this user, including temporary and revoked access.',
            'system' => 'System',
        ],
        'requests' => [
            'title' => 'Entitlement requests',
            'description' => 'Review entitlement requests submitted by this user and approve, reject, or revoke them in context.',
            'open' => 'Open',
        ],
    ],
];
