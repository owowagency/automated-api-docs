<?php

namespace OwowAgency\AutomatedApiDocs\Parsers;

class PostmanCollection extends Parser
{
    /**
     * The Postman Collection array.
     *
     * @var array
     */
    private $collections;

    /**
     * Execute the parser and transform to correct format.
     *
     * @param  array  $collections
     * @return void
     */
    public function handle($collections)
    {
        foreach ($collections as $collection) {
            $this->createGroup($collection);
        }

        $this->saveContent(json_encode([
            'info' => [
                '_postman_id' => $this->getConfigValue('parser_options._postman_id'),
                'name' => config('app.name'),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $this->collections,
        ]));
    }

    /**
     * Create a new group (Postman folder).
     *
     * @param  array  $collection
     * @return void
     */
    protected function createGroup($collection)
    {
        $group = [
            'name' => ucwords($collection->collection_name),
        ];

        $routes = [];

        foreach ($collection->paths as $path) {
            foreach ($path->routes as $route) {
                foreach ($route->items as $item) {
                    $summary = mb_strimwidth(rtrim($route->summary, '.'), 0, 35, '...');

                    $routes[] = [
                        'name' => $summary,
                        'request' => [
                            'method' => $route->method,
                            'header' => [
                                [
                                    'key' => 'Content-Type',
                                    'value' => 'application/json'
                                ],
                                [
                                    'key' => 'Accept',
                                    'value' => 'application/json'
                                ],
                                [
                                    'key' => 'Authorization',
                                    'value' => 'Bearer {{api_token}}'
                                ],
                            ],
                            'body' => [
                                'mode' => 'raw',
                                'raw' => json_encode($item->request_body, JSON_PRETTY_PRINT),
                            ],
                            'url' => [
                                'raw' => "{{base_url}}/{$path->uri}",
                                'host' => '{{base_url}}',
                                'path' => array_filter(explode('/', $path->uri)),
                            ],
                            'description' => strlen($summary) > 35
                                ? $route->summary
                                : '',
                        ],
                        'response' => json_encode($item->response_body, JSON_PRETTY_PRINT),
                    ];
                }
            }
        }

        $group['item'] = $routes;

        $this->collections[] = $group;
    }

    /**
     * Get the file extension of the docs format.
     *
     * @return string
     */
    public function getExtension()
    {
        return 'postman_collection.json';
    }
}
