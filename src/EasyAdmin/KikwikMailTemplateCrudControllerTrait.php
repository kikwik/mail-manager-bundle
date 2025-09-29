<?php

namespace Kikwik\MailManagerBundle\EasyAdmin;

use App\Entity\Mail\MailDecorator;
use App\Repository\Mail\MailDecoratorRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Ehyiah\QuillJsBundle\DTO\QuillGroup;
use Ehyiah\QuillJsBundle\Form\QuillAdminField;

trait KikwikMailTemplateCrudControllerTrait
{
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort([
                'name' => 'ASC',
            ]);
    }

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
            TextField::new('replyToEmail'),
            TextField::new('subject'),
            ChoiceField::new('decoratorName')->setChoices($decoratorChoices),
            QuillAdminField::new('content')->setFormTypeOptions([
                'quill_extra_options' => [
                    'height' => '780px',
                ],
                'quill_options' => [
                    QuillGroup::buildWithAllFields()
                ]
            ]),
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
