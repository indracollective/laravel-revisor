<?php

use Indra\Revisor\Enums\RevisorContext;

return [
    // The table suffixes are used to produce 3 tables for each of your Revisor enabled Models
    // Example: if your model's table name is `pages`, the default revisor table_suffixes config
    // will produce the following tables when migrations are run:
    // pages_drafts, pages_versions, pages_published
    'table_suffixes' => [
        RevisorContext::Draft->value => '_drafts',
        RevisorContext::Version->value => '_versions',
        RevisorContext::Published->value => '_published',
    ],

    // The default mode determines which of your Revisor enabled Model's tables will read/written to by default
    // The RevisorContext enum is used to define the possible values for this
    // The options are `Draft`, `Version` and `Published`
    'default_context' => RevisorContext::Published,

    // Publishing configuration
    'publishing' => [
        // Determines whether records should be automatically published when created/updated
        // These can be overridden on a Model instance as needed, see \Indra\Revisor\Concerns\HasPublishing
        'publish_on_created' => false,
        'publish_on_updated' => false,

        // The names of table columns that store publishing data
        'table_columns' => [
            'is_published' => 'is_published',
            'published_at' => 'published_at',
            'publisher' => 'publisher',
        ],
    ],

    // The publishing config is used to determine the default versioning behaviour,
    'versioning' => [
        // Determines whether records should have new versions created when created/updated
        // These can be overridden on a Model instance as needed, see \Indra\Revisor\Concerns\HasVersioning
        'save_new_version_on_created' => true,
        'save_new_version_on_updated' => true,

        // The maximum number of versions to keep
        // if set to true, version records will not be pruned
        'keep_versions' => 10,

        // The names of table columns that store versioning data
        'table_columns' => [
            'is_current' => 'is_current',
            'version_number' => 'version_number',
            'record_id' => 'record_id',
        ],
    ],
];
