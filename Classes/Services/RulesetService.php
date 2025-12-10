<?php

namespace Wlb\Crowdsourcing\Services;

class RulesetService
{
    private $rulesetXml;
    
    public function __construct()
    {
        $rulesetPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('rulesetPath');
        $this->rulesetXml = simplexml_load_file($rulesetPath);
    }

    /**
     * Gets the ruleset definitions
     *
     * @return array
     */
    public function getRulesetDefinitions(): array
    {
        $metadataDefinitions = [];

        // load metadata to array
        foreach ($this->rulesetXml->declaration->key as $key) {
            $metadataId = (string) $key->attributes()->{'id'};
            $metadataDefinitions[$metadataId]['label'] = (string) $key->label;
            $metadataDefinitions[$metadataId]['type'] = (string) $key->codomain->attributes()->{'type'};
            $metadataDefinitions[$metadataId]['pattern'] = (string) $key->pattern;
            foreach ($key->option as $option) {
                $optionValue = (string) $option->attributes()->{'value'};
                $metadataDefinitions[$metadataId]['options'][$optionValue] = (string) $option->label;
            }
            foreach ($key->key as $secondKey) {
                $metadataId = (string) $secondKey->attributes()->{'id'};
                $metadataDefinitions[$metadataId]['label'] = (string) $secondKey->label;
                $metadataDefinitions[$metadataId]['type'] = (string) $secondKey->codomain->attributes()->{'type'};
                $metadataDefinitions[$metadataId]['pattern'] = (string) $secondKey->pattern;
                foreach ($secondKey->option as $option) {
                    $optionValue = (string) $option->attributes()->{'value'};
                    $metadataDefinitions[$metadataId]['options'][$optionValue] = (string) $option->label;
                }
                foreach ($secondKey->key as $thirdKey) {
                    $metadataId = (string) $thirdKey->attributes()->{'id'};
                    $metadataDefinitions[$metadataId]['label'] = (string) $thirdKey->label;
                    $metadataDefinitions[$metadataId]['type'] = (string) $thirdKey->codomain->attributes()->{'type'};
                    $metadataDefinitions[$metadataId]['pattern'] = (string) $thirdKey->pattern;
                    foreach ($thirdKey->option as $option) {
                        $optionValue = (string) $option->attributes()->{'value'};
                        $metadataDefinitions[$metadataId]['options'][$optionValue] = (string) $option->label;
                    }
                }
            }
        }

        return $metadataDefinitions;
    }

    /**
     * Gets the configuration from the ruleset
     *
     * @return array
     */
    public function getConfigurationFromRuleset(): array
    {
        $configurationRuleset = [];
        $metadataDefinitions = $this->getRulesetDefinitions();
        
        foreach ($this->rulesetXml->declaration->division as $division) {
            if ($division->attributes()->{'processTitle'}) {
                $documentType = (string) $division->attributes()->{'id'};
                $configurationRuleset[$documentType] = [];

            }
        }

        foreach ($this->rulesetXml->correlation->restriction as $restriction) {
            // Each restriction defines a doc type
            $divisionName = (string) $restriction->attributes()->{'division'};
            if (array_key_exists($divisionName, $configurationRuleset)) {
                foreach ($restriction as $permit) {
                    if ((string) $permit->attributes()->{'key'}) {
                        $permitKey = (string) $permit->attributes()->{'key'};
                        $configurationRuleset[$divisionName][$permitKey]['label'] = $metadataDefinitions[$permitKey]['label'];
                        if ($minOccurs = (string) $permit->attributes()->{'minOccurs'}) {
                            $configurationRuleset[$divisionName][$permitKey]['minOccurs'] = $minOccurs;
                        }
                        if ($maxOccurs = (string) $permit->attributes()->{'maxOccurs'}) {
                            $configurationRuleset[$divisionName][$permitKey]['maxOccurs'] = $maxOccurs;
                        }
                        if ($inputType = $metadataDefinitions[$permitKey]['type']) {
                            $inputType = $this->convertInputType($inputType);
                            $configurationRuleset[$divisionName][$permitKey]['inputtype'] = $inputType;
                        }
                        if ($options = $metadataDefinitions[$permitKey]['options']) {
                            $configurationRuleset[$divisionName][$permitKey]['options'] = $options;
                        }
                    }
                    foreach ($permit->permit as $secondPermit) {
                        if ((string) $secondPermit->attributes()->{'key'}) {
                            $secondPermitKey = (string) $secondPermit->attributes()->{'key'};
                            $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['label'] = $metadataDefinitions[$secondPermitKey]['label'];
                            if ($minOccurs = (string) $secondPermit->attributes()->{'minOccurs'}) {
                                $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['minOccurs'] = $minOccurs;
                            }
                            if ($maxOccurs = (string) $secondPermit->attributes()->{'maxOccurs'}) {
                                $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['maxOccurs'] = $maxOccurs;
                            }
                            if ($inputType = $metadataDefinitions[$secondPermitKey]['type']) {
                                $inputType = $this->convertInputType($inputType);
                                $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['inputtype'] = $inputType;
                            }
                            if ($options = $metadataDefinitions[$secondPermitKey]['options']) {
                                $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['options'] = $options;
                            }
                        }

                        foreach ($secondPermit->permit as $thirdPermit) {
                            if ((string) $thirdPermit->attributes()->{'key'}) {
                                $thirdPermitKey = (string) $thirdPermit->attributes()->{'key'};
                                $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['children'][$thirdPermitKey]['label'] = $metadataDefinitions[$thirdPermitKey]['label'];
                                if ($minOccurs = (string) $thirdPermit->attributes()->{'minOccurs'}) {
                                    $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['children'][$thirdPermitKey]['minOccurs'] = $minOccurs;
                                }
                                if ($maxOccurs = (string) $thirdPermit->attributes()->{'maxOccurs'}) {
                                    $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['children'][$thirdPermitKey]['maxOccurs'] = $maxOccurs;
                                }
                                if ($inputType = $metadataDefinitions[$thirdPermitKey]['type']) {
                                    $inputType = $this->convertInputType($inputType);
                                    $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['children'][$thirdPermitKey]['inputtype'] = $inputType;
                                }
                                if ($options = $metadataDefinitions[$thirdPermitKey]['options']) {
                                    $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['children'][$thirdPermitKey]['options'] = $options;
                                }
                            }

                        }
                    }
                }
            }
        }
        
        return $configurationRuleset;
    }

    /**
     * Converts the given input type to a corresponding HTML input type.
     *
     * @param string $inputType The input type to be converted.
     * @return string The corresponding HTML input type, or the original input type if no conversion is defined.
     */
    public function convertInputType($inputType): string
    {
        switch ($inputType) {
            case 'boolean':
                return 'checkbox';
        }
        return $inputType;
    }



}