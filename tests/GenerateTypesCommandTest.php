<?php

namespace Andrazero121\ApiResourceTyper\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;
use Andrazero121\ApiResourceTyper\Providers\ApiResourceTyperServiceProvider;

class GenerateTypesCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ApiResourceTyperServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test config
        config([
            'api-resource-typer.output_path' => $this->getTempPath(),
            'api-resource-typer.auto_generate' => true,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->getTempPath())) {
            File::deleteDirectory($this->getTempPath());
        }
        
        parent::tearDown();
    }

    public function test_command_exists()
    {
        $this->assertTrue(
            collect($this->app['Illuminate\Contracts\Console\Kernel']->all())
                ->has('generate:api-types')
        );
    }

    public function test_generates_output_directory()
    {
        $this->artisan('generate:api-types');
        
        $this->assertTrue(File::exists($this->getTempPath()));
    }

    public function test_type_inference()
    {
        $trait = new class {
            use \Andrazero121\ApiResourceTyper\Traits\ApiResourceTyper;
            
            public function testInferTypes($data) {
                return $this->inferTypesFromData($data);
            }
            
            public function testGetType($value) {
                return $this->getTypeScriptType($value);
            }
        };

        // Test basic types
        $this->assertEquals('string', $trait->testGetType('hello'));
        $this->assertEquals('number', $trait->testGetType(123));
        $this->assertEquals('boolean', $trait->testGetType(true));
        $this->assertEquals('Date', $trait->testGetType('2025-06-29 10:30:00'));
        $this->assertEquals('any[]', $trait->testGetType([]));
        $this->assertEquals('string[]', $trait->testGetType(['a', 'b', 'c']));
        
        // Test complex data
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true,
            'created_at' => '2025-06-29 10:30:00',
            'tags' => ['admin', 'user'],
            'meta' => ['key' => 'value'],
        ];
        
        $types = $trait->testInferTypes($data);
        
        $this->assertEquals('number', $types['id']);
        $this->assertEquals('string', $types['name']);
        $this->assertEquals('string', $types['email']);
        $this->assertEquals('boolean', $types['active']);
        $this->assertEquals('Date', $types['created_at']);
        $this->assertEquals('string[]', $types['tags']);
        $this->assertEquals('object', $types['meta']);
    }

    protected function getTempPath(): string
    {
        return sys_get_temp_dir() . '/api-resource-typer-tests';
    }
}