<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Output path
    |---------------------------------------------------------------------------
    |
    | Where to put the output of the automated api docs.
    |
    */

    'output_path' => public_path('docs'),

    /*
    |---------------------------------------------------------------------------
    | Temporary path
    |---------------------------------------------------------------------------
    |
    | After creating a JSON files of all documented routes we need to
    | temporarily store it. This file is used by the parsers.
    |
    */

    'temporary_path' => storage_path('app/docs.json'),

    /*
    |---------------------------------------------------------------------------
    | Parser list
    |---------------------------------------------------------------------------
    |
    | A list of all parsers which you'd like to use. A full list of all
    | available parsers can be found in the README.md
    |
    */

    'parsers' => [
        \OwowAgency\AutomatedApiDocs\Parsers\ApiBlueprint::class,
        // \OwowAgency\AutomatedApiDocs\Parsers\PostmanCollection::class,
    ],

    'parser_options' => [

        /*
        |-----------------------------------------------------------------------
        | Postman ID
        |-----------------------------------------------------------------------
        |
        | Unique ID of the postman collection. A UID is recommended.
        |
        | https://schema.getpostman.com/json/collection/v2.1.0/collection.json
        |
        */

        '_postman_id' => '',
    ],

];
