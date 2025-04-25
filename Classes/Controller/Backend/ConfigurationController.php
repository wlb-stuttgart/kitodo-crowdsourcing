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
        $ruleset = '/var/www/html/public/fileadmin/crowd/ruleset_crowdsourcing_wlb.xml';

        $sxe = null;
        $sxe = simplexml_load_file($ruleset);

        $configurationRuleset = [];
        $metadataDefinitions = [];

        // load metadata to array
        foreach ($sxe->declaration->key as $key) {
            $metadataId = (string) $key->attributes()->{'id'};
            $metadataDefinitions[$metadataId] = (string) $key->label;
            foreach ($key->key as $secondKey) {
                $metadataId = (string) $secondKey->attributes()->{'id'};
                $metadataDefinitions[$metadataId] = (string) $secondKey->label;
                foreach ($secondKey->key as $thirdKey) {
                    $metadataId = (string) $thirdKey->attributes()->{'id'};
                    $metadataDefinitions[$metadataId] = (string) $thirdKey->label;
                }
            }
        }

        foreach ($sxe->declaration->division as $division) {
            if ($division->attributes()->{'processTitle'}) {
                $documentType = (string) $division->attributes()->{'id'};
                $configurationRuleset[$documentType] = [];

            }
        }

        foreach ($sxe->correlation->restriction as $restriction) {
            // Each restriction defines a doc type
            $divisionName = (string) $restriction->attributes()->{'division'};
            if (array_key_exists($divisionName, $configurationRuleset)) {
                foreach ($restriction as $permit) {
                    if ((string) $permit->attributes()->{'key'}) {
                        $permitKey = (string) $permit->attributes()->{'key'};
                        $configurationRuleset[$divisionName][$permitKey]['label'] = $metadataDefinitions[$permitKey];
                        if ($minOccurs = (string) $permit->attributes()->{'minOccurs'}) {
                            $configurationRuleset[$divisionName][$permitKey]['minOccurs'] = $minOccurs;
                        }
                        if ($maxOccurs = (string) $permit->attributes()->{'maxOccurs'}) {
                            $configurationRuleset[$divisionName][$permitKey]['maxOccurs'] = $maxOccurs;
                        }
                    }
                    foreach ($permit->permit as $secondPermit) {
                        if ((string) $secondPermit->attributes()->{'key'}) {
                            $secondPermitKey = (string) $secondPermit->attributes()->{'key'};
                            $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['label'] = $metadataDefinitions[$secondPermitKey];
                            if ($minOccurs = (string) $secondPermit->attributes()->{'minOccurs'}) {
                                $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['minOccurs'] = $minOccurs;
                            }
                            if ($maxOccurs = (string) $secondPermit->attributes()->{'maxOccurs'}) {
                                $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['maxOccurs'] = $maxOccurs;
                            }
                        }

                        foreach ($secondPermit->permit as $thirdPermit) {
                            if ((string) $thirdPermit->attributes()->{'key'}) {
                                $thirdPermitKey = (string) $thirdPermit->attributes()->{'key'};
                                $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['children'][$thirdPermitKey]['label'] = $metadataDefinitions[$thirdPermitKey];
                                if ($minOccurs = (string) $thirdPermit->attributes()->{'minOccurs'}) {
                                    $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['children'][$thirdPermitKey]['minOccurs'] = $minOccurs;
                                }
                                if ($maxOccurs = (string) $thirdPermit->attributes()->{'maxOccurs'}) {
                                    $configurationRuleset[$divisionName][$permitKey]['children'][$secondPermitKey]['children'][$thirdPermitKey]['maxOccurs'] = $maxOccurs;
                                }
                            }

                        }
                    }
                }
            }
        }

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
                $config = array_replace_recursive($dbConfigArray, $configurationRuleset);
            } else {
                $config = $dbConfigArray;
            }
        }

        $this->view->assign('rulesetConfig', $configurationRuleset);
        $this->view->assign('config', $config);

        return $this->htmlResponse();
    }

    public function saveDemoFormAction(): ResponseInterface
    {
        $untrustedMetadata = $this->request->getParsedBody()['metadata'];
        $trustedMetadata = $this->request->getArgument('metadata');

        debug($trustedMetadata);
        debug($untrustedMetadata);

        exit;

        return (new ForwardResponse('index'));
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
