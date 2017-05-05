<?php

namespace InfyOm\Generator\Generators;

use Illuminate\Support\Str;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\BaseGenerator;

class PermissionsGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $permissionsContents;

    /** @var string */
    private $permissionsTemplate;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = base_path('database/seeds/PermissionSeeder.php');

        $this->permissionsContents = file_get_contents($this->path);

        $this->permissionsTemplate = get_template('permissions', 'laravel_generator');

        $this->permissionsTemplate = fill_template($this->commandData->dynamicVars, $this->permissionsTemplate);
    }

    public function generate()
    {
        $this->permissionsContents = str_replace("#GENERATED-INSERT-HERE#", $this->permissionsTemplate, $this->permissionsContents);

        file_put_contents($this->path, $this->permissionsContents);
        $this->commandData->commandComment("\n".$this->commandData->config->mCamelPlural.' permissions added.');
    }

    public function rollback()
    {
        if (Str::contains($this->permissionsContents, $this->permissionsTemplate)) {
            file_put_contents($this->path, str_replace($this->permissionsTemplate, '#GENERATED-INSERT-HERE#', $this->permissionsContents));
            $this->commandData->commandComment('permissions deleted');
        }
    }
}
