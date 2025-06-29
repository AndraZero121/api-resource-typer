<?php

namespace AndraZero121\ApiResourceTyper\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class GenerateTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'generate:api-types {--model=} {--controller=} {--output-type=ts}';

    /**
     * The console command description.
     */
    protected $description = 'Generate TypeScript interfaces from API Resource Controllers and Models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $outputType = $this->option('output-type') ?? 'ts';
        $this->info('🚀 Starting API Resource Types generation...');

        // Create output directory if it doesn't exist
        $outputPath = config('api-resource-typer.output_path');
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
            $this->info("📁 Created directory: {$outputPath}");
        }

        // Get specific model or all models
        if ($this->option('model')) {
            $this->generateForModel($this->option('model'), $outputType);
        } else {
            $this->generateForAllModels($outputType);
        }

        $this->info('✅ Type generation completed!');
    }

    /**
     * Generate types for all models
     */
    protected function generateForAllModels($outputType = 'ts')
    {
        $modelsPath = config('api-resource-typer.models_path');
        if (!File::exists($modelsPath)) {
            $this->error("Models directory not found: {$modelsPath}");
            return;
        }
        $modelFiles = File::glob($modelsPath . '/*.php');
        foreach ($modelFiles as $modelFile) {
            $modelName = basename($modelFile, '.php');
            $this->generateForModel($modelName, $outputType);
        }
    }

    /**
     * Generate types for specific model
     */
    protected function generateForModel(string $modelName, $outputType = 'ts')
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
            $columns = Schema::getColumnListing($tableName);
            $columnTypes = [];
            foreach ($columns as $column) {
                $columnType = Schema::getColumnType($tableName, $column);
                $columnTypes[$column] = $this->mapToOutputType($columnType, $outputType, $tableName, $column);
            }
            $this->generateInterfaceFile($modelName, $columnTypes, $outputType);
        } catch (\Exception $e) {
            $this->error("Error processing {$modelName}: " . $e->getMessage());
        }
    }

    /**
     * Map PHP/Database types to TypeScript or JavaScript types
     */
    protected function mapToOutputType(string $type, string $outputType = 'ts', $table = null, $column = null): string
    {
        $tsMappings = config('api-resource-typer.type_mappings');
        $jsMappings = [
            'string' => 'string',
            'integer' => 'number',
            'int' => 'number',
            'float' => 'number',
            'double' => 'number',
            'decimal' => 'number',
            'boolean' => 'boolean',
            'bool' => 'boolean',
            'array' => 'Array',
            'object' => 'object',
            'datetime' => 'Date',
            'timestamp' => 'Date',
            'date' => 'Date',
            'time' => 'string',
            'json' => 'object',
            'text' => 'string',
            'longtext' => 'string',
            'mediumtext' => 'string',
            'enum' => 'string',
        ];
        // Cek detail kolom dari DBAL jika table & column diberikan
        if ($table && $column && Schema::hasColumn($table, $column)) {
            try {
                $col = DB::getDoctrineColumn($table, $column);
                if ($col->getType()->getName() === 'decimal') {
                    return 'number';
                }
                if ($col->getType()->getName() === 'enum') {
                    return 'string';
                }
                if ($col->getNotnull() === false) {
                    // nullable, tambahkan |null di ts, atau null di js
                    $baseType = $outputType === 'js' ? ($jsMappings[$type] ?? 'any') : ($tsMappings[$type] ?? 'any');
                    return $outputType === 'js' ? $baseType : ($baseType . ' | null');
                }
            } catch (\Throwable $e) {}
        }
        if ($outputType === 'js') {
            return $jsMappings[$type] ?? 'any';
        }
        return $tsMappings[$type] ?? 'any';
    }

    /**
     * Generate interface file for TypeScript or JavaScript
     */
    protected function generateInterfaceFile(string $modelName, array $columnTypes, string $outputType = 'ts')
    {
        $excludeColumns = config('api-resource-typer.exclude_columns', []);
        $outputPath = config('api-resource-typer.output_path');
        $filteredColumns = array_filter($columnTypes, function($key) use ($excludeColumns) {
            return !in_array($key, $excludeColumns);
        }, ARRAY_FILTER_USE_KEY);
        $interfaceName = $modelName . 'Resource';
        $fileName = $outputPath . '/' . $interfaceName . '.' . $outputType;
        $content = $this->buildInterfaceContent($interfaceName, $filteredColumns, $outputType);
        File::put($fileName, $content);
        $this->info("📝 Generated: {$fileName}");
    }

    /**
     * Build interface content for TypeScript or JavaScript
     */
    protected function buildInterfaceContent(string $interfaceName, array $columnTypes, string $outputType = 'ts'): string
    {
        $content = "// Auto-generated " . strtoupper($outputType) . " interface\n";
        $content .= "// Generated at: " . now()->toDateTimeString() . "\n\n";
        if ($outputType === 'ts') {
            $content .= "export interface {$interfaceName} {\n";
            foreach ($columnTypes as $column => $type) {
                $content .= "  {$column}: {$type};\n";
            }
            $content .= "}\n\n";
            $content .= "export interface {$interfaceName}Collection {\n";
            $content .= "  data: {$interfaceName}[];\n";
            $content .= "  links?: {\n    first: string;\n    last: string;\n    prev: string | null;\n    next: string | null;\n  };\n";
            $content .= "  meta?: {\n    current_page: number;\n    last_page: number;\n    per_page: number;\n    total: number;\n  };\n";
            $content .= "}\n\n";
            $content .= "export interface {$interfaceName}Response {\n  data: {$interfaceName};\n}\n";
        } else {
            $content .= "/**\n * @typedef {Object} {$interfaceName}\n";
            foreach ($columnTypes as $column => $type) {
                $content .= " * @property {{$type}} {$column}\n";
            }
            $content .= " */\n\n";
            $content .= "/**\n * @typedef {Object} {$interfaceName}Collection\n * @property {{$interfaceName}[]} data\n * @property {{first: string, last: string, prev: string|null, next: string|null}} [links]\n * @property {{current_page: number, last_page: number, per_page: number, total: number}} [meta]\n */\n\n";
            $content .= "/**\n * @typedef {Object} {$interfaceName}Response\n * @property {{$interfaceName}} data\n */\n";
        }
        return $content;
    }
}