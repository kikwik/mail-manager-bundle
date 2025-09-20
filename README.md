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

2. Create your Entity class the extends from `Kikwik\MailManagerBundle\Model\Template` and `Kikwik\MailManagerBundle\Model\Log`:

```php
# src/Entity/Mail/Template.php
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

    const MODEL1 = 'model1';
    const MODEL2 = 'model2';

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

```php
# src/Entity/Mail/Log.php
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

3. Configure the bundle in `config/packages/kikwik_mail_manager.yaml`:

```yaml
kikwik_mail_manager:
    template_class: App\Entity\Mail\MailTemplate
    log_class:      App\Entity\Mail\MailLog
```

4. Update the database to create the tables for entities provided by the bundle:

```console
$ php bin/console make:migration
$ php bin/console doctrine:migrations:migrate
```


Configuration
-------------


Usage
-----

Autowire the `Kikwik\MailManagerBundle\Service\MailManager` service and call the `compose`, `send` or `composeAndSend` method:

```php
final class MyController extends AbstractController
{
    public function myAction(MailManager $mailManager)
    {
        // This will create a new unpersisted entity of class \Kikwik\MailManagerBundle\Model\LogInterface (or null if the template does not exists or is not active)
        $mail = $mailManager->compose(
            'my_template_name',                                 // template name
            ['my_param' => 'my_value']                          // context
            new Address('test@example.com','My customer'),      // recipient (to)
            ['test-cc@example.com'],                            // carbonCopies (cc)
            ['test-bcc@example.com']                            // blindCarbonCopies (bcc)
        ); 
    
        // This will send a previously created email (will be persisted and flush into the database, sendedAt will be filled with the current datetime)
        $mailManager->send($mail); 
    
        // This will create, persist and send email
        $mailManager->composeAndSend(
            'my_template_name',                                 // template name
            ['my_param' => 'my_value']                          // context
            new Address('test@example.com','My customer'),      // recipient (to)
            ['test-cc@example.com'],                            // carbonCopies (cc)
            ['test-bcc@example.com']                            // blindCarbonCopies (bcc)
        ); 
    }
}
```


EasyAdmin
---------

You can use the `Kikwik\MailManagerBundle\EasyAdmin\KikwikMailLogCrudControllerTrait` 
and `Kikwik\MailManagerBundle\EasyAdmin\KikwikMailTemplateCrudControllerTrait` 
to add a custom crud controller for `MailLog` and `MailTemplate` entities:

```php
# src/Controller/Admin/Mail/MailTemplateCrudController.php
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
# src/Controller/Admin/Mail/MailLogCrudController.php
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
}
```
