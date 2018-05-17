<?php
/**
 * Created by PhpStorm.
 * User: miquelator
 * Date: 17/5/18
 * Time: 20:50
 */

namespace PwBox\Model\Implementation;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;

use PwBox\Model\MailerRepository;

class SwiftMailerRepository implements MailerRepository
{

    private $transport;

    /**
     * SwiftMailerRepository constructor.
     * @param $transport
     */
    public function __construct()
    {
        $this->transport = (new Swift_SmtpTransport('smtp.live.com', 587, 'tls'))
            ->setUsername('projectesweb2@hotmail.com')
            ->setPassword('MiqPanCar96')
        ;
    }
    //Si tot està bé, enviem missatge



    public function sendValidate($id, $name, $email){


        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($this->transport);



        // Create a message
        $message = (new Swift_Message('Activation mail'))
            ->setFrom(['projectesweb2@hotmail.com' => 'Pwbox Awesome Team'])
            ->setTo([$email, $email => $name])
            ->setBody(
                '<html>' .
                ' <head></head>' .
                ' <body>' .
                '<a href="pwbox.test/activate/id=' . $id.'">Sign in</a>'.
                ' </body>' .
                '</html>',
                'text/html' // Mark the content-type as HTML
            );

        // Send the message
        $result = $mailer->send($message);


    }



}