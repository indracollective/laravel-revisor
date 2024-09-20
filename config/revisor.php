<?php

// config for Indra/Revisor
use Indra\Revisor\Enums\RevisorMode;

return [
    'default_mode' => RevisorMode::Published,
    'table_suffixes' => [
        RevisorMode::Draft->value => '_drafts',
        RevisorMode::Version->value => '_versions',
        RevisorMode::Published->value => '_published',
    ],
    'publishing' => [
        'publish_on_created' => false,
        'publish_on_updated' => false,
    ],
    'versioning' => [
        'record_new_version_on_created' => true,
        'record_new_version_on_updated' => true,
        'keep_versions' => 10,
    ],
];
