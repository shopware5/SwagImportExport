<?php

namespace Shopware\Components\SwagImportExport\Validators;

abstract class Validator
{
    abstract public function checkRequiredFields($record);

    public function prepareInitialData($record)
    {
        $record = array_filter(
            $record,
            function($value) {
                return $value !== '';
            }
        );

        return $record;
    }
}