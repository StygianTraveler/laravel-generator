<?php

namespace InfyOm\Generator\Commands;

use Illuminate\Console\Command;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\API\APIControllerGenerator;
use InfyOm\Generator\Generators\API\APIRequestGenerator;
use InfyOm\Generator\Generators\API\APIRoutesGenerator;
use InfyOm\Generator\Generators\API\APITestGenerator;
use InfyOm\Generator\Generators\MigrationGenerator;
use InfyOm\Generator\Generators\ModelGenerator;
use InfyOm\Generator\Generators\RepositoryGenerator;
use InfyOm\Generator\Generators\LocalizationGenerator;
use InfyOm\Generator\Generators\PermissionsGenerator;
use InfyOm\Generator\Generators\RepositoryTestGenerator;
use InfyOm\Generator\Generators\Scaffold\ControllerGenerator;
use InfyOm\Generator\Generators\Scaffold\MenuGenerator;
use InfyOm\Generator\Generators\Scaffold\RequestGenerator;
use InfyOm\Generator\Generators\Scaffold\RoutesGenerator;
use InfyOm\Generator\Generators\Scaffold\ViewGenerator;
use InfyOm\Generator\Generators\TestTraitGenerator;
use InfyOm\Generator\Generators\VueJs\ControllerGenerator as VueJsControllerGenerator;
use InfyOm\Generator\Generators\VueJs\ModelJsConfigGenerator;
use InfyOm\Generator\Generators\VueJs\RoutesGenerator as VueJsRoutesGenerator;
use InfyOm\Generator\Generators\VueJs\ViewGenerator as VueJsViewGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RollbackGeneratorCommand extends Command
{
    /**
     * The command Data.
     *
     * @var CommandData
     */
    public $commandData;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'infyom:rollback';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback a full CRUD API and Scaffold for given model';

    /**
     * @var Composer
     */
    public $composer;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->composer = app()['composer'];
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        if (!in_array($this->argument('type'), [
            CommandData::$COMMAND_TYPE_API,
            CommandData::$COMMAND_TYPE_SCAFFOLD,
            CommandData::$COMMAND_TYPE_API_SCAFFOLD,
            CommandData::$COMMAND_TYPE_VUEJS,
        ])) {
            $this->error('invalid rollback type');
        }

        $this->commandData = new CommandData($this, $this->argument('type'));
        $this->commandData->config->mName = $this->commandData->modelName = $this->argument('model');

        $this->commandData->config->init($this->commandData, ['tableName', 'prefix', 'skip']);

        if(!$this->isSkip('migration')) {
            $migrationGenerator = new MigrationGenerator($this->commandData);
            $migrationGenerator->rollback();
        }

        if(!$this->isSkip('model')) {
            $modelGenerator = new ModelGenerator($this->commandData);
            $modelGenerator->rollback();
        }

        if(!$this->isSkip('repository')) {
            $repositoryGenerator = new RepositoryGenerator($this->commandData);
            $repositoryGenerator->rollback();
        }

        if(!$this->isSkip('localization')) {
            $localizationGenerator = new localizationGenerator($this->commandData);
            $localizationGenerator->rollback();
        }

        if(!$this->isSkip('permissions')) {
            $permissionsGenerator = new PermissionsGenerator($this->commandData);
            $permissionsGenerator->rollback();        
        }

        if(!$this->isSkip('requests') && !$this->isSkip('api_requests')) {
            $requestGenerator = new APIRequestGenerator($this->commandData);
            $requestGenerator->rollback();
        }

        if(!$this->isSkip('controllers') && !$this->isSkip('api_controllers')) {
            $controllerGenerator = new APIControllerGenerator($this->commandData);
            $controllerGenerator->rollback();
        }

        if(!$this->isSkip('routes') && !$this->isSkip('api_routes')) {
            $routesGenerator = new APIRoutesGenerator($this->commandData);
            $routesGenerator->rollback();
        }

        if(!$this->isSkip('requests') && !$this->isSkip('scaffold_requests')) {
            $requestGenerator = new RequestGenerator($this->commandData);
            $requestGenerator->rollback();
        }

        if(!$this->isSkip('controllers') && !$this->isSkip('scaffold_controllers')) {
            $controllerGenerator = new ControllerGenerator($this->commandData);
            $controllerGenerator->rollback();
        }

        if(!$this->isSkip('views')) {
            $viewGenerator = new ViewGenerator($this->commandData);
            $viewGenerator->rollback();
        }

        if(!$this->isSkip('routes') && !$this->isSkip('scaffold_routes')) {
            $routeGenerator = new RoutesGenerator($this->commandData);
            $routeGenerator->rollback();
        }

        if(!$this->isSkip('controllers')) {
            $controllerGenerator = new VueJsControllerGenerator($this->commandData);
            $controllerGenerator->rollback();
        }

        if(!$this->isSkip('routes')) {
            $routesGenerator = new VueJsRoutesGenerator($this->commandData);
            $routesGenerator->rollback();
        }

        if(!$this->isSkip('views')) {
            $viewGenerator = new VueJsViewGenerator($this->commandData);
            $viewGenerator->rollback();
        }

        $modelJsConfigGenerator = new ModelJsConfigGenerator($this->commandData);
        $modelJsConfigGenerator->rollback();

        if (!$this->isSkip('tests') && $this->commandData->getAddOn('tests')) {

            $repositoryTestGenerator = new RepositoryTestGenerator($this->commandData);
            $repositoryTestGenerator->rollback();

            $testTraitGenerator = new TestTraitGenerator($this->commandData);
            $testTraitGenerator->rollback();

            $apiTestGenerator = new APITestGenerator($this->commandData);
            $apiTestGenerator->rollback();
        }

        if ($this->commandData->config->getAddOn('menu.enabled')) {

            if(!$this->isSkip('menu')) {
                $menuGenerator = new MenuGenerator($this->commandData);
                $menuGenerator->rollback();
            }
        }

        if(!$this->isSkip('dump-autoload')) {
            $this->info('Generating autoload files');
            $this->composer->dumpOptimized();
        }
    }

    public function isSkip($skip)
    {
        if ($this->commandData->getOption('skip')) {
            return in_array($skip, (array) $this->commandData->getOption('skip'));
        }

        return false;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            ['tableName', null, InputOption::VALUE_REQUIRED, 'Table Name'],
            ['prefix', null, InputOption::VALUE_REQUIRED, 'Prefix for all files'],
            ['skip', null, InputOption::VALUE_REQUIRED, 'Skip Specific Items to Rollback'],
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['model', InputArgument::REQUIRED, 'Singular Model name'],
            ['type', InputArgument::REQUIRED, 'Rollback type: (api / scaffold / scaffold_api)'],
        ];
    }
}
