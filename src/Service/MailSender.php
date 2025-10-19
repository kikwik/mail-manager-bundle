<?php

namespace Kikwik\MailManagerBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Ehyiah\QuillJsBundle\DTO\QuillGroup;
use Ehyiah\QuillJsBundle\Form\QuillType;
use Kikwik\MailManagerBundle\Model\Log;
use Kikwik\MailManagerBundle\Validator\EmailList;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ManagerRegistry $doctrine,
        private readonly FormFactory     $formFactory,
    )
    {
    }

    /**
     * Creates a form builder for sending emails with options to edit recipient fields, subject, and body.
     *
     * @param Log $log Instance containing the email data to populate the form.
     * @param bool $addSkipButton Determines if the "Skip" button should be included in the form.
     *
     * @return FormBuilderInterface The configured form builder instance.
     */
    public function createSendFormBuilder(Log $log, bool $addSkipButton): FormBuilderInterface
    {
        $email = $log->getUnserializedEmail();
        $toData = [];
        foreach($email->getTo() as $to)
        {
            $toData[] = $to->toString();
        }
        $ccData = [];
        foreach($email->getCc() as $cc)
        {
            $ccData[] = $cc->toString();
        }
        $bccData = [];
        foreach($email->getBcc() as $bcc)
        {
            $bccData[] = $bcc->toString();
        }
        // Create a form to edit the TemplatedEmail subject fields
        $formBuilder = $this->formFactory->createBuilder(FormType::class,null,[ 'attr' => ['novalidate' => 'novalidate']])
            ->add('to', EmailType::class, [
                'data' => implode(', ', $toData),
                'label' => 'To',
                'constraints' => new EmailList(),
            ])
            ->add('cc', TextType::class, [
                'data' => implode(', ', $ccData),
                'label' => 'Cc',
                'help' => 'Separate multiple emails with a comma (,)',
                'constraints' => new EmailList(),
            ])
            ->add('bcc', TextType::class, [
                'data' => implode(', ', $bccData),
                'label' => 'Bcc',
                'help' => 'Separate multiple emails with a comma (,)',
                'constraints' => new EmailList(),
            ])
            ->add('subject', TextType::class, [
                'data' => $email->getSubject(),
                'label' => 'Subject',
            ])
            ->add('body', QuillType::class, [
                'data' => $email->getHtmlBody(),
                'label' => 'Content',
                'quill_extra_options' => [
                    'height' => '780px',
                ],
                'quill_options' => [
                    QuillGroup::buildWithAllFields()
                ],
            ])
            ->add('send', SubmitType::class, [
                'priority' => 100,
                'label'=>'<span class="fa fa-paper-plane"></span>&nbsp;&nbsp;Send email',
                'label_html' => true,
                'attr' => ['class' => 'btn btn-primary'],
                'row_attr' => ['class' => 'text-end']
            ])
        ;
        if($addSkipButton)
        {
            $formBuilder
                ->add('skip', SubmitType::class, [
                    'priority' => 101,
                    'label'=>'<span class="fa fa-archive"></span>&nbsp;&nbsp;Do not send email, save and mark as Skipped',
                    'label_html' => true,
                    'attr' => ['class' => 'btn btn-default ms-3'],
                    'row_attr' => ['class' => 'float-end']
                ])
            ;
        }
        return $formBuilder;
    }

    /**
     * Processes the form for sending emails by updating the email object with form data and either sending or skipping the email.
     *
     * @param Log $log Instance containing the original email data and metadata for the logging entity.
     * @param FormInterface $form The form containing recipient, subject, and email body data.
     *
     * @return string Returns 'send' if the email is sent, or 'skip' if the email sending is skipped.
     */
    public function processSendForm(Log $log, FormInterface $form): string
    {
        $email = new TemplatedEmail();
        // Update the TemplatedEmail object with the form data
        $data = $form->getData();
        $email
            ->from($log->getUnserializedEmail()->getFrom()[0])
            ->subject($data['subject'])
            ->html($data['body'])
        ;
        if($data['to'])
        {
            $email->to($data['to']);
        }
        if($data['cc'])
        {
            $ccs = explode(',',$data['cc']);
            $email->cc(...$ccs);
        }
        if($data['bcc'])
        {
            $bccs = explode(',',$data['bcc']);
            $email->bcc(...$bccs);
        }
        // Upload email to log
        $log->fromEmail($email);

        if($form->has('skip') && $form->get('skip')->isClicked())
        {
            // skip email
            $this->doNotSend($log);
            return 'skip';
        }
        else
        {
            // send email
            $this->send($log);
            return 'send';
        }
    }

    /**
     * Sends an email using the information from the provided log object.
     * If the email contains any recipients (To, Cc, Bcc), it marks the log as sent,
     * updates the sent date, and dispatches the email via the mailer service.
     * Additionally, if persistAndFlush is set to true, the log object is persisted
     * and the changes are flushed to the database.
     *
     * @param Log $log The log object containing email and status data.
     * @param bool $persistAndFlush Flag to determine whether to persist and flush the log changes.
     */
    public function send(Log $log, bool $persistAndFlush = true)
    {
        $email = $log->getUnserializedEmail();
        if($email->getTo() || $email->getCc() || $email->getBcc()) {
            $log->setSendedAt(new \DateTimeImmutable());
            $log->setStatus(LOG::STATUS_SENT);
            $this->mailer->send($email);
        }
        if($persistAndFlush)
        {
            $this->doctrine->getManagerForClass(get_class($log))->persist($log);
            $this->doctrine->getManagerForClass(get_class($log))->flush();
        }
    }

    /**
     * Updates the status of the given log object to "do not send".
     * If persistAndFlush is set to true, the log object is persisted
     * and the changes are flushed to the database.
     *
     * @param Log $log The log object whose status will be updated.
     * @param bool $persistAndFlush Flag to determine whether to persist and flush the log changes.
     */
    public function doNotSend(Log $log, bool $persistAndFlush = true)
    {
        $log->setStatus(Log::STATUS_DO_NOT_SEND);
        if($persistAndFlush)
        {
            $this->doctrine->getManagerForClass(get_class($log))->persist($log);
            $this->doctrine->getManagerForClass(get_class($log))->flush();
        }
    }

    /**
     * Marks the specified log as requiring manual review by updating its status.
     * If persistAndFlush is set to true, the updated log object is persisted
     * and changes are saved to the database.
     *
     * @param Log $log The log object to modify.
     * @param bool $persistAndFlush Flag to determine whether to persist and flush the log changes.
     */
    public function needManualReview(Log $log, bool $persistAndFlush = true)
    {
        $log->setStatus(Log::STATUS_NEED_MANUAL_REVIEW);
        if($persistAndFlush)
        {
            $this->doctrine->getManagerForClass(get_class($log))->persist($log);
            $this->doctrine->getManagerForClass(get_class($log))->flush();
        }
    }
}
