<?php

namespace OwowAgency\AutomatedApiDoc\Parsers;

class ApibParser
{
    private $docs;

    public function __construct($docs)
    {
        $this->docs = $docs;
    }

    public function handle($json)
    {
        $collections = json_decode($json);

        $content = "";

        foreach ($collections as $collection) {
            $content .= "# Group /{$collection->collection_name}\n\n";

            foreach ($collection->paths as $path) {
                $content .= "## {$collection->collection_name} [/{$path->uri}]\n\n";

                if (count($path->route_parameters) > 0) {
                    $content .= "+ Parameters\n";

                    foreach ($path->route_parameters as $routeParameter) {
                        $content .= "    + {$routeParameter} (no type yet) - No description\n";
                    }

                    $content .= "\n";
                }

                foreach ($path->routes as $route) {
                    $content .= "### /{$path->uri} [{$route->method}]\n\n";
                    $content .= "{$route->summary}\n\n";

                    foreach ($route->items as $key => $routeItem) {
                        $requestName = count($route->items) === 0
                            ? ''
                            : " #{$key}";

                        $content .= "+ Request{$requestName} (application/json)\n\n";
                        $content .= "    + Headers\n\n";
                        $content .= "            Accept: application/json\n\n";

                        if (! is_null($routeItem->request_body)) {
                            $requestBody = json_encode($routeItem->request_body);

                            $content .= "    + Body\n\n";
                            $content .= "            {$requestBody}\n\n";
                        }

                        if (! is_null($routeItem->request_rules)) {
                            $requestRules = json_encode($routeItem->request_rules);

                            $content .= "    + Schema\n\n";
                            $content .= "            {$requestRules}\n\n";
                        }

                        $responseBody = json_encode($routeItem->response_body);

                        if ($routeItem->code == 204) {
                            $responseBody = '';
                        }

                        $content .= "+ Response {$routeItem->code} (application/json)\n\n";
                        $content .= "        {$responseBody}\n\n";
                    }
                }
            }
        }

        $file = rtrim($this->docs['output_path'], '/') . '/output.apib';

        if (! file_exists($file)) {
            touch($file);
        }

        file_put_contents($file, $content);
    }
}
