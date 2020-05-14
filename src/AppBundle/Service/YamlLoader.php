<?php

namespace AppBundle\Service;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class YamlLoader
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * YamlLoader constructor.
     *
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function getSupport()
    {
        return $this->get("support");
    }

    public function getCountries()
    {
        return $this->get("countries");
    }

    private function get($file)
    {
        try {
            return Yaml::parseFile("{$this->rootDir}/Resources/yaml/{$file}.yml");
        } catch (ParseException $e) {
            return [];
        }
    }
}