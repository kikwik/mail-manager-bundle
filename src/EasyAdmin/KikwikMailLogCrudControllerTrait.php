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
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CodeEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Kikwik\MailManagerBundle\Model\Log;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

trait KikwikMailLogCrudControllerTrait
{
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
                return !$log->getSendedAt();
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
                return $log->getSendedAt();
            });
    }

    #[AdminAction(routePath: '/{entityId}/send', routeName: 'sendEmail', methods: ['GET', 'POST'])]
    public function sendEmail(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        /** @var Log $log */
        $log = $context->getEntity()->getInstance();
        if($log->getSendedAt())
        {
            $this->addFlash('danger', sprintf('This email was already sended at %s',$log->getSendedAt()->format('Y-m-d H:i:s')));
            return $this->redirect($adminUrlGenerator->setAction(Action::DETAIL)->generateUrl());
        }

        $form = $this->createSendForwardForm($log->getUnserializedEmail());
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $this->updateLog($log, $form);

            // send email
            $mail = unserialize($log->getSerializedEmail());
            $mailer->send($mail);
            $log->setSendedAt(new \DateTimeImmutable());
            $entityManager->persist($log);
            $entityManager->flush();

            $this->addFlash('success', 'This email has just been sended');
            return $this->redirect($adminUrlGenerator->setAction(Action::DETAIL)->setEntityId($log->getId())->generateUrl());
        }

        return $this->render('@KikwikMailManager/easy-admin/send-forward-email-action.html.twig', [
            'log' => $log,
            'form' => $form->createView(),
            'action' => 'send'
        ]);
    }

    #[AdminAction(routePath: '/{entityId}/forward', routeName: 'forwardEmail', methods: ['GET', 'POST'])]
    public function forwardEmail(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        $oldLog = $context->getEntity()->getInstance();
        assert($oldLog instanceof Log);
        $logClass = get_class($oldLog);
        $newLog = new $logClass();
        $newLog->fromEmail(unserialize($oldLog->getSerializedEmail()));
        $this->cloneLogCustomFields($oldLog, $newLog);
        $newLog->setSendedAt(null);

        $form = $this->createSendForwardForm($newLog->getUnserializedEmail());
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $this->updateLog($newLog, $form);

            // send email
            $mailer->send($newLog->getUnserializedEmail());
            $newLog->setSendedAt(new \DateTimeImmutable());
            $entityManager->persist($newLog);
            $entityManager->flush();

            $this->addFlash('success', 'This email has just been sended');
            return $this->redirect($adminUrlGenerator->setAction(Action::DETAIL)->setEntityId($newLog->getId())->generateUrl());
        }

        return $this->render('@KikwikMailManager/easy-admin/send-forward-email-action.html.twig', [
            'log' => $newLog,
            'form' => $form->createView(),
            'action' => 'forward'
        ]);
    }

    protected function createSendForwardForm(Email $email): FormInterface
    {
        // Create a form to edit the TemplatedEmail subject fields
        $form = $this->createFormBuilder()
            ->add('recipientAddress', TextType::class, [
                'data' => $email->getTo()[0]->getAddress(),
                'label' => 'Recipient address',
            ])
            ->add('subject', TextType::class, [
                'data' => $email->getSubject(),
                'label' => 'Subject',
            ])
            ->add('body', CodeEditorType::class, [
                'data' => $email->getHtmlBody(),
                'label' => 'Content',
                'attr' => [
                    'rows' => 20,
                ],
            ])
            ->getForm();
        return $form;
    }

    protected function updateLog(Log $log, FormInterface $form): void
    {
        $email = $log->getUnserializedEmail();;
        // Update the TemplatedEmail object with the form data
        $data = $form->getData();
        $email
            ->to($data['recipientAddress'])
            ->subject($data['subject'])
            ->html($data['body'])
        ;
        // Upload email to log
        $log->fromEmail($email);
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
                ->setTemplatePath('@KikwikMailManager/easy-admin/field/unserialized-email.html.twig'),
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
