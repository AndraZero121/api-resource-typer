<?php

namespace AndraZero121\ApiResourceTyper\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class ApiResourceTyperMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only process in debug mode and for JSON responses
        if (!config('app.debug') || 
            !config('api-resource-typer.auto_generate', true) ||
            !$response instanceof JsonResponse) {
            return $response;
        }

        // Only process API routes
        if (!str_starts_with($request->path(), 'api/')) {
            return $response;
        }

        $this->processResponse($response, $request);

        return $response;
    }

    /**
     * Process the response and generate types
     */
    protected function processResponse(JsonResponse $response, Request $request): void
    {
        try {
            $data = $response->getData(true);
            
            if (!is_array($data)) {
                return;
            }

            // Detect response structure
            $typeName = $this->getTypeNameFromRoute($request->route()->getName() ?? $request->path());
            
            if (isset($data['data'])) {
                // Laravel Resource response structure
                if (is_array($data['data']) && !empty($data['data'])) {
                    $firstItem = is_array($data['data'][0]) ? $data['data'][0] : $data['data'];
                    $this->generateTypeScript($typeName, $firstItem);
                }
            } else {
                // Direct data response
                $this->generateTypeScript($typeName, $data);
            }
        } catch (\Exception $e) {
            // Log error but don't break the response
            logger()->error('ApiResourceTyperMiddleware: ' . $e->getMessage());
        }
    }

    /**
     * Get type name from route
     */
    protected function getTypeNameFromRoute(string $route): string
    {
        // Remove api prefix and clean up
        $route = str_replace(['api.', 'api/', '/'], ['', '', '.'], $route);
        
        // Convert to PascalCase
        $parts = explode('.', $route);
        $typeName = '';
        
        foreach ($parts as $part) {
            $typeName .= ucfirst($part);
        }
        
        return $typeName . 'Type';
    }

    /**
     * Generate TypeScript interface
     */
    protected function generateTypeScript(string $typeName, array $data): void
    {
        $outputPath = config('api-resource-typer.output_path');
        
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        $fileName = $outputPath . '/' . $typeName . '.ts';
        
        // Don't regenerate if file exists and is recent
        if (File::exists($fileName) && File::lastModified($fileName) > (time() - 3600)) {
            return;
        }

        $types = $this->inferTypes($data);
        $content = $this->buildContent($typeName, $types);
        
        File::put($fileName, $content);
    }

    /**
     * Infer TypeScript types from data
     */
    protected function inferTypes(array $data): array
    {
        $types = [];
        
        foreach ($data as $key => $value) {
            $types[$key] = $this->getTypeScriptType($value);
        }
        
        return $types;
    }

    /**
     * Get TypeScript type from PHP value
     */
    protected function getTypeScriptType($value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_int($value) || is_float($value)) {
            return 'number';
        }
        
        if (is_string($value)) {
            return $this->isDateString($value) ? 'Date' : 'string';
        }
        
        if (is_array($value)) {
            if (empty($value)) {
                return 'any[]';
            }
            
            // Check if it's an object (associative array)
            if (array_keys($value) !== range(0, count($value) - 1)) {
                return 'object';
            }
            
            // Array - get type from first element
            $firstType = $this->getTypeScriptType($value[0]);
            return $firstType . '[]';
        }
        
        return 'any';
    }

    /**
     * Check if string is a date
     */
    protected function isDateString(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $value) ||
               preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value);
    }

    /**
     * Build TypeScript content
     */
    protected function buildContent(string $typeName, array $types): string
    {
        $content = "// Auto-generated by ApiResourceTyper\n";
        $content .= "// Generated at: " . now()->toDateTimeString() . "\n\n";
        
        $content .= "export interface {$typeName} {\n";
        foreach ($types as $property => $type) {
            $content .= "  {$property}: {$type};\n";
        }
        $content .= "}\n\n";
        
        // Add API response wrappers
        $content .= "export interface {$typeName}Response {\n";
        $content .= "  data: {$typeName};\n";
        $content .= "}\n\n";
        
        $content .= "export interface {$typeName}Collection {\n";
        $content .= "  data: {$typeName}[];\n";
        $content .= "  links?: {\n";
        $content .= "    first: string;\n";
        $content .= "    last: string;\n";
        $content .= "    prev: string | null;\n";
        $content .= "    next: string | null;\n";
        $content .= "  };\n";
        $content .= "  meta?: {\n";
        $content .= "    current_page: number;\n";
        $content .= "    last_page: number;\n";
        $content .= "    per_page: number;\n";
        $content .= "    total: number;\n";
        $content .= "  };\n";
        $content .= "}\n";
        
        return $content;
    }
}