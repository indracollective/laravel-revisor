<?php

use Indra\Revisor\Enums\RevisorMode;

return [
    // The default mode determines which table will be read/written to by default
    // The RevisorMode enum is used to define the possible values for this
    // which are `Draft`, `Version` and `Published`
    'default_mode' => RevisorMode::Published,

    // The table suffixes are used to define the table names for each mode
    // The keys are the values of the RevisorMode enum
    // The values are the table suffixes
    'table_suffixes' => [
        RevisorMode::Draft->value => '_drafts',
        RevisorMode::Version->value => '_versions',
        RevisorMode::Published->value => '_published',
    ],

    // The publishing config is used to determine the default publishing behaviour,
    'publishing' => [
        // If true, records will be automatically published on created
        'publish_on_created' => false,
        // If true, records will be automatically published on updated
        'publish_on_updated' => false,
        // table column names
        'table_columns' => [
            'is_published' => 'is_published',
            'published_at' => 'published_at',
            'publisher' => 'publisher',
        ],
    ],

    // The publishing config is used to determine the default versioning behaviour,
    'versioning' => [
        // If true, new version records will be automatically created when drafts are created
        'save_new_version_on_created' => true,
        // If true, new version records will be automatically created when drafts are updated
        'save_new_version_on_updated' => true,
        // The maximum number of versions to keep
        // if set to true, version records will not be pruned
        'keep_versions' => 10,
        // table column names,
        'table_columns' => [
            'is_current' => 'is_current',
            'version_number' => 'version_number',
            'record_id' => 'record_id',
        ],
    ],
];
