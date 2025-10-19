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
use Ehyiah\QuillJsBundle\DTO\QuillGroup;
use Ehyiah\QuillJsBundle\Form\QuillType;
use Kikwik\MailManagerBundle\Model\Log;
use Kikwik\MailManagerBundle\Service\MailSender;
use Kikwik\MailManagerBundle\Validator\EmailList;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait KikwikMailLogCrudControllerTrait
{
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->disable('new')
            ->disable('edit')
            ->add(Crud::PAGE_INDEX, $this->createSendAction())
            ->add(Crud::PAGE_DETAIL, $this->createSendAction())
            ->add(Crud::PAGE_INDEX, $this->createForwardAction())
            ->add(Crud::PAGE_DETAIL, $this->createForwardAction())
            ;
    }

    protected function createSendAction()
    {
        return Action::new('sendEmail', 'Send', 'fa fa-paper-plane')
            ->linkToCrudAction('sendEmail')
            ->setHtmlAttributes([
                'title' => 'Send email',
                'data-bs-toggle' => 'tooltip',
            ])
            ->displayIf(static function ($log) {
                return !$log?->getSendedAt();
            });
    }

    public function createForwardAction()
    {
        return Action::new('forwardEmail', 'Forward', 'fa fa-share-from-square')
            ->linkToCrudAction('forwardEmail')
            ->setHtmlAttributes([
                'title' => 'Forward email',
                'data-bs-toggle' => 'tooltip',
            ])
            ->displayIf(static function ($log) {
                return $log?->getSendedAt();
            });
    }

    #[AdminAction(routePath: '/{entityId}/send', routeName: 'sendEmail', methods: ['GET', 'POST'])]
    public function sendEmail(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator, MailSender $mailSender)
    {
        /** @var Log $log */
        $log = $context->getEntity()->getInstance();
        if($log->getSendedAt())
        {
            $this->addFlash('danger', sprintf('This email was already sended at %s',$log->getSendedAt()->format('Y-m-d H:i:s')));
            return $this->redirect($adminUrlGenerator->setAction(Action::DETAIL)->generateUrl());
        }

        $form = $mailSender->createSendFormBuilder($log, true)->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $action = $mailSender->processSendForm($log, $form);
            switch($action)
            {
                case 'skip':
                    $this->addFlash('success', 'This email has just been skipped');
                    break;
                case 'send':
                    $this->addFlash('success', 'This email has just been sended');
                    break;
                default:
                    $this->addFlash('success', 'This email has just been '.$action);
            }
            return $this->redirect($adminUrlGenerator->setAction(Action::DETAIL)->setEntityId($log->getId())->generateUrl());
        }

        return $this->render('@KikwikMailManager/easy-admin/action_send-forward-email.html.twig', [
            'log' => $log,
            'form' => $form->createView(),
            'action' => 'send'
        ]);
    }

    #[AdminAction(routePath: '/{entityId}/forward', routeName: 'forwardEmail', methods: ['GET', 'POST'])]
    public function forwardEmail(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator, MailSender $mailSender)
    {
        $oldLog = $context->getEntity()->getInstance();
        assert($oldLog instanceof Log);
        $logClass = get_class($oldLog);
        $newLog = new $logClass();
        $newLog->fromEmail(unserialize($oldLog->getSerializedEmail()));
        $this->cloneLogCustomFields($oldLog, $newLog);
        $newLog->setSendedAt(null);

        $form = $mailSender->createSendFormBuilder($newLog, false)->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $action = $mailSender->processSendForm($newLog, $form);
            switch($action)
            {
                case 'skip':
                    $this->addFlash('success', 'This email has just been skipped');
                    break;
                case 'send':
                    $this->addFlash('success', 'This email has just been sended');
                    break;
                default:
                    $this->addFlash('success', 'This email has just been '.$action);
            }

            return $this->redirect($adminUrlGenerator->setAction(Action::DETAIL)->setEntityId($newLog->getId())->generateUrl());
        }

        return $this->render('@KikwikMailManager/easy-admin/action_send-forward-email.html.twig', [
            'log' => $newLog,
            'form' => $form->createView(),
            'action' => 'forward'
        ]);
    }

    /**
     * override this function to customize the log cloning (copy custom fields))
     */
    protected function cloneLogCustomFields(Log $oldLog, Log $newLog): void
    {
        // do something like:
        // $newLog->setCustomField($oldLog->getCustomField());
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
                ->setTemplatePath('@KikwikMailManager/easy-admin/field_unserialized-email.html.twig'),
            DateTimeField::new('createdAt')->hideOnForm(),
            TextField::new('status')->hideOnForm(),
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
