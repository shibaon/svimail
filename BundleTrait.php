<?php

namespace Svi\MailBundle;

use Svi\MailBundle\Service\MailService;

trait BundleTrait
{
    /**
     * @return MailService
     */
    public function getMailService()
    {
        return $this->app[MailService::class];
    }
}