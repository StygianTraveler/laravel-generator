<?php

namespace InfyOm\Generator\Generators;

use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\TableFieldsGenerator;

class LocalizationGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;
    private $fileName;
    private $table;

    /**
     * ModelGenerator constructor.
     *
     * @param \InfyOm\Generator\Common\CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathLocalization;
        $this->fileName = $this->commandData->config->mSnake.'.php';
    }

    public function generate()
    {
        $templateData = get_template('localization', 'laravel-generator');

        $templateData = $this->fillTemplate($templateData);

		foreach($this->commandData->config->locales as $lang) {
			FileUtil::createFile($this->path . $lang . '/', $this->fileName, $templateData);

			$this->commandData->commandComment("\nLocalization file created: ");
			$this->commandData->commandInfo($lang . "/" . $this->fileName);
		}
    }

    private function fillTemplate($templateData)
    {
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $fields = [];

        foreach ($this->commandData->fields as $field) {
            if ($field->inForm || $field->inIndex) {
                $fields[] = "'".$field->name."' => '".$field->fieldTitle."'";
            }
        }
        
        $templateData = str_replace('$LOCALIZATION_FIELDS$', implode(','.infy_nl_tab(1, 1), $fields), $templateData);

        return $templateData;
    }

    public function rollback()
    {
		foreach($this->commandData->config->locales as $lang) {
			if ($this->rollbackFile($this->path . $lang . '/', $this->fileName)) {
				$this->commandData->commandComment('Localization file deleted: '.$lang.'/'.$this->fileName);
			}
		}
    }
}
