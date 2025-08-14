<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use Wlb\Crowdsourcing\Domain\Model\MetadataConfiguration;
use Wlb\Crowdsourcing\Domain\Repository\MetadataConfigurationRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ConfigurationController extends ActionController
{
    protected ModuleTemplate $moduleTemplate;

    public function __construct(
        private readonly MetadataConfigurationRepository $metadataConfigurationRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory
    )
    {
    }

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
    }

    public function indexAction(): ResponseInterface
    {
        $rulesetService = new \Wlb\Crowdsourcing\Services\RulesetService();
        $configurationRuleset = $rulesetService->getConfigurationFromRuleset();
        $metadataDefinitions = $rulesetService->getRulesetDefinitions();

        // compare db with ruleset in both directions
        $rulesetAdded = [];
        $rulesetRemoved = [];
        $config = $configurationRuleset;
        $missingDocType = [];

        // get db saved config
        $queryResult = $this->metadataConfigurationRepository->findAll();

        if ($queryResult->count() !== 0) {
            /** @var MetadataConfiguration $dbConfiguration */
            $dbConfiguration = $queryResult->getFirst();
            $dbConfigArray = json_decode($dbConfiguration->getJson(), true);

            foreach ($dbConfigArray as $key => $dbDocumentConfiguration) {
                if (array_key_exists($key, $configurationRuleset)) {
                    $rulesetAdded[$key] = array_diff_key($configurationRuleset[$key], $dbDocumentConfiguration);
                    $rulesetRemoved[$key] = array_diff_key($dbDocumentConfiguration, $configurationRuleset[$key]);
                } else {
                    $missingDocType[$key] = $key;
                    // TODO: Remove dbDocumentConfiguration for the given key, if its not existing anymore??
                }
            }

            foreach ($rulesetRemoved as $docType => $metadataRemoved) {
                foreach ($metadataRemoved as $metadataId => $metadataConfig) {
                    $rulesetRemoved[$docType][$metadataId]['label'] = $metadataDefinitions[$metadataId];
                }
            }

            $this->moduleTemplate->assign('missingDocType', $missingDocType);
            $this->moduleTemplate->assign('rulesetAdded', $rulesetAdded);
            $this->moduleTemplate->assign('rulesetRemoved', $rulesetRemoved);
            $this->moduleTemplate->assign('dbConfig', $dbConfigArray);

            if (!empty($rulesetAdded)) {
                // remove options from dbConfigArray because only the keys are saved in the db
                $this->removeOptionsRecursive($dbConfigArray);
                $config = array_replace_recursive($dbConfigArray, $configurationRuleset);
            } else {
                $config = $dbConfigArray;
            }
        }
        $this->moduleTemplate->assign('rulesetConfig', $configurationRuleset);
        $this->moduleTemplate->assign('config', $config);

        return $this->moduleTemplate->renderResponse('Backend/Configuration/Index');;
    }

    /**
     * @return ResponseInterface
     * @throws IllegalObjectTypeException
     * @throws NoSuchArgumentException
     * @throws StopActionException
     * @throws UnknownObjectException
     */
    public function saveAction(): ResponseInterface
    {

        $metadataConfiguration = $this->request->getArgument('metadata');

        $queryResult = $this->metadataConfigurationRepository->findAll();

        if ($queryResult->count() === 0) {
            $metadataConfigurationObject = new MetadataConfiguration();
            $metadataConfigurationObject->setName("Name");
            $metadataConfigurationObject->setJson(json_encode($metadataConfiguration));

            $this->metadataConfigurationRepository->add($metadataConfigurationObject);
        } else {
            /** @var MetadataConfiguration $metadataConfigurationObject */
            $metadataConfigurationObject = $queryResult->getFirst();
            $metadataConfigurationObject->setName("Name");
            $metadataConfigurationObject->setJson(json_encode($metadataConfiguration));

            $this->metadataConfigurationRepository->update($metadataConfigurationObject);
        }


        return $this->redirect('index');
    }
    function removeOptionsRecursive(&$array) {
        if (!is_array($array)) {
            return;
        }

        unset($array['options']);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->removeOptionsRecursive($value);

                if (isset($value['children'])) {
                    $this->removeOptionsRecursive($value['children']);
                }
            }
        }
    }
}