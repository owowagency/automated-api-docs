<?php

namespace App\Library\Parsers;

use OwowAgency\AutomatedApiDocs\Parsers\Parser;
use Symfony\Component\Yaml\Yaml;

class Swagger extends Parser
{
    /**
     * Temporary storage for the parsed data.
     */
    private $content;

    /**
     * Execute the parser and transform to correct format.
     */
    public function handle($collection): void
    {
        $baseFile = file_get_contents(__DIR__.'/base.yml');

        $this->addLines(0, $baseFile);
        
        foreach ($collection as $group) {
            $this->addGroup($group);
        }

        $this->saveContent($this->content);
    }

    /**
     * Add lines to content
     */
    public function addLines(int $spacing, string $lines): void
    {
        $lines = explode("\n", $lines);

        foreach ($lines as $line) {
            $this->content .=  $this->space($spacing) . $line . PHP_EOL;
        }
    }

    /**
     * Add group to content
     */
    public function addGroup($group): void
    {
        foreach ($group->paths as $path) {
            $this->addLines(2, $path->uri.':');

            foreach ($path->routes as $route) {
                $this->addRoute($route, $group, $path);
            }
        }
    }

    /**
     * Add route to content
     */
    public function addRoute($route, $group, $path): void
    {
        $this->addLines(4, strtolower($route->method).':');

        $this->addLines(6, 'summary: '.$route->summary);

        $this->addTag($group->collection_name);

        $this->addRouteParameters($path->route_parameters);

        $this->addResponses($route->items);

        $this->addRequests($route->items);

    }

    /**
     * Add tag to content
     */
    public function addTag($tag): void
    {
        $this->addLines(6, 'tags:');
        $this->addLines(8, "- ". $tag);
    }

    /**
     * Add route parameters to content
     */
    public function addRouteParameters(array $parameters): void
    {
        foreach ($parameters as $parameter) {
            $this->addLines(6, 'parameters:');
            $this->addLines(8, '- name: '.$parameter);
            $this->addLines(10, 'in: path');
            $this->addLines(10, 'required: true');
            $this->addLines(10, 'schema:');
            $this->addLines(12, 'type: '. gettype($parameter));
        }
    }

    /**
     * Add responses to content
     */
    public function addResponses(array $responses): void
    {
        $this->addLines(6, "responses:");

        foreach ($responses as $response) {
            $this->addLines(8,"\"{$response->code}\":");
            $this->addLines(10,"content:");
            $this->addLines(12,"application/json:");
            $this->addLines(14,"schema:");
            $this->addLines(16,"type: object");
            $this->addLines(16,"properties:");
            
            $response_body = Yaml::dump((array) $this->arrayToTypeExample($response->response_body));

            $this->addLines(18, "{$response_body}");
        }
    }

    /**
     * Add requests to content
     */
    public function addRequests(array $requests): void
    {
        if(count($requests) > 0) {
            $this->addLines(6,"requestBody:");
            $this->addLines(8,"required: true");
            $this->addLines(8,"content:");
            $this->addLines(10,"application/json:");
            $this->addLines(12,"schema:");
    
            foreach ($requests as $request) {
                $this->addLines(14,"type: object");
                $this->addLines(14,"properties:");
                
                $request_body = Yaml::dump((array) $this->arrayToTypeExample($request->request_body));
    
                $this->addLines(18, "{$request_body}");
            }
        }
    }

    /**
     * Convert array to openAPI array
     */
    public function arrayToTypeExample(array|object $array): array
    {
        $newArray = [];

        foreach ($array as $key => $value) {
            if(is_array($value)) {
                if(!empty($value)) {
                    $newArray[$key] = [
                        'type' => 'array',
                        'items' => [
                            'allOf' => $this->arrayToTypeExample($value)
                        ]
                    ];
                }
            } elseif(is_object($value)) {
                $newArray[$key] = [
                    'type' => 'object',
                    'properties' => $this->arrayToTypeExample($value)
                ];
            } else {
                $newArray[$key] = [
                    'type' => gettype($value),
                    'example' => is_array($array) ? $this->arrayToTypeExample($value) : $value
                ];
            }
        }

        return $newArray;
    }

    /**
     * Get the file extension of the docs format.
     */
    public function getExtension(): string
    {
        return 'yml';
    }

    /**
     * Create a space.
     */
    public function space($count = 1): string
    {
        return str_repeat(' ', $count);
    }
}
