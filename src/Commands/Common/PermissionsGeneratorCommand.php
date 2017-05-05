<?php

namespace InfyOm\Generator\Commands\Common;

use InfyOm\Generator\Commands\BaseCommand;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\PermissionsGenerator;

class PermissionsGeneratorCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'infyom.scaffold:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the permission seeder';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandData = new CommandData($this, CommandData::$COMMAND_TYPE_API);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $permissionsGenerator = new PermissionsGenerator($this->commandData);
        $permissionsGenerator->generate();

        $this->performPostActions();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array_merge(parent::getOptions(), []);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array_merge(parent::getArguments(), []);
    }
}
