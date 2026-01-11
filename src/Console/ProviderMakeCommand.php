<?php

declare(strict_types=1);

namespace Revolution\Feedable\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * ```
 * vendor/bin/testbench make:feedable-provider Sample
 * ```
 *
 * @internal
 */
#[AsCommand(name: 'make:feedable-provider')]
class ProviderMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     */
    protected $name = 'make:feedable-provider';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Feedable Service Provider';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/feedable-provider.stub');
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
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return __DIR__.'/..'.str_replace('\\', '/', $name).'ServiceProvider.php';
    }
}
