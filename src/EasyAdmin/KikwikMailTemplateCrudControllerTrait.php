<?php

namespace Kikwik\MailManagerBundle\EasyAdmin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

trait KikwikMailTemplateCrudControllerTrait
{
    public function getDefaultFieldList(array $templateChoices): array
    {
        return [
            ChoiceField::new('name')->setChoices($templateChoices),
            BooleanField::new('isEnabled'),
            TextField::new('senderName'),
            TextField::new('senderEmail'),
            TextField::new('subject'),
            TextEditorField::new('body')->setTemplatePath('@KikwikMailManager/easy-admin/text_editor_raw.html.twig'),
        ];
    }

    public function addDefaultFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isEnabled')
            ->add('subject')
            ->add('body')
            ;
    }
}
