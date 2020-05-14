<?php

namespace AppBundle\Service;

use AppBundle\Entity\Mosque;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Knp\Component\Pager\PaginatorInterface;
use Swift_Mailer;
use Twig\Environment;

class UserService
{

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $emailFrom;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function __construct(
        Swift_Mailer $mailer,
        EntityManager $em,
        Environment $twig,
        PaginatorInterface $paginator,
        $emailFrom
    ) {
        $this->mailer = $mailer;
        $this->em = $em;
        $this->emailFrom = $emailFrom;
        $this->twig = $twig;
        $this->paginator = $paginator;
    }

    /**
     * @param array $data
     *
     * @throws \Swift_RfcComplianceException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    function sendEmailToAllUsers(array $data)
    {
        $subject = $data["subject"];

        $body = $this->twig->render("email_templates/communication.html.twig", ['content' => $data["content"]]);

        $query = $this->em->createQueryBuilder()
            ->from(User::class, "u")
            ->distinct(true)
            ->select("u.email")
            ->where("u.unsubscribeEmails IS NULL OR u.unsubscribeEmails = false")
        ;

        if ($data["isApiUser"] === true) {
            $query->andWhere('u.apiAccessToken is not null');
        }

        if ($data["country"] !== null || $data["hasMosque"] === true) {
            $query->innerJoin('u.mosques', 'm');
        }

        if ($data["country"] !== null) {
            $query->andWhere('m.country = :country')
                ->setParameter(':country', $data["country"]);
        }

        if ($data["hasMosque"] === true) {
            $query->andWhere('m.type = :type')
                ->setParameter(':type', Mosque::TYPE_MOSQUE);
        }

        $offset = 1;
        $pagination = $this->paginator->paginate($query, $offset, 100);

        for ($offset = 1; $offset <= $pagination->getPageCount(); $offset++) {
            $result = $this->paginator->paginate($query, $offset, 100)->getItems();
            $emails = array_column($result, "email");

            $message = $this->mailer->createMessage();
            $message->setSubject($subject)
                ->setTo($this->emailFrom[0])
                ->setFrom([$this->emailFrom[0] => $this->emailFrom[1]])
                ->setBody($body, 'text/html');

            $patternProvider = new \Swift_Mime_Grammar();
            $tmp = [];
            foreach ($emails as $email) {
                if (!preg_match('/^' . $patternProvider->getDefinition('addr-spec') . '$/D', $email)) {
                    continue;
                }
                $tmp[] = $email;
            }

            if (!empty($tmp)) {
                $message->setBcc($tmp);
                $this->mailer->send($message);
            }

        }
    }

    function remindUserToUploadScreenPhoto()
    {
        $mosques = $this->em->getRepository("AppBundle:Mosque")->getMosquesWithoutScreenPhoto();
        $message = $this->mailer->createMessage();
        $message->setFrom([$this->emailFrom[0] => $this->emailFrom[1]]);

        /**
         * @var Mosque $mosque
         */
        $suspendedCount = 0;
        foreach ($mosques as $mosque) {
            $mosque->incrementEmailScreenPhotoReminder();
            if ($mosque->getEmailScreenPhotoReminder() <= 3) {
                $message->setSubject("Rappel / Reminder / تذكير");
                $body = $this->twig->render("email_templates/mosque_screen_photo_reminder.html.twig",
                    ['mosque' => $mosque]);
                $message->setBody($body, 'text/html');
                $message->setTo($mosque->getUser()->getEmail());
                $this->mailer->send($message);
            }

            if ($mosque->getEmailScreenPhotoReminder() > 3) {
                $suspendedCount++;
                $mosque->suspend(Mosque::SUSPENSION_REASON_MISSING_PHOTO);
            }
        }

        $this->em->flush();
        return $suspendedCount;
    }
}
