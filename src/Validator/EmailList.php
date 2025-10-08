<?php

namespace Kikwik\MailManagerBundle\Validator;

use Symfony\Component\Validator\Constraint;

class EmailList extends Constraint
{
    public $message = 'Not valid email: {{ email }}';
}
