<?php

namespace AppBundle\Util;

class YoutubeHelper
{

    public const URL_PATTERN = "https://www.youtube.com/embed/%s";
    public const ID_REGEX = "%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^\"&?/ ]{11})%i";

    static function buildEmbedUrl($url)
    {
        return sprintf(self::URL_PATTERN, self::extractId($url));
    }

    static function extractId($url)
    {
        preg_match(self::ID_REGEX, $url,$match);
        return $match[1];
    }

}