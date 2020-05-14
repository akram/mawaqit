<?php

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestService
{

    /**
     * @var Request
     */
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getMasterRequest();
    }

    /**
     * Raspberry local
     * @return bool
     */
    public function isLocal()
    {
        if(!$this->request instanceof Request){
            return true;
        }

        if ($this->request->getHost() === 'mawaqit.local'){
            return true;
        }

        if ($this->request->getHost() === 'localhost'){
            return true;
        }

        if ($this->request->getHost() === '127.0.0.1'){
            return true;
        }

        if (strpos($this->request->getHost(), "192.168") !== false){
            return true;
        }

        return false;
    }
}
