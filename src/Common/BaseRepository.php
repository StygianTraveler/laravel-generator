<?php

namespace InfyOm\Generator\Common;

use Exception;

abstract class BaseRepository extends \Prettus\Repository\Eloquent\BaseRepository
{
    public function findWithoutFail($id, $columns = ['*'])
    {
        try {
            return $this->find($id, $columns);
        } catch (Exception $e) {
            return;
        }
    }

    public function create(array $attributes)
    {
        // Have to skip presenter to get a model not some data
        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);
        $model = parent::create($attributes);
        $this->skipPresenter($temporarySkipPresenter);

        $model = $this->updateRelations($model, $attributes);
        $model->save();

        return $this->parserResult($model);
    }

    public function update(array $attributes, $id)
    {
        // Have to skip presenter to get a model not some data
        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);
        $model = parent::update($attributes, $id);
        $this->skipPresenter($temporarySkipPresenter);

        $model = $this->updateRelations($model, $attributes);
        $model->save();

        return $this->parserResult($model);
    }

    public function updateRelations($model, $attributes)
    {
        foreach ($attributes as $key => $val) {
            if (isset($model) &&
                method_exists($model, $key) &&
                isset($model->fillableRelations) &&
                in_array($key, $model->fillableRelations) && 
                is_a(@$model->$key(), 'Illuminate\Database\Eloquent\Relations\Relation')
            ) {
                $methodClass = get_class($model->$key($key));
                switch ($methodClass) {
                    case 'Illuminate\Database\Eloquent\Relations\BelongsToMany':
                        $new_values = array_get($attributes, $key, []);
                        if (array_search('', $new_values) !== false) {
                            unset($new_values[array_search('', $new_values)]);
                        }
                        $model->$key()->sync(array_values($new_values));
                        break;
                    case 'Illuminate\Database\Eloquent\Relations\BelongsTo':
                        $model_key = $model->$key()->getForeignKey();
                        $new_value = array_get($attributes, $key, null);
                        $new_value = $new_value == '' ? null : $new_value;
                        $model->$model_key = $new_value;
                        break;
                    case 'Illuminate\Database\Eloquent\Relations\HasOne':
                        $newValues = array_get($attributes, $key, []);
                        if($model->$key == null) {
                            $model->$key()->create($newValues);
                        }
                        else {
                            $model->$key->fill($newValues);
                            $model->$key->save();
                        }
                        break;
                    case 'Illuminate\Database\Eloquent\Relations\HasOneOrMany':
                        break;
                    case 'Illuminate\Database\Eloquent\Relations\HasMany':


                        $newValues = array_get($attributes, $key, []);

                        //Find list of ids
                        if(array_has($newValues, '0.id')) {
                            //List of objects, extract the id of each
                            $ids = array_pluck($newValues, 'id');
                        }
                        else {
                            //Assuming newValues is just a list of ids
                            $ids = $newValues;
                        }

                        //Update existing child elements
                        foreach($model->$key as $element) {
                            $inputKey = array_search($element->id, $ids);
                            if($inputKey !== false) {
                                //element is already in the database, update it if we have data
                                if(is_array($newValues[$inputKey])) {
                                    $element->fill($newValues[$inputKey]);
                                    $element->save();
                                    $this->updateRelations($element, $newValues[$inputKey]);
                                    unset($newValues[$inputKey]);
                                }
                            }
                            else {
                                //element was removed in the form, delete it from the database
                                $element->delete();
                            }
                        }

                        //Remaining elements in the array are new, create them if we have data
                        foreach($newValues as $elementData) {
                            if(is_array($elementData)) {
                                $element = $model->$key()->create($elementData);
                                $this->updateRelations($element, $elementData);
                            }
                        }

                        break;
                }
            }
        }

        return $model;
    }
}
