<?php

namespace Kikwik\MailManagerBundle\EasyAdmin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Kikwik\MailManagerBundle\Model\Log;
use Symfony\Component\Mailer\MailerInterface;

trait KikwikMailLogCrudControllerTrait
{
    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->disable('new')
            ->disable('edit')
            ->add(Crud::PAGE_DETAIL, $this->createSendAction())->add(Crud::PAGE_INDEX, $this->createSendAction())
            ;
    }

    protected function createSendAction()
    {
        return Action::new('send', 'Send', 'fa fa-paper-plane')
            ->linkToCrudAction('send')
            ->setHtmlAttributes([
                'title' => 'Send email',
                'data-bs-toggle' => 'tooltip',
                'onclick' => 'return confirm("Are you sure you want to send this email?");' // Popup di conferma
            ])
            ->displayIf(static function ($log) {
                return !$log->getSendedAt();
            });
    }

    #[AdminAction(routePath: '/{entityId}/send', routeName: 'send', methods: ['GET', 'POST'])]
    public function send(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        /** @var Log $log */
        $log = $context->getEntity()->getInstance();
        if($log->getSendedAt())
        {
            $this->addFlash('danger', sprintf('This email was already sended at %s',$log->getSendedAt()->format('Y-m-d H:i:s')));
            return $this->redirect($adminUrlGenerator->setAction(Action::DETAIL)->generateUrl());
        }

        $mail = $log->getUnserializedEmail();
        $mailer->send($mail);
        $log->setSendedAt(new \DateTimeImmutable());
        $entityManager->persist($log);
        $entityManager->flush();

        $this->addFlash('success', 'This email has just been sended');
        return $this->redirect($adminUrlGenerator->setAction(Action::DETAIL)->generateUrl());
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
