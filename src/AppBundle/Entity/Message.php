<?php

namespace AppBundle\Entity;

use AppBundle\Util\YoutubeHelper;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Message
 */
class Message
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $content;

    /**
     * @var boolean
     */
    private $enabled = true;

    /**
     * Display on mobile
     * @var boolean
     */
    private $mobile = true;

    /**
     * Display on main mosque screen
     * @var boolean
     */
    private $desktop = true;

    /**
     * @var integer
     */
    private $position;

    /**
     * @var File
     */
    private $file;

    /**
     * @var string
     */
    private $image;

    /**
     * @var string
     */
    private $video;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var Mosque
     */
    private $mosque;

    public function __toString()
    {
        return $this->getTitle() ? $this->getTitle() : "Message";
    }

    /**
     * Get title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Message
     */
    public function setTitle($title)
    {
        $this->title = $title;

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

    /**
     * Get content
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Message
     */
    public function setContent($content)
    {
        $content = preg_replace("/<li>|<ul>|<ol>|<\/ul>|<\/ol>/i", "", $content);
        $content = preg_replace("/<\/li>/i", "<br>", $content);
        $this->content = $content;

        return $this;
    }

    function isEnabled()
    {
        return $this->enabled;
    }

    function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return File|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param File|null $image
     *
     * @return $this
     * @throws \Exception
     */
    public function setFile(File $image = null)
    {
        $this->file = $image;

        if ($image) {
            $this->updated = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $imageName
     *
     * @return self
     */
    public function setImage($imageName)
    {
        $this->image = $imageName;

        return $this;
    }

    function getMosque(): Mosque
    {
        return $this->mosque;
    }

    function setMosque($mosque)
    {
        $this->mosque = $mosque;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     *
     * @return self
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMobile(): bool
    {
        return $this->mobile;
    }

    /**
     * @param bool $mobile
     *
     * @return Message
     */
    public function setMobile(bool $mobile): Message
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDesktop(): bool
    {
        return $this->desktop;
    }

    /**
     * @param bool $desktop
     */
    public function setDesktop(bool $desktop): void
    {
        $this->desktop = $desktop;
    }

    /**
     * @return string
     */
    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function getEmbedVideo(): ?string
    {
        if (empty($this->video)) {
            return null;
        }

        $id = YoutubeHelper::extractId($this->video);

        return YoutubeHelper::buildEmbedUrl(
                $this->video
            ) . "?enablejsapi=1&rel=0&loop=1&iv_load_policy=3&playlist={$id}";
    }

    /**
     * @param string $video
     *
     * @return Message
     */
    public function setVideo(?string $video): Message
    {
        $this->video = $video;

        return $this;
    }

}
