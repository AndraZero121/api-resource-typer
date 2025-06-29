<?php

namespace AndraZero121\ApiResourceTyper\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class GenerateTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'generate:api-types {--model=} {--controller=}';

    /**
     * The console command description.
     */
    protected $description = 'Generate TypeScript interfaces from API Resource Controllers and Models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting API Resource Types generation...');

        // Create output directory if it doesn't exist
        $outputPath = config('api-resource-typer.output_path');
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
            $this->info("📁 Created directory: {$outputPath}");
        }

        // Get specific model or all models
        if ($this->option('model')) {
            $this->generateForModel($this->option('model'));
        } else {
            $this->generateForAllModels();
        }

        $this->info('✅ Type generation completed!');
    }

    /**
     * Generate types for all models
     */
    protected function generateForAllModels()
    {
        $modelsPath = config('api-resource-typer.models_path');
        
        if (!File::exists($modelsPath)) {
            $this->error("Models directory not found: {$modelsPath}");
            return;
        }

        $modelFiles = File::glob($modelsPath . '/*.php');
        
        foreach ($modelFiles as $modelFile) {
            $modelName = basename($modelFile, '.php');
            $this->generateForModel($modelName);
        }
    }

    /**
     * Generate types for specific model
     */
    protected function generateForModel(string $modelName)
    {
        $this->info("🔄 Processing model: {$modelName}");

        try {
            $modelClass = "App\\Models\\{$modelName}";
            
            if (!class_exists($modelClass)) {
                $this->warn("Model class not found: {$modelClass}");
                return;
            }

            $model = new $modelClass();
            $tableName = $model->getTable();
            
            // Get table columns
            $columns = Schema::getColumnListing($tableName);
            $columnTypes = [];

            foreach ($columns as $column) {
                $columnType = Schema::getColumnType($tableName, $column);
                $columnTypes[$column] = $this->mapToTypeScriptType($columnType);
            }

            // Generate TypeScript interface
            $this->generateTypeScriptInterface($modelName, $columnTypes);

        } catch (\Exception $e) {
            $this->error("Error processing {$modelName}: " . $e->getMessage());
        }
    }

    /**
     * Map PHP/Database types to TypeScript types
     */
    protected function mapToTypeScriptType(string $type): string
    {
        $mappings = config('api-resource-typer.type_mappings');
        return $mappings[$type] ?? 'any';
    }

    /**
     * Generate TypeScript interface file
     */
    protected function generateTypeScriptInterface(string $modelName, array $columnTypes)
    {
        $excludeColumns = config('api-resource-typer.exclude_columns', []);
        $outputPath = config('api-resource-typer.output_path');
        
        // Filter out excluded columns
        $filteredColumns = array_filter($columnTypes, function($key) use ($excludeColumns) {
            return !in_array($key, $excludeColumns);
        }, ARRAY_FILTER_USE_KEY);

        $interfaceName = $modelName . 'Resource';
        $fileName = $outputPath . '/' . $interfaceName . '.ts';

        $content = $this->buildInterfaceContent($interfaceName, $filteredColumns);
        
        File::put($fileName, $content);
        $this->info("📝 Generated: {$fileName}");
    }

    /**
     * Build TypeScript interface content
     */
    protected function buildInterfaceContent(string $interfaceName, array $columnTypes): string
    {
        $content = "// Auto-generated TypeScript interface\n";
        $content .= "// Generated at: " . now()->toDateTimeString() . "\n\n";
        $content .= "export interface {$interfaceName} {\n";

        foreach ($columnTypes as $column => $type) {
            $content .= "  {$column}: {$type};\n";
        }

        $content .= "}\n\n";
        
        // Add API response types
        $content .= "export interface {$interfaceName}Collection {\n";
        $content .= "  data: {$interfaceName}[];\n";
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
        $content .= "}\n\n";

        $content .= "export interface {$interfaceName}Response {\n";
        $content .= "  data: {$interfaceName};\n";
        $content .= "}\n";

        return $content;
    }
}