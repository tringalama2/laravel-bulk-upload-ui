<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'make:bulk-importer')]
class MakeBulkImporterCommand extends GeneratorCommand
{
    protected $name = 'make:bulk-importer';

    protected $description = 'Create a new bulk upload Importer class for a model';

    protected $type = 'Importer';

    public function handle(): ?bool
    {
        $result = parent::handle();

        $this->components->info(sprintf(
            'Register it in config/laravel-bulk-upload-ui.php: \'%s\' => \\%s::class',
            Str::of($this->getNameInput())->kebab()->value(),
            $this->qualifyClass($this->getNameInput()),
        ));

        return $result;
    }

    protected function getStub(): string
    {
        return __DIR__.'/../../../resources/stubs/bulk-importer.stub';
    }

    protected function getNameInput(): string
    {
        // Always the bare class name, even when --model is fully qualified (e.g.
        // "App\Models\Contact"): GeneratorCommand::qualifyClass() short-circuits and
        // ignores getDefaultNamespace() whenever the name it's given already starts with
        // the app's root namespace, which a namespaced model argument would otherwise do.
        return class_basename(trim((string) $this->argument('model'))).'Importer';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\BulkImport';
    }

    protected function buildClass($name): string
    {
        $modelClass = trim((string) $this->argument('model'));
        $modelClass = str_contains($modelClass, '\\')
            ? $modelClass
            : $this->laravel->getNamespace().'Models\\'.$modelClass;

        $replace = [
            '{{ modelNamespace }}' => $modelClass,
            '{{ modelClass }}' => class_basename($modelClass),
        ];

        return str_replace(array_keys($replace), array_values($replace), parent::buildClass($name));
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model to generate an Importer for, e.g. Contact or App\\Models\\Contact'],
        ];
    }
}
