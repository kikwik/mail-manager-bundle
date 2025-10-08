<?php

namespace Kikwik\MailManagerBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmailListValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        $emails = array_map('trim', explode(',', $value));
        $invalidEmails = [];
        foreach ($emails as $email) {
            if($email)
            {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalidEmails[] = $email;
                }
            }
        }
        if(count($invalidEmails))
        {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ email }}', implode(', ',$invalidEmails))
                ->addViolation();
        }
    }
}
