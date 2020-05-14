<?php

namespace AppBundle\Service;

use AppBundle\Entity\Mosque;

class MailService
{

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $contactEmail;

    /**
     * @var array
     */
    private $doNotReplyEmail;

    /**
     * @var array
     */
    private $postmasterEmail;

    public function __construct(
        \Swift_Mailer $mailer,
        \Twig_Environment $twig,
        $contactEmail,
        $doNotReplyEmail,
        $postmasterEmail
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->contactEmail = $contactEmail; // contact@mawaqit.net
        $this->postmasterEmail = $postmasterEmail; // postmaster@mawaqit.net
        $this->doNotReplyEmail = $doNotReplyEmail; // no-reply@mawaqit.net
    }

    /**
     * Send email when mosque created
     *
     * @param Mosque $mosque
     *
     * @throws @see sendEmail
     */
    function mosqueCreated(Mosque $mosque)
    {
        $title = 'Nouvelle mosquée (' . $mosque->getCountryFullName() . ')';
        $this->sendEmail($mosque, $title, $this->postmasterEmail, $this->postmasterEmail, 'created');
    }

    /**
     * Send email when mosque has been validated by admin
     *
     * @param Mosque $mosque
     *
     * @throws @see sendEmail
     */
    function mosqueValidated(Mosque $mosque)
    {
        $title = "Mosque validated";
        $this->sendEmail($mosque, $title, $mosque->getUser()->getEmail(), $this->doNotReplyEmail, 'validated');
    }

    /**
     * Send email when mosque has been suspended by admin
     *
     * @param Mosque $mosque
     *
     * @throws @see sendEmail
     */
    function mosqueSuspended(Mosque $mosque)
    {
        $title = "Mosque suspended";
        $this->sendEmail($mosque, $title, $mosque->getUser()->getEmail(), $this->postmasterEmail, 'suspended');
    }

    /**
     * Send email to user to check information of the mosque
     *
     * @param Mosque $mosque
     *
     * @throws @see sendEmail
     */
    function checkMosque(Mosque $mosque)
    {
        $title = "We need informations";
        $this->sendEmail($mosque, $title, $mosque->getUser()->getEmail(), $this->postmasterEmail, 'check');
    }

    /**
     * Send email to user to check information of the mosque when duplicated
     *
     * @param Mosque $mosque
     *
     * @throws @see sendEmail
     */
    function duplicatedMosque(Mosque $mosque)
    {
        $title = "Duplicated mosque";
        $this->sendEmail($mosque, $title, $mosque->getUser()->getEmail(), $this->postmasterEmail, 'duplicated');
    }

    /**
     * Send email to user to inform him that his photo is deleted
     *
     * @param Mosque $mosque
     *
     * @throws @see sendEmail
     */
    function rejectScreenPhoto(Mosque $mosque)
    {
        $title = "Screen photo";
        $this->sendEmail($mosque, $title, $mosque->getUser()->getEmail(), $this->postmasterEmail,
            'screen_photo_rejected');
    }

    /**
     * @param Mosque $mosque
     * @param        $title
     * @param        $to
     * @param        $from
     * @param        $status
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function sendEmail(Mosque $mosque, $title, $to, $from, $status)
    {
        $body = $this->twig->render("email_templates/mosque_$status.html.twig", ['mosque' => $mosque]);

        /**
         * @var \Swift_Message $message
         */
        $message = $this->mailer->createMessage();

        $message->setSubject($title)
            ->setCharset("utf-8")
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, 'text/html')
            ->addPart(strip_tags($body), 'text/plain');
        $message->getHeaders()->addTextHeader('List-Unsubscribe', "https://mawaqit.net/fr/backoffice/profile/edit");

        $this->mailer->send($message);
    }

}
