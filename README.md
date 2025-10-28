KikwikMailManagerBundle
=======================

The **KikwikMailManagerBundle** is a Symfony bundle that manages transactional emails for symfony 6.4 / 7.*.


Installation
------------

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

1. Open a command console, enter your project directory and execute:

```console
$ composer require kikwik/mail-manager-bundle
```


Configuration
-------------

1. Create your Entity class that extends `Kikwik\MailManagerBundle\Model\Template`.
    The static `getTemplateChoices` method can be used to list the available templates defined in the system (inside the easyadmin controllers)

```php
<?php

namespace App\Entity\Mail;

use App\Repository\Mail\MailTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\IpTraceable\Traits\IpTraceableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kikwik\MailManagerBundle\Model\Template;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: MailTemplateRepository::class)]
#[ORM\Table('mail__template')]
#[UniqueEntity(fields: ['name'])]
class MailTemplate extends Template
{
    use TimestampableEntity;
    use BlameableEntity;
    use IpTraceableEntity;

    /**************************************/
    /* CONST for template choices         */
    /**************************************/

    const MODEL1 = '01-model1';
    const MODEL2 = '02-model2';

    public static function getTemplateChoices(): array
    {
        return [
            'Model of type 1' => self::MODEL1,
            'Model of type 2' => self::MODEL2,
        ];
    }

    /**************************************/
    /* PROPERTIES                         */
    /**************************************/

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**************************************/
    /* GETTERS & SETTERS                  */
    /**************************************/

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

2. (optional) Create your Entity class that extends `Kikwik\MailManagerBundle\Model\Decorator`:


```php
<?php

namespace App\Entity\Mail;

use App\Repository\Mail\MailDecoratorRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\IpTraceable\Traits\IpTraceableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kikwik\MailManagerBundle\Model\Decorator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: MailDecoratorRepository::class)]
#[ORM\Table('mail_decorator')]
#[UniqueEntity(fields: ['name'])]
class MailDecorator extends Decorator
{
    use TimestampableEntity;
    use BlameableEntity;
    use IpTraceableEntity;

    /**************************************/
    /* PROPERTIES                         */
    /**************************************/

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**************************************/
    /* GETTERS & SETTERS                  */
    /**************************************/

    public function getId(): ?int
    {
        return $this->id;
    }
}

```

3. Create your Entity class that extends `Kikwik\MailManagerBundle\Model\Log`:


```php
<?php

namespace App\Entity\Mail;

use App\Repository\Mail\MailLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\IpTraceable\Traits\IpTraceableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kikwik\MailManagerBundle\Model\Log;

#[ORM\Entity(repositoryClass: MailLogRepository::class)]
#[ORM\Table('mail__log')]
class MailLog extends Log
{
    use TimestampableEntity;
    use BlameableEntity;
    use IpTraceableEntity;

