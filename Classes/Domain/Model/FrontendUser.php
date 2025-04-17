<?php

namespace Wlb\Crowdsourcing\Domain\Model;

class FrontendUser extends \Evoweb\SfRegister\Domain\Model\FrontendUser
{
    /**
     * @var string
     */
    Protected string $extending;

    public function getExtending(): string
    {
        return $this->extending;
    }

    public function setExtending(string $extending): void
    {
        $this->extending = $extending;
    }
}
