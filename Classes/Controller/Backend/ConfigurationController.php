<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use Wlb\Crowdsourcing\Domain\Model\MetadataConfiguration;
use Wlb\Crowdsourcing\Domain\Repository\MetadataConfigurationRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ConfigurationController extends ActionController
{
    /**
     * @access protected
     * @var MetadataConfigurationRepository
     */
    protected MetadataConfigurationRepository $metadataConfigurationRepository;

    /**
     * @access public
     *
     * @param MetadataConfigurationRepository $metadataConfigurationRepository
     *
     * @return void
     */
    public function injectMetadataConfigurationRepository(MetadataConfigurationRepository $metadataConfigurationRepository): void
    {
        $this->metadataConfigurationRepository = $metadataConfigurationRepository;
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

            $this->view->assign('missingDocType', $missingDocType);
            $this->view->assign('rulesetAdded', $rulesetAdded);
            $this->view->assign('rulesetRemoved', $rulesetRemoved);
            $this->view->assign('dbConfig', $dbConfigArray);


            if (!empty($rulesetAdded)) {
                // remove options from dbConfigArray because only the keys are saved in the db
                $this->removeOptionsRecursive($dbConfigArray);
                $config = array_replace_recursive($dbConfigArray, $configurationRuleset);
            } else {
                $config = $dbConfigArray;
            }
        }
        $this->view->assign('rulesetConfig', $configurationRuleset);
        $this->view->assign('config', $config);

        return $this->htmlResponse();
    }

    /**
     * @throws UnknownObjectException
     * @throws StopActionException
     * @throws IllegalObjectTypeException
     * @throws NoSuchArgumentException
     */
    public function saveAction()
    {
        $metadataConfiguration = $this->request->getArgument('metadata');

        $queryResult = $this->metadataConfigurationRepository->findAll();

        if ($queryResult->count() === 0) {
            $metadataConfigurationObject = new MetadataConfiguration();
            $metadataConfigurationObject->setJson(json_encode($metadataConfiguration));
            $this->metadataConfigurationRepository->add($metadataConfigurationObject);
        } else {
            /** @var MetadataConfiguration $metadataConfigurationObject */
            $metadataConfigurationObject = $queryResult->getFirst();
            $metadataConfigurationObject->setName("Name");
            $metadataConfigurationObject->setJson(json_encode($metadataConfiguration));

            $this->metadataConfigurationRepository->update($metadataConfigurationObject);
        }

        $this->redirect('index');
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