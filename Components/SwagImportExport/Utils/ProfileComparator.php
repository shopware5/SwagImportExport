<?php

namespace Shopware\Components\SwagImportExport\Utils;

class ProfileComparator
{
    /**
     * @param $name
     * @return string
     * @throws \Exception
     */
    private function getDefaultProfileByType($name)
    {
        return TreeHelper::getDefaultTreeByProfileType($name);
    }

    /**
     * Compare the Arrays and returns an array that contains the missing fields
     *
     * @param $defaultProfile array
     * @param $currentProfile array
     * @return array
     */
    private function compareArrays($defaultProfile, $currentProfile)
    {
        return array_values(
            array_diff(
                $defaultProfile,
                $currentProfile
            )
        );
    }

    /**
     * This method converts the stdClass obj into an multidimensional Array
     *
     * @param $object
     * @return array
     */
    private function objectToArray($object)
    {
        if(is_object($object)) {
            $object = (array) $object;
        }

        if(is_array($object)) {
            $returnValue = array();
            foreach($object as $key => $val) {
                $returnValue[$key] = $this->objectToArray($val);
            }
        } else {
            $returnValue = $object;
        }
           
        return $returnValue;
    }

    /**
     * This function filter unnecessary values and flatten the array
     *
     * @param $array
     * @return array
     */
    private function getRequiredValues($array)
    {
        $returnValue = array();
        foreach($array as $key => $value) {
            if(is_array($value)){
                $returnValue = array_merge($returnValue, $this->getRequiredValues($value));
            } else {
                if($key == 'shopwareField' && $value != ""){
                    $returnValue[] = $value;
                }
            }
        }
        return $returnValue;
    }

    /**
     * @param \Shopware\CustomModels\ImportExport\Profile $profileEntity
     * @return array
     */
    public function compareProfile($profileEntity)
    {
        $returnValue = array();

        $defaultProfile = $this->getRequiredValues($this->objectToArray(json_decode($this->getDefaultProfileByType($profileEntity->getType()))));
        $currentProfile = $this->getRequiredValues($this->objectToArray(json_decode($profileEntity->getTree())));
        $returnValue['create'] = $this->compareArrays($defaultProfile, $currentProfile);
        
        $defaultProfile = ProfileComparatorHelper::getRequiredUpdateNodesByName($profileEntity->getType());
        $returnValue['update'] = $this->compareArrays($defaultProfile, $currentProfile);
        
        return $returnValue;
    }
}