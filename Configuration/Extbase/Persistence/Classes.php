<?php
return [
    \Wlb\Crowdsourcing\Domain\Model\FrontendUser::class => [
        'tableName' => 'fe_users',
    ],
];

/*
Extbase {
  persistence {
    classes {
      Evoweb\SfRegister\Domain\Model\FrontendUser {
        subclassNames {
          Wlb\Crowdsourcing\Domain\Model\FrontendUser = Wlb\Crowdsourcing\Domain\Model\FrontendUser
        }
      }
      Wlb\Crowdsourcing\Domain\Model\FrontendUser {
        mapping {
          tableName = fe_users
        }
      }
    }
  }
}

*/
