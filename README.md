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
        // This will create a new mail (not saved in database)
        $mail = $mailManager->compose(
            new Address('test@example.com','My customer'),
            'my_template_name',
            ['my_param' => 'my_value']
        ); 
    
        // This will create a new mail and persist and flush into the database (sendedAt will be null)
        $mail = $mailManager->compose(
            new Address('test@example.com','My customer'),
            'my_template_name',
            ['my_param' => 'my_value'],
            true
        ); 
        
        // This will send a previously created email (will be persisted and flush into the database, sendedAt will be filled with the current datetime)
        $mailManager->send($mail); 
    
        // This will create, persist and send email
        $mailManager->composeAndSend(
            new Address('test@example.com','My customer'),
            'my_template_name',
            ['my_param' => 'my_value']
        ); 
    }
}
```
