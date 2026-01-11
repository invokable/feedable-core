<?php

declare(strict_types=1);

namespace Revolution\Feedable\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * ```
 * vendor/bin/testbench make:feedable-test Sample
 * ```
 *
 * @internal
 */
#[AsCommand(name: 'make:feedable-test')]
class TestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     */
    protected $name = 'make:feedable-test';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Feedable test';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/feedable-test.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        return 'Revolution\Feedable';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Drivers\\'.$this->getNameInput();
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        $name = $this->getNameInput();

        return __DIR__.'/../../tests/Feature/Drivers/'.str_replace('\\', '/', $name).'Test.php';
    }
}
