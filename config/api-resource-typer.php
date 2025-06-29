<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | The path where TypeScript interface files will be generated.
    | Default is 'resources/js/types' directory.
    |
    */
    'output_path' => resource_path('js/types'),

    /*
    |--------------------------------------------------------------------------
    | Models Directory
    |--------------------------------------------------------------------------
    |
    | The directory where your Eloquent models are located.
    |
    */
    'models_path' => app_path('Models'),

    /*
    |--------------------------------------------------------------------------
    | Controllers Directory
    |--------------------------------------------------------------------------
    |
    | The directory where your API controllers are located.
    |
    */
    'controllers_path' => app_path('Http/Controllers/Api'),

    /*
    |--------------------------------------------------------------------------
    | Type Mappings
    |--------------------------------------------------------------------------
    |
    | Map PHP/Laravel types to TypeScript types.
    |
    */
    'type_mappings' => [
        'string' => 'string',
        'integer' => 'number',
        'int' => 'number',
        'float' => 'number',
        'double' => 'number',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'array' => 'any[]',
        'object' => 'object',
        'datetime' => 'Date',
        'timestamp' => 'Date',
        'date' => 'Date',
        'time' => 'string',
        'json' => 'any',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Generate
    |--------------------------------------------------------------------------
    |
    | Automatically generate types when API responses are returned.
    | Only works in debug mode for performance reasons.
    |
    */
    'auto_generate' => env('API_RESOURCE_TYPER_AUTO_GENERATE', true),

    /*
    |--------------------------------------------------------------------------
    | Exclude Columns
    |--------------------------------------------------------------------------
    |
    | Columns that should be excluded from type generation.
    |
    */
    'exclude_columns' => [
        'password',
        'remember_token',
        'email_verified_at',
    ],
];