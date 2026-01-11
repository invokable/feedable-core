<?php

declare(strict_types=1);

namespace Revolution\Feedable\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * ```
 * vendor/bin/testbench make:driver Sample
 * ```
 *
 * @internal
 */
#[AsCommand(name: 'make:driver')]
class DriverMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     */
    protected $name = 'make:driver';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Feedable driver and Service Provider';

    public function handle(): void
    {
        parent::handle();

        $name = $this->getNameInput();

        // Create Service Provider at the same time
        $this->call(ProviderMakeCommand::class, [
            'name' => $name,
        ]);

        // Create Test at the same time
        $this->call(TestMakeCommand::class, [
            'name' => $name,
        ]);
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/feedable-driver.stub');
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

        return __DIR__.'/..'.str_replace('\\', '/', $name).'Driver.php';
    }
}
