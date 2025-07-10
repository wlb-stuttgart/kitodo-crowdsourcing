<?php
declare(strict_types=1);

namespace Wlb\Crowdsourcing\Validation\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validator for password strength.
 */
class PasswordValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => [12, 'Minimum password length', 'integer'],
    ];

    /**
     * Validate password strength
     *
     * @param mixed $value
     */
    protected function isValid($value): void
    {
        if (!is_string($value) || empty($value)) {
            return;
        }

        $errors = [];
        $minLength = (int)$this->options['minimum'];
        $recommendedLength = (int)$this->options['recommendedLength'];

        if (strlen($value) < $minLength) {
            $errors[] = $this->translateErrorMessage(
                'feuser.password.error.minlength',
                'Crowdsourcing',
                [$minLength]
            );
        }

        // Check uppercase
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = $this->translateErrorMessage(
                'feuser.password.error.uppercase',
                'Crowdsourcing'
            );
        }

        // Check lowercase
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = $this->translateErrorMessage(
                'feuser.password.error.lowercase',
                'Crowdsourcing'
            );
        }

        //Check numbers
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = $this->translateErrorMessage(
                'feuser.password.error.number',
                'Crowdsourcing'
            );
        }

        // Check symbols
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\?~`]/', $value)) {
            $errors[] = $this->translateErrorMessage(
                'feuser.password.error.symbol',
                'Crowdsourcing'
            );
        }

        if (!empty($errors)) {
            $message  = $this->translateErrorMessage(
                'feuser.password.error.message',
                'Crowdsourcing',
                [trim(implode(', ', $errors))]
            );

            $this->addError($message, 1234567890);
        }
    }
}
