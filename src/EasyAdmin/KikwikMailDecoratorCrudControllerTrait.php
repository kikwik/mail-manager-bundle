<?php

namespace Kikwik\MailManagerBundle\EasyAdmin;

use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

trait KikwikMailDecoratorCrudControllerTrait
{
    public function getDefaultFieldList(): array
    {
        return [
            TextField::new('name'),
            TextEditorField::new('header')->setTemplatePath('@KikwikMailManager/easy-admin/text_editor_raw.html.twig'),
            TextEditorField::new('footer')->setTemplatePath('@KikwikMailManager/easy-admin/text_editor_raw.html.twig'),
        ];
    }
}
