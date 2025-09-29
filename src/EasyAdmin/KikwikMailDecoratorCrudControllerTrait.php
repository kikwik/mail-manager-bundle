<?php

namespace Kikwik\MailManagerBundle\EasyAdmin;

use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

trait KikwikMailDecoratorCrudControllerTrait
{
    public function getDefaultFieldList(): array
    {
        return [
            TextField::new('name'),
            CodeEditorField::new('header')
                ->setTemplatePath('@KikwikMailManager/easy-admin/field/code_editor_with_preview.html.twig'),
            CodeEditorField::new('footer')
                ->setTemplatePath('@KikwikMailManager/easy-admin/field/code_editor_with_preview.html.twig'),
        ];
    }
}
