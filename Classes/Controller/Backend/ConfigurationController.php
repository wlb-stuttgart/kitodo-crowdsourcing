<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $ruleset = '/var/www/html/public/fileadmin/crowd/ruleset_crowdsourcing_wlb.xml';

        $sxe = null;
        $sxe = simplexml_load_file($ruleset);

        $configurationSet = [];
        $metadataDefinitions = [];

        // load metadata to array
        foreach ($sxe->declaration->key as $key) {
            $metadataId = (string) $key->attributes()->{'id'};
            $metadataDefinitions[$metadataId] = (string) $key->label;
        }

        foreach ($sxe->declaration->division as $division) {
            if ($division->attributes()->{'processTitle'}) {
                $documentType = (string) $division->attributes()->{'id'};
                $configurationSet[$documentType] = [];

            }
        }

        foreach ($sxe->correlation->restriction as $restriction) {
            // Each restriction defines a doc type
            $divisionName = (string)$restriction->attributes()->{'division'};
            if (array_key_exists($divisionName, $configurationSet)) {
                foreach ($restriction as $permit) {
                    if ((string) $permit->attributes()->{'key'}) {
                        $permitKey = (string) $permit->attributes()->{'key'};
                        $configurationSet[$divisionName][$permitKey]['label'] = $metadataDefinitions[$permitKey];
                        if ($minOccurs = (string) $permit->attributes()->{'minOccurs'}) {
                            $configurationSet[$divisionName][$permitKey]['minOccurs'] = $minOccurs;
                        }
                        if ($maxOccurs = (string) $permit->attributes()->{'maxOccurs'}) {
                            $configurationSet[$divisionName][$permitKey]['maxOccurs'] = $maxOccurs;
                        }
                    }
                }
            }
        }

        // compare db with ruleset in both directions
        $rulesetAdded = [];
        $rulesetRemoved = [];

        // get db saved config
        $queryResult = $this->metadataConfigurationRepository->findAll();
        if ($queryResult->count() !== 0) {
            /** @var MetadataConfiguration $dbConfiguration */
            $dbConfiguration = $queryResult->getFirst();
            $config = json_decode($dbConfiguration->getJson(), true);

            foreach ($config as $key => $dbDocumentConfiguration) {
                $rulesetAdded[$key] = array_diff_key($configurationSet[$key], $dbDocumentConfiguration);
                $rulesetRemoved[$key] = array_diff_key($dbDocumentConfiguration, $configurationSet[$key]);
            }

            foreach ($rulesetRemoved as $docType => $metadataRemoved) {
                foreach ($metadataRemoved as $metadataId => $metadataConfig) {
                    $rulesetRemoved[$docType][$metadataId]['label'] = $metadataDefinitions[$metadataId];
                }
            }

            $this->view->assign('rulesetAdded', $rulesetAdded);
            $this->view->assign('rulesetRemoved', $rulesetRemoved);
        }

        $this->view->assign('rulesetConfig', $configurationSet);
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
}
