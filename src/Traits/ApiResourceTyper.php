<?php

namespace AndraZero121\ApiResourceTyper\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\File;

trait ApiResourceTyper
{
    /**
     * Auto-generate types when returning API responses
     */
    protected function responseWithTypes($resource, int $status = 200, array $headers = []): JsonResponse
    {
        // Generate types if in development environment
        if (config('app.debug') && config('api-resource-typer.auto_generate', true)) {
            $this->generateTypesFromResource($resource);
        }

        if ($resource instanceof JsonResource || $resource instanceof ResourceCollection) {
            return $resource->response()->setStatusCode($status);
        }

        return response()->json($resource, $status, $headers);
    }

    /**
     * Generate types from resource
     */
    protected function generateTypesFromResource($resource): void
    {
        try {
            if ($resource instanceof JsonResource) {
                $this->generateTypeFromSingleResource($resource);
            } elseif ($resource instanceof ResourceCollection) {
                $this->generateTypeFromCollection($resource);
            }
        } catch (\Exception $e) {
            // Silently fail in production, log in development
            if (config('app.debug')) {
                logger()->error('ApiResourceTyper: ' . $e->getMessage());
            }
        }
    }

    /**
     * Generate type from single resource
     */
    protected function generateTypeFromSingleResource(JsonResource $resource): void
    {
        $data = $resource->resolve();
        $resourceClass = get_class($resource);
        $typeName = $this->getTypeNameFromResourceClass($resourceClass);
        
        $this->generateTypeScriptInterface($typeName, $data);
    }

    /**
     * Generate type from collection resource
     */
    protected function generateTypeFromCollection(ResourceCollection $resource): void
    {
        $collection = $resource->collection;
        
        if ($collection->isNotEmpty()) {
            $firstItem = $collection->first();
            if ($firstItem instanceof JsonResource) {
                $data = $firstItem->resolve();
                $resourceClass = get_class($firstItem);
                $typeName = $this->getTypeNameFromResourceClass($resourceClass);
                
                $this->generateTypeScriptInterface($typeName, $data);
            }
        }
    }

    /**
     * Get type name from resource class
     */
    protected function getTypeNameFromResourceClass(string $resourceClass): string
    {
        $className = class_basename($resourceClass);
        return str_replace('Resource', '', $className) . 'Type';
    }

    /**
     * Generate TypeScript interface from data
     */
    protected function generateTypeScriptInterface(string $typeName, array $data): void
    {
        $outputPath = config('api-resource-typer.output_path');
        
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        $fileName = $outputPath . '/' . $typeName . '.ts';
        
        // Don't regenerate if file exists and is recent (less than 1 hour old)
        if (File::exists($fileName) && File::lastModified($fileName) > (time() - 3600)) {
            return;
        }

        $types = $this->inferTypesFromData($data);
        $content = $this->buildTypeScriptContent($typeName, $types);
        
        File::put($fileName, $content);
    }

    /**
     * Infer TypeScript types from data
     */
    protected function inferTypesFromData(array $data): array
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
            return 'any';
        }
        
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_int($value) || is_float($value)) {
            return 'number';
        }
        
        if (is_string($value)) {
            // Check if it's a date string
            if ($this->isDateString($value)) {
                return 'Date';
            }
            return 'string';
        }
        
        if (is_array($value)) {
            if (empty($value)) {
                return 'any[]';
            }
            
            // Check if it's an associative array (object)
            if (array_keys($value) !== range(0, count($value) - 1)) {
                return 'object';
            }
            
            // Get type of first element for array type
            $firstElementType = $this->getTypeScriptType($value[0]);
            return $firstElementType . '[]';
        }
        
        return 'any';
    }

    /**
     * Check if string is a date
     */
    protected function isDateString(string $value): bool
    {
        $dateFormats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s\Z',
            'Y-m-d\TH:i:sP',
            'Y-m-d',
        ];
        
        foreach ($dateFormats as $format) {
            if (\DateTime::createFromFormat($format, $value) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Build TypeScript content
     */
    protected function buildTypeScriptContent(string $typeName, array $types): string
    {
        $content = "// Auto-generated TypeScript interface\n";
        $content .= "// Generated at: " . now()->toDateTimeString() . "\n";
        $content .= "// From: " . static::class . "\n\n";
        
        $content .= "export interface {$typeName} {\n";
        
        foreach ($types as $property => $type) {
            $content .= "  {$property}: {$type};\n";
        }
        
        $content .= "}\n\n";
        
        // Add collection and response types
        $content .= "export interface {$typeName}Collection {\n";
        $content .= "  data: {$typeName}[];\n";
        $content .= "  links?: PaginationLinks;\n";
        $content .= "  meta?: PaginationMeta;\n";
        $content .= "}\n\n";
        
        $content .= "export interface {$typeName}Response {\n";
        $content .= "  data: {$typeName};\n";
        $content .= "}\n\n";
        
        // Add common pagination interfaces if not exists
        $content .= $this->getPaginationInterfaces();
        
        return $content;
    }

    /**
     * Get pagination interfaces
     */
    protected function getPaginationInterfaces(): string
    {
        return "// Common pagination interfaces\n" .
               "interface PaginationLinks {\n" .
               "  first: string;\n" .
               "  last: string;\n" .
               "  prev: string | null;\n" .
               "  next: string | null;\n" .
               "}\n\n" .
               "interface PaginationMeta {\n" .
               "  current_page: number;\n" .
               "  last_page: number;\n" .
               "  per_page: number;\n" .
               "  total: number;\n" .
               "  from: number;\n" .
               "  to: number;\n" .
               "}\n";
    }
}