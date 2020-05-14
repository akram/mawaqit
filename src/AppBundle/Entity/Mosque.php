<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mosque
 */
class Mosque
{
    const STATUS_NEW = "NEW";
    const STATUS_VALIDATED = "VALIDATED";
    const STATUS_SUSPENDED = "SUSPENDED";
    const STATUS_CHECK = "CHECK";
    const STATUS_DUPLICATED = "DUPLICATED";
    const STATUS_SCREEN_PHOTO_ADDED = "SCREEN_PHOTO_ADDED";
    const STATUS_WATCHED = "WATCHED";
    const TYPE_MOSQUE = "MOSQUE";
    const TYPE_HOME = "HOME";
    const TYPE_SCHOOL = "SCHOOL";
    const TYPE_STORE = "STORE";
    const TYPE_ASSOCIATION = "ASSOCIATION";
    const TYPE_MUSALLA = "MUSALLA";
    const TYPES = [
        self::TYPE_MOSQUE,
        self::TYPE_MUSALLA,
        self::TYPE_HOME,
        self::TYPE_SCHOOL,
        self::TYPE_ASSOCIATION,
        self::TYPE_STORE,
    ];
    const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CHECK,
        self::STATUS_VALIDATED,
        self::STATUS_SUSPENDED,
        self::STATUS_DUPLICATED,
        self::STATUS_SCREEN_PHOTO_ADDED,
        self::STATUS_WATCHED
    ];

    const ACCESSIBLE_STATUSES = [
        self::STATUS_VALIDATED,
        self::STATUS_SCREEN_PHOTO_ADDED,
        self::STATUS_WATCHED
    ];

    const RESTRICTED_STATUSES = [
        self::STATUS_VALIDATED,
        self::STATUS_WATCHED
    ];

    const SUSPENSION_REASON_MOSQUE_CLOSED = "mosque_closed";
    const SUSPENSION_REASON_MOSQUE_PRAYER_TIMES_INCORRECT = "prayer_times_not_correct";
    const SUSPENSION_REASON_MISSING_PHOTO = "missing_photo";
    const SUSPENSION_REASON_MOSQUE_NOT_EXIST = "non_existing_mosque";
    const SUSPENSION_REASON_OTHER = "other";

    const STARTDATE_CHECKING_PHOTO = "2019-09-28";
    /**
     * @Groups({"elastic"})
     * @var UuidInterface
     */
    protected $uuid;
    /**
     * @Groups({"elastic"})
     * @var int
     */
    private $id;
    /**
     * @Groups({ "elastic"})
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $streamUrl;
    /**
     * @var string
     */
    private $type = "MOSQUE";
    /**
     * @Groups({"elastic"})
     * @var string
     */
    private $slug;
    /**
     * @var string
     */
    private $address;
    /**
     * @var string
     */
    private $city;
    /**
     * @var string
     */
    private $zipcode;
    /**
     * @var string
     */
    private $country;
    /**
     * @Groups({"elastic"})
     * @var float
     */
    private $latitude;
    /**
     * @Groups({"elastic"})
     * @var float
     */
    private $longitude;
    /**
     * @var string
     */
    private $countryFullName;
    /**
     * @Groups({"elastic"})
     * @var string
     */
    private $associationName;
    /**
     * @Groups({"elastic"})
     * @var string
     */
    private $phone;
    /**
     * @var string
     */
    private $rib;
    /**
     * @Groups({"elastic"})
     * @var string
     */
    private $paymentWebsite;
    /**
     * @Groups({"elastic"})
     * @var string
     */
    private $email;
    /**
     * @Groups({"elastic"})
     * @var string
     */
    private $site;
    /**
     * @var string
     */
    private $status = self::STATUS_NEW;
    /**
     * @var string
     */
    private $reason;
    /**
     * @var boolean
     */
    private $addOnMap = true;
    /**
     * @var File
     */
    private $justificatoryFile;
    /**
     * @var File
     */
    private $file1;
    /**
     * @var File
     */
    private $file2;
    /**
     * @var File
     */
    private $file3;
    /**
     * @var string
     */
    private $justificatory;
    /**
     * @var string
     */
    private $image1;
    /**
     * @var string
     */
    private $image2;
    /**
     * @var string
     */
    private $image3;
    /**
     * The choosen locale (language fr, ar, en, ...)
     * @var string
     */
    private $locale = 'fr';
    /**
     * @var \DateTime
     */
    private $created;
    /**
     * @var \DateTime
     */
    private $updated;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var ArrayCollection[Message]
     */
    private $messages;
    /**
     * @var Collection[Comment]
     */
    private $comments;
    /**
     * @var FlashMessage
     */
    private $flashMessage;
    /**
     * @var boolean|null
     */
    private $isCalendarCompleted = null;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $womenSpace;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $janazaPrayer;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $aidPrayer;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $childrenCourses;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $adultCourses;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $ramadanMeal;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $handicapAccessibility;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $ablutions;
    /**
     * @Groups({"elastic"})
     * @var boolean|null
     */
    private $parking;
    /**
     * @Groups({"elastic"})
     * @var string
     * @Assert\Length(max="200")
     */
    private $otherInfo;
    /**
     * @var boolean|null
     */
    private $synchronized;
    /**
     * @var int|null
     */
    private $emailScreenPhotoReminder;

    /**
     * @var array
     */
    private $similar = [];

    /**
     * @var int
     */
    private $mobileFavoriteCounter;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->configuration = new Configuration();
        $this->uuid = Uuid::uuid4();
        $this->created = new \DateTime();
    }

    public function __clone()
    {
        $this->id = null;
        $this->slug = null;
        $this->uuid = Uuid::uuid4();
        $this->image1 = null;
        $this->image2 = null;
        $this->image3 = null;
        $this->updated = null;
        $this->created = new \DateTime();
        $this->messages = null;
        $this->comments = null;
        $this->flashMessage = null;
        $this->emailScreenPhotoReminder = null;
        $this->reason = null;
        $this->configuration = clone $this->configuration;
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * for elastic geo point
     * @Groups({"elastic"})
     * @return array
     */
    function getLocation()
    {
        return [
            "lon" => $this->longitude,
            "lat" => $this->latitude,
        ];
    }

    /**
     * Get the title to display on top of screen
     * @return string
     */
    function getTitle()
    {
        if ($this->getType() !== self::TYPE_MOSQUE) {
            return "mosque.title.{$this->getType()}";
        }

        $name = $this->getName();
        if (strpos(strtolower($name), strtolower($this->getCity())) === false) {
            $name .= " - " . $this->getCity();
        }
        return $name;
    }

    /**
     * Label for web search autocomplete
     *
     * @Groups({"elastic"})
     * @return string
     */
    function getLabel()
    {
        return $this->getTitle();
    }

    /**
     * Get type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Mosque
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Mosque
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreamUrl(): ?string
    {
        return $this->streamUrl;
    }

    /**
     * @param string $streamUrl
     *
     * @return Mosque
     */
    public function setStreamUrl(?string $streamUrl): Mosque
    {
        $this->streamUrl = $streamUrl;
        return $this;
    }

    /**
     * Get city
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Mosque
     */
    public function setCity($city)
    {
        $transformedCity = [];
        $cityParts = preg_split('/\s+|\-+/', $city);
        foreach ($cityParts as $key => $part) {
            $transformedCity[$key] = ucfirst(strtolower($part));
        }
        $this->city = implode("-", $transformedCity);
        return $this;
    }

    /**
     * Get id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return UuidInterface
     */
    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface $uuid
     *
     * @return Mosque
     */
    public function setUuid(UuidInterface $uuid): Mosque
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Get name
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set name
     *
     * @param string $slug
     *
     * @return Mosque
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get address
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Mosque
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Get country
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return Mosque
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * Get associationName
     * @return string
     */
    public function getAssociationName()
    {
        return $this->associationName;
    }

    /**
     * Set associationName
     *
     * @param string $associationName
     *
     * @return Mosque
     */
    public function setAssociationName($associationName)
    {
        $this->associationName = $associationName;
        return $this;
    }

    /**
     * Get phone
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Mosque
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Get site
     * @return string
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set site
     *
     * @param string $site
     *
     * @return Mosque
     */
    public function setSite($site)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * Get zipcode
     * @return string
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Set zipcode
     *
     * @param string $zipcode
     *
     * @return Mosque
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
        return $this;
    }

    /**
     * Get rib
     * @return string
     */
    public function getRib()
    {
        return $this->rib;
    }

    /**
     * Set rib
     *
     * @param string $rib
     *
     * @return Mosque
     */
    public function setRib($rib)
    {
        $this->rib = $rib;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentWebsite(): ?string
    {
        return $this->paymentWebsite;
    }

    /**
     * @param string $paymentWebsite
     *
     * @return Mosque
     */
    public function setPaymentWebsite(?string $paymentWebsite): Mosque
    {
        $this->paymentWebsite = $paymentWebsite;
        return $this;
    }

    /**
     * Get email
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Mosque
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get updated
     * @return \DateTime
     */
    public function getUpdated()
    {
        $mosqueUpdated = $this->updated;
        $configurationUpdated = $this->configuration->getUpdated();
        if ($mosqueUpdated > $configurationUpdated) {
            return $mosqueUpdated;
        }
        return $configurationUpdated;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Mosque
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Get full address
     * @Groups({"elastic"})
     * @return string
     */
    public function getLocalisation()
    {
        return ($this->address ? $this->address . ' ' : '') . $this->zipcode . ' ' . $this->city . ' ' . $this->getCountryFullName();
    }

    /**
     * @return string
     */
    public function getCountryFullName()
    {
        return $this->countryFullName;
    }

    /**
     * @param string $countryFullName
     *
     * @return Mosque
     */
    public function setCountryFullName(string $countryFullName): Mosque
    {
        $this->countryFullName = $countryFullName;
        return $this;
    }

    function getUser()
    {
        return $this->user;
    }

    function setUser(User $user)
    {
        $this->user = $user;
    }

    function getConfiguration()
    {
        return $this->configuration;
    }

    function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return File|null
     */
    public function getJustificatoryFile()
    {
        return $this->justificatoryFile;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return self
     */
    public function setJustificatoryFile(File $file = null)
    {
        $this->justificatoryFile = $file;
        if ($file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updated = new \DateTime();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getJustificatory()
    {
        return $this->justificatory;
    }

    /**
     * @param string $justificatory
     *
     * @return $this
     */
    public function setJustificatory($justificatory)
    {
        $this->justificatory = $justificatory;
        return $this;
    }

    /**
     * @return File|null
     */
    public function getFile1()
    {
        return $this->file1;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return self
     */
    public function setFile1(File $image = null)
    {
        $this->file1 = $image;
        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updated = new \DateTime();
        }
        return $this;
    }

    /**
     * @return File|null
     */
    public function getFile2()
    {
        return $this->file2;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return self
     */
    public function setFile2(File $image = null)
    {
        $this->file2 = $image;
        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updated = new \DateTime();
        }
        return $this;
    }

    /**
     * @return File|null
     */
    public function getFile3()
    {
        return $this->file3;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return self
     */
    public function setFile3(File $image = null)
    {
        $this->file3 = $image;
        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updated = new \DateTime();
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getImage1()
    {
        return $this->image1;
    }

    /**
     * @param string $imageName
     *
     * @return self
     */
    public function setImage1($imageName)
    {
        $this->image1 = $imageName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getImage2()
    {
        return $this->image2;
    }

    /**
     * @param string $imageName
     *
     * @return self
     */
    public function setImage2($imageName)
    {
        $this->image2 = $imageName;
        return $this;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getEnabledMessages($desktop = null, $mobile = null)
    {

        $messages = [];

        foreach ($this->messages as $message) {
            if (!$message->isEnabled()) {
                continue;
            }

            if (true === $mobile && !$message->isMobile()) {
                continue;
            }

            if (true === $desktop && !$message->isDesktop()) {
                continue;
            }
            $messages[] = $message;

        }
        return $messages;
    }

    public function getNbOfEnabledMessages()
    {
        $nb = 0;
        foreach ($this->messages as $message) {
            if ($message->isEnabled()) {
                $nb++;
            }
        }
        return $nb;
    }

    public function addMessage(Message $message)
    {
        $this->messages->add($message);
        $message->setMosque($this);
        $this->setUpdated(new \DateTime());
    }

    public function setMessages(array $messages)
    {
        $this->clearMessages();
        foreach ($messages as $message) {
            $this->addMessage($message);
        }
    }

    public function clearMessages()
    {
        $this->messages = new ArrayCollection();
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): ?Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setMosque($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getMosque() === $this) {
                $comment->setMosque(null);
            }
        }

        return $this;
    }

    /**
     * get GPS coordinates
     * @return array
     */
    function getGpsCoordinates()
    {
        return [
            "lat" => $this->getLatitude(),
            "lon" => $this->getLongitude()
        ];
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     */
    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return bool
     */
    public function isAddOnMap()
    {
        return $this->addOnMap;
    }

    /**
     * @param bool $addOnMap
     */
    public function setAddOnMap($addOnMap)
    {
        $this->addOnMap = $addOnMap;
    }

    public function getTypes()
    {
        return self::TYPES;
    }

    public function resetToHome()
    {
        $this->addOnMap = false;
        $this->address = null;
        $this->site = null;
        $this->phone = null;
        $this->email = null;
        $this->associationName = null;
        $this->rib = null;
        $this->status = Mosque::STATUS_VALIDATED;
    }

    /**
     * @Groups({"elastic"})
     * @return string
     */
    public function getImage()
    {
        if (empty($this->image1)) {
            return 'https://mawaqit.net/bundles/app/prayer-times/img/default.jpg';
        }
        return "https://mawaqit.net/upload/" . $this->image1;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Mosque
     */
    public function setStatus(string $status): Mosque
    {
        $this->status = $status;
        return $this;
    }

    public function isSuspended()
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isAccessible()
    {
        return in_array($this->status, self::ACCESSIBLE_STATUSES);
    }

    public function isElasticIndexable()
    {
        return $this->isMosque() && $this->isFullyValidated();
    }

    /**
     * Cond 1 : Mosques only
     * Cond 2 : Must be validated
     * Cond 3 : Created after Mosque::STARTDATE_CHECKING_PHOTO
     * Cond 4 : The screen picture must be uploaded
     * @return bool
     */
    public function isFullyValidated()
    {
        if (!$this->isMosque()) {
            return true;
        }

        if (!$this->isValidated()) {
            return false;
        }

        if ($this->getCreated()->format("Y-m-d") < Mosque::STARTDATE_CHECKING_PHOTO) {
            return true;
        }

        if (empty($this->getImage3())) {
            return false;
        }

        return true;
    }

    public function isValidated()
    {
        return in_array($this->status, [
            self::STATUS_VALIDATED,
            self::STATUS_WATCHED,
        ]);
    }

    public function isCheckingInProgress()
    {
        return $this->status === self::STATUS_CHECK;
    }

    public function isConfigurationAllowed()
    {
        if ($this->isValidated()) {
            return true;
        }
        return in_array($this->status, [
            self::STATUS_SUSPENDED
        ]);
    }

    public function isActionsAllowed()
    {
        if ($this->isValidated()) {
            return true;
        }
        return in_array($this->status, [
            self::STATUS_CHECK,
            self::STATUS_SUSPENDED,
            self::STATUS_NEW,
        ]);
    }

    public function isEditAllowed()
    {
        if ($this->isValidated()) {
            return true;
        }

        return in_array($this->status, [
            self::STATUS_CHECK,
        ]);
    }

    public function isNew()
    {
        return $this->status === self::STATUS_NEW;
    }

    public function hasScreenPhotoAdded()
    {
        return $this->status === self::STATUS_SCREEN_PHOTO_ADDED;
    }

    public function statusClass()
    {
        return strtolower($this->status);
    }

    /**
     * True if calendar is completed
     * @return boolean
     */
    function isCalendarCompleted()
    {
        if ($this->isCalendarCompleted === null) {
            $this->isCalendarCompleted = true;
            $configuration = $this->configuration;
            if ($configuration->isCalendar()) {
                if (!empty($configuration->getDecodedCalendar())) {
                    foreach ($configuration->getDecodedCalendar() as $month => $days) {
                        foreach ($days as $day => $prayers) {
                            foreach ($prayers as $prayerIndex => $prayer) {
                                if (empty($prayer)) {
                                    $this->isCalendarCompleted = false;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->isCalendarCompleted;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return bool|null
     */
    public function getWomenSpace(): ?bool
    {
        return $this->womenSpace;
    }

    /**
     * @param bool|null $womenSpace
     *
     * @return Mosque
     */
    public function setWomenSpace(?bool $womenSpace): Mosque
    {
        $this->womenSpace = $womenSpace;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getJanazaPrayer(): ?bool
    {
        return $this->janazaPrayer;
    }

    /**
     * @param bool|null $janazaPrayer
     *
     * @return Mosque
     */
    public function setJanazaPrayer(?bool $janazaPrayer): Mosque
    {
        $this->janazaPrayer = $janazaPrayer;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getChildrenCourses(): ?bool
    {
        return $this->childrenCourses;
    }

    /**
     * @param bool|null $childrenCourses
     *
     * @return Mosque
     */
    public function setChildrenCourses(?bool $childrenCourses): Mosque
    {
        $this->childrenCourses = $childrenCourses;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAdultCourses(): ?bool
    {
        return $this->adultCourses;
    }

    /**
     * @param bool|null $adultCourses
     *
     * @return Mosque
     */
    public function setAdultCourses(?bool $adultCourses): Mosque
    {
        $this->adultCourses = $adultCourses;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getRamadanMeal(): ?bool
    {
        return $this->ramadanMeal;
    }

    /**
     * @param bool|null $ramadanMeal
     *
     * @return Mosque
     */
    public function setRamadanMeal(?bool $ramadanMeal): Mosque
    {
        $this->ramadanMeal = $ramadanMeal;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getHandicapAccessibility(): ?bool
    {
        return $this->handicapAccessibility;
    }

    /**
     * @param bool|null $handicapAccessibility
     *
     * @return Mosque
     */
    public function setHandicapAccessibility(?bool $handicapAccessibility): Mosque
    {
        $this->handicapAccessibility = $handicapAccessibility;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAblutions(): ?bool
    {
        return $this->ablutions;
    }

    /**
     * @param bool|null $ablutions
     *
     * @return Mosque
     */
    public function setAblutions(?bool $ablutions): Mosque
    {
        $this->ablutions = $ablutions;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getParking(): ?bool
    {
        return $this->parking;
    }

    /**
     * @param bool|null $parking
     *
     * @return Mosque
     */
    public function setParking(?bool $parking): Mosque
    {
        $this->parking = $parking;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAidPrayer(): ?bool
    {
        return $this->aidPrayer;
    }

    /**
     * @param bool|null $aidPrayer
     *
     * @return Mosque
     */
    public function setAidPrayer(?bool $aidPrayer): Mosque
    {
        $this->aidPrayer = $aidPrayer;
        return $this;
    }

    public function showUsefullInfo()
    {
        return $this->parking !== null;
    }

    /**
     * @return FlashMessage|null
     */
    public function getFlashMessage(): ?FlashMessage
    {
        return $this->flashMessage;
    }

    /**
     * @param FlashMessage $flashMessage
     */
    public function setFlashMessage(FlashMessage $flashMessage = null): void
    {
        $this->flashMessage = $flashMessage;
        $this->setUpdated(new \DateTime());
    }

    /**
     * @return bool|null
     */
    public function getSynchronized(): ?bool
    {
        return $this->synchronized;
    }

    /**
     * @param bool|null $synchronized
     */
    public function setSynchronized(?bool $synchronized): void
    {
        $this->synchronized = $synchronized;
    }

    public function getConf(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getOtherInfo(): ?string
    {
        return $this->otherInfo;
    }

    /**
     * @param string $otherInfo
     */
    public function setOtherInfo(string $otherInfo = null): void
    {
        $this->otherInfo = $otherInfo;
    }

    /**
     * @return string
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function setReason(string $reason = null): void
    {
        $this->reason = $reason;
    }

    /**
     * @return int|null
     */
    public function getEmailScreenPhotoReminder(): ?int
    {
        return $this->emailScreenPhotoReminder;
    }

    /**
     * @param int|null $emailScreenPhotoReminder
     *
     * @return Mosque
     */
    public function setEmailScreenPhotoReminder(int $emailScreenPhotoReminder = null): Mosque
    {
        $this->emailScreenPhotoReminder = $emailScreenPhotoReminder;
        return $this;
    }

    public function incrementEmailScreenPhotoReminder()
    {
        $this->emailScreenPhotoReminder++;
    }

    public function suspend($reaseon)
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->reason = $reaseon;
    }

    /**
     * Get number of days before the mosque will be removed
     * Condition: Screen photo missed and mosque created after 2019-09-28
     */
    public function daysBeforeRemove()
    {
        if (!$this->isMosque()) {
            return null;
        }

        if ($this->getImage3()) {
            return null;
        }

        if ($this->getCreated() < new \DateTime(self::STARTDATE_CHECKING_PHOTO)) {
            return null;
        }

        $deadline = clone $this->created;
        $createdDay = (int)$this->created->format("d");
        if ($createdDay < 15) {
            $deadline->modify("last day of next month");
        }
        if ($createdDay >= 15) {
            // special case, to prevent 28 and 29 february bug
            if ((int)$deadline->format('m') === 12 && $createdDay >= 28) {
                $deadline->setDate($deadline->format('Y'), $deadline->format('m'), 28);
            }

            $deadline->modify("+2 months");
            $deadline->setDate($deadline->format('Y'), $deadline->format('m'), 15);
        }

        $now = new \DateTime();
        $interval = $deadline->diff($now);

        $days = (int)$interval->format('%a');

        if ($deadline < $now) {
            $days = -$days;
        }

        return $days;
    }

    public function isMosque()
    {
        return $this->type === self::TYPE_MOSQUE;
    }

    /**
     * @return string|null
     */
    public function getImage3()
    {
        return $this->image3;
    }

    /**
     * @param string $imageName
     *
     * @return self
     */
    public function setImage3($imageName)
    {
        $this->image3 = $imageName;
        return $this;
    }

    /**
     * Get created
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Mosque
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    public function getIcon()
    {
        if ($this->type === self::TYPE_MUSALLA) {
            return 'pray';
        }

        return strtolower($this->type);
    }

    /**
     * @Groups({"elastic"})
     * @return string
     */
    function getJumua()
    {
        return $this->getConf()->getJumuaTime();
    }

    /**
     * @Groups({"elastic"})
     * @return string
     */
    function getJumua2()
    {
        return $this->getConf()->getJumuaTime2();
    }

    /**
     * @return array
     */
    public function getSimilar(): array
    {
        return $this->similar;
    }

    /**
     * @param array $similar
     *
     * @return Mosque
     */
    public function setSimilar(array $similar): Mosque
    {
        $this->similar = $similar;
        return $this;
    }

    /**
     * @param int $mobileFavoriteCounter
     *
     * @return Mosque
     */
    public function setMobileFavoriteCounter(?int $mobileFavoriteCounter): Mosque
    {
        $this->mobileFavoriteCounter = $mobileFavoriteCounter;
        return $this;
    }

    /**
     * @return int
     */
    public function getMobileFavoriteCounter(): ?int
    {
        return $this->mobileFavoriteCounter;
    }

}