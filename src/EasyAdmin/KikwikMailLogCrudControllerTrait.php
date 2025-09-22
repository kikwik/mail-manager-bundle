<?php

namespace Kikwik\MailManagerBundle\EasyAdmin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

trait KikwikMailLogCrudControllerTrait
{
    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->disable('new')
            ->disable('edit');
    }

    public function getDefaultFieldList()
    {
        return [
            TextField::new('sender')->hideOnForm(),
            TextField::new('replyTo')->hideOnForm(),
            TextField::new('recipient')->hideOnForm(),
            TextField::new('carbonCopy')->hideOnForm(),
            TextField::new('blindCarbonCopy')->hideOnForm(),
            TextField::new('templateName')->hideOnForm(),
            TextField::new('subject')->hideOnForm(),
            Field::new('unserializedEmail', 'Body')->hideOnForm()
                ->setTemplatePath('@KikwikMailManager/easy-admin/unserialized-email.html.twig'),
            DateTimeField::new('sendedAt')->hideOnForm(),
        ];
    }

    public function addDefaultFilters(Filters $filters, array $templateChoices): Filters
    {
        return $filters
            ->add('sender')
            ->add('recipient')
            ->add('carbonCopy')
            ->add('blindCarbonCopy')
            ->add(ChoiceFilter::new('templateName')->setChoices($templateChoices))
            ->add('subject')
            ->add('sendedAt')
            ;
    }
}