    /**************************************/
    /* PROPERTIES                         */
    /**************************************/

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**************************************/
    /* GETTERS & SETTERS                  */
    /**************************************/

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

4. Configure the bundle in `config/packages/kikwik_mail_manager.yaml`:

```yaml
kikwik_mail_manager:
    template_class:     App\Entity\Mail\MailTemplate
    decorator_class:    App\Entity\Mail\MailDecorator   
    log_class:          App\Entity\Mail\MailLog
```

5. Update the database to create the tables for entities provided by the bundle:

```console
$ php bin/console make:migration
$ php bin/console doctrine:migrations:migrate
```



Usage
-----

Autowire the `Kikwik\MailManagerBundle\Service\MailBuilderFactory` service and call the `createMailBuilder` method,
then use the returned `Kikwik\MailManagerBundle\Service\MailBuilder` object to compose emails.
Autowire the `Kikwik\MailManagerBundle\Service\MailManager` service to send emails.

```php

use \Kikwik\MailManagerBundle\Service\MailBuilderFactory;
use \Kikwik\MailManagerBundle\Service\MailSender;

final class MyController extends AbstractController
{
    public function myAction(MailBuilderFactory $mailBuilderFactory, MailSender $mailSender)
    {
        // Example 1 - create, send and persist       
        if($mailBuilder = $mailBuilderFactory->createMailBuilder('my_template_name'))   // MailBuilderFactory will be null if the template does not exists or is not enabled
        {
            // create the Log object
            $mailLog = $mailBuilder
                ->context(['attivazione'=>$richiesta])                      // set context
                ->to([new Address('sales@customer.com','My customer')])     // set to
                ->cc(['info@customer.com'])                                 // set cc
                ->bcc(['admin@mycompany.com', 'helpdesk@mycompany.com'])    // set bcc
                ->getLog()
            ;
            $mailLog->setSomethingCustom($myObject); // $mailLog is your App\Entity\Mail\MailLog entity, you can set your custom property
                
            $mailSender
                ->send($mailLog)         // send the email, persist the $mailLog and flush
            ;
        }
        
        // Example 2 - persist the log without send email for later review
        if($mailBuilder = $mailBuilderFactory->createMailBuilder('my_template_name'))
        {
            // create the Log object
            $mailLog = $mailBuilder
                ->context(['attivazione'=>$richiesta])
                ->to([new Address('sales@customer.com','My customer')])
                ->getLog()
            ;
            $mailSender
                ->needManualReview($mailLog)    // mark the Log object as "need manual review", persist and flush
            ;
        }

        // Example 3 - persist the log without send email for archive
        if($mailBuilder = $mailBuilderFactory->createMailBuilder('my_template_name'))
        {
            // create the Log object
            $mailLog = $mailBuilder
                ->context(['attivazione'=>$richiesta])
                ->to([new Address('sales@customer.com','My customer')])
                ->getLog()
            ;
            $mailSender
                ->doNotSend($mailLog)    // mark the Log object as "do not send", persist and flush
            ;
        }
        
        // Example 4 - send email without persist the log
        if($mailBuilder = $mailBuilderFactory->createMailBuilder('my_template_name'))
        {
            // create the Log object
            $mailLog = $mailBuilder
                ->context(['attivazione'=>$richiesta])
                ->to([new Address('sales@customer.com','My customer')])
                ->getLog()
            ;
            $mailSender->send($mailLog, false); // send the email without saving the Log object in the database thanks to the second false parameter
        }
    }
}
```


EasyAdmin
---------

You can use the `Kikwik\MailManagerBundle\EasyAdmin\KikwikMailLogCrudControllerTrait` 
and `Kikwik\MailManagerBundle\EasyAdmin\KikwikMailTemplateCrudControllerTrait` 
and `Kikwik\MailManagerBundle\EasyAdmin\KikwikMailDecoratorCrudControllerTrait`
to add a custom crud controller for `MailLog` and `MailTemplate` and `MailDecorator` entities:

```php
<?php

namespace App\Controller\Admin\Mail;

use App\Entity\Mail\MailTemplate;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Kikwik\MailManagerBundle\EasyAdmin\KikwikMailTemplateCrudControllerTrait;

class MailTemplateCrudController extends AbstractCrudController
{
    use KikwikMailTemplateCrudControllerTrait;

    public static function getEntityFqcn(): string
    {
        return MailTemplate::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->getDefaultFieldList(MailTemplate::getTemplateChoices());
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $this->addDefaultFilters($filters);
    }
}
```

```php
<?php

namespace App\Controller\Admin\Mail;

use App\Entity\Mail\MailDecorator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Kikwik\MailManagerBundle\EasyAdmin\KikwikMailDecoratorCrudControllerTrait;

class MailDecoratorCrudController extends AbstractCrudController
{
    use KikwikMailDecoratorCrudControllerTrait;

    public static function getEntityFqcn(): string
    {
        return MailDecorator::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $this->addDefaultFilters($filters);
    }
}

```

```php
<?php

namespace App\Controller\Admin\Mail;

use App\Entity\Mail\MailLog;
use App\Entity\Mail\MailTemplate;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Kikwik\MailManagerBundle\EasyAdmin\KikwikMailLogCrudControllerTrait;

class MailTemplateCrudController extends AbstractCrudController
{
    use KikwikMailLogCrudControllerTrait;

    public static function getEntityFqcn(): string
    {
        return MailLog::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->getDefaultFieldList();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $this->addDefaultFilters($filters, MailTemplate::getTemplateChoices());
    }
    
    protected function cloneLogCustomFields(Log $oldLog, Log $newLog): void
    {
        $newLog->setSomethingCustom($oldLog->getSomethingCustom()); // set a custom property when cloning the log for forward action
    }
}
```
