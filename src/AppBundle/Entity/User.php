<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * User
 */
class User extends BaseUser
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @Assert\IsTrue(groups={"Registration"})
     * @var boolean
     */
    private $tou;

    /**
     * @Assert\Uuid()
     * @var Uuid
     */
    private $apiAccessToken;

    /**
     * Allowed quota call per day
     * @var integer
     */
    private $apiQuota;

    /**
     * The number of api call in the day
     * @var integer
     */
    private $apiCallNumber;

    /**
     * The api use comment
     * @var string
     */
    private $apiUseDescription;

    /**
     * Allowed quota call per day
     * @var integer
     */
    private $mosqueQuota;

    /**
     * @var boolean
     */
    private $unsubscribeEmails;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var PersistentCollection
     */
    private $mosques;

    /**
     * @var bool
     * @RecaptchaTrue(groups={"Registration"})
     */
    public $recaptcha;

    public function __construct() {
        $this->created = new \DateTime();
        parent::__construct();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return User
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return User
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return PersistentCollection
     */
    public function getMosques(): ?PersistentCollection
    {
        return $this->mosques;
    }

    /**
     * @return bool
     */
    public function isTou()
    {
        return $this->tou;
    }

    /**
     * @param bool $tou
     * @return User
     */
    public function setTou(bool $tou): User
    {
        $this->tou = $tou;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiAccessToken()
    {
        return $this->apiAccessToken;
    }

    /**
     * @param string $apiAccessToken
     */
    public function setApiAccessToken($apiAccessToken): void
    {
        $this->apiAccessToken = $apiAccessToken;
    }

    /**
     * @return int
     */
    public function getApiQuota()
    {
        return $this->apiQuota;
    }

    /**
     * @param int $apiQuota
     */
    public function setApiQuota($apiQuota): void
    {
        $this->apiQuota = $apiQuota;
    }

    /**
     * @return int
     */
    public function getApiCallNumber()
    {
        return $this->apiCallNumber;
    }

    /**
     * @param int $apiCallNumber
     */
    public function setApiCallNumber($apiCallNumber): void
    {
        $this->apiCallNumber = $apiCallNumber;
    }

    /**
     * @return string
     */
    public function getApiUseDescription()
    {
        return $this->apiUseDescription;
    }

    /**
     * @param string $apiUseDescription
     */
    public function setApiUseDescription($apiUseDescription): void
    {
        $this->apiUseDescription = $apiUseDescription;
    }

    public function incrementApiCallNumber()
    {
        $this->apiCallNumber++;
    }

    /**
     * @return int
     */
    public function getMosqueQuota()
    {
        return $this->mosqueQuota;
    }

    /**
     * @param int $mosqueQuota
     */
    public function setMosqueQuota($mosqueQuota): void
    {
        $this->mosqueQuota = $mosqueQuota;
    }

    /**
     * @return bool
     */
    public function isUnsubscribeEmails(): ?bool
    {
        return $this->unsubscribeEmails;
    }

    /**
     * @param bool $unsubscribeEmails
     *
     * @return User
     */
    public function setUnsubscribeEmails(bool $unsubscribeEmails): User
    {
        $this->unsubscribeEmails = $unsubscribeEmails;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecaptcha()
    {
        return $this->recaptcha;
    }

    /**
     * @param mixed $recaptcha
     */
    public function setRecaptcha($recaptcha): void
    {
        $this->recaptcha = $recaptcha;
    }

    public function attributeApiAccessToken(PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('apiQuota')) {
            if (null !== $this->getApiQuota() && null === $this->getApiAccessToken()) {
                $this->setApiAccessToken(Uuid::uuid4());
            }
        }
    }

}

