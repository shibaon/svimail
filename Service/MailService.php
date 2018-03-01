<?php

namespace Svi\MailBundle\Service;

use Svi\AppContainer;
use Svi\Application;

class MailService extends AppContainer
{
    private $swift = null;

    function __construct(Application $app)
    {
        parent::__construct($app);

        $this->getSwift();
    }

    public function sendSpool()
    {
        if (!$this->app->getConfigService()->getParameter('mail.spool')) {
            throw new \Exception('No mail.spool dir configured');
        }
        $spool = new \Swift_FileSpool($this->app->getRootDir() . '/' . $this->app->getConfigService()->getParameter('mail.spool'));
        if ($this->app->getConfigService()->getParameter('mail.spoolTimeLimit')) {
            $spool->setTimeLimit($this->app->getConfigService()->getParameter('mail.spoolTimeLimit'));
        }
        if ($this->app->getConfigService()->getParameter('mail.spoolMessageLimit')) {
            $spool->setMessageLimit($this->app->getConfigService()->getParameter('mail.spoolMessageLimit'));
        }

        $spool->flushQueue($this->getRealTransport());
    }

    protected function swiftMail(\Swift_Message $message)
    {
        $this->getSwift()->send($message);
    }

    /**
     * @return \Swift_Mailer
     * @throws \Exception
     */
    private function getSwift()
    {
        if (!$this->swift) {
            $transport = null;

            if ($this->app->getConfigService()->getParameter('mail.spool')) {
                $spool = new \Swift_FileSpool($this->app->getRootDir() . '/' . $this->app->getConfigService()->getParameter('mail.spool'));
                $transport = new \Swift_SpoolTransport($spool);
            } else {
                $transport = $this->getRealTransport();
            }

            $this->swift = new \Swift_Mailer($transport);
        }

        return $this->swift;
    }

    private function getRealTransport()
    {
        switch ($this->app->getConfigService()->getParameter('mail.transport')) {
            case 'mail':
                $transport = new \Swift_SendmailTransport();
                break;
            case 'smtp':
                if (!$this->app->getConfigService()->getParameter('mail.host')) {
                    throw new \Exception('No mail.host defined for smtp in config');
                }
                if (!$this->app->getConfigService()->getParameter('mail.port')) {
                    throw new \Exception('No mail.port defined for smtp in config');
                }
                $transport = new \Swift_SmtpTransport(
                    $this->app->getConfigService()->getParameter('mail.host'),
                    $this->app->getConfigService()->getParameter('mail.port'),
                    $this->app->getConfigService()->getParameter('mail.encryption')
                );
                if ($this->app->getConfigService()->getParameter('mail.encryption') &&
                    !$this->app->getConfigService()->getParameter('mail.verifyPeer')) {
                    $transport->setStreamOptions([
                        'ssl' => [
                            'verify_peer'      => false,
                            'verify_peer_name' => false,
                        ],
                    ]);
                }
                $transport
                    ->setUsername($this->app->getConfigService()->getParameter('mail.user'))
                    ->setPassword($this->app->getConfigService()->getParameter('mail.password'));
                break;
            default:
                $transport = null;
        }

        if (!$transport) {
            throw new \Exception('No correct mail.transport defined in config (correct are: mail, smtp)');
        }

        return $transport;
    }

}