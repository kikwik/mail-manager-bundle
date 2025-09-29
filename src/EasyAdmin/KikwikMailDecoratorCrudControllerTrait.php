<?php

namespace Kikwik\MailManagerBundle\EasyAdmin;

use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Ehyiah\QuillJsBundle\DTO\QuillGroup;
use Ehyiah\QuillJsBundle\Form\QuillAdminField;

trait KikwikMailDecoratorCrudControllerTrait
{
    public function getDefaultFieldList(): array
    {
        return [
            TextField::new('name'),
            QuillAdminField::new('header')->setFormTypeOptions([
                'quill_extra_options' => [
                    'height' => '300px',
                ],
                'quill_options' => [
                    QuillGroup::buildWithAllFields()
                ]
            ]),
            QuillAdminField::new('footer')->setFormTypeOptions([
                'quill_extra_options' => [
                    'height' => '300px',
                ],
                'quill_options' => [
                    QuillGroup::buildWithAllFields()
                ]
            ]),
        ];
    }
}
