<?php

namespace Kikwik\MailManagerBundle\EasyAdmin;

use App\Entity\Mail\MailDecorator;
use App\Repository\Mail\MailDecoratorRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

trait KikwikMailTemplateCrudControllerTrait
{
    public function getDefaultFieldList(array $templateChoices): array
    {
        $decoratorChoices = [];
        $mailDecoratorRepository = $this->container->get('doctrine')->getManagerForClass(MailDecorator::class)->getRepository(MailDecorator::class);
        foreach ($mailDecoratorRepository->findAll() as $decorator) {
            $decoratorChoices[$decorator->getName()] = $decorator->getName();
        }


        return [
            ChoiceField::new('name')->setChoices($templateChoices),
            BooleanField::new('isEnabled'),
            TextField::new('senderName'),
            TextField::new('senderEmail'),
            TextField::new('subject'),
            ChoiceField::new('decoratorName')->setChoices($decoratorChoices),
            TextEditorField::new('content')->setTemplatePath('@KikwikMailManager/easy-admin/text_editor_raw.html.twig'),
        ];
    }

    public function addDefaultFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isEnabled')
            ->add('subject')
            ->add('content')
            ;
    }
}
