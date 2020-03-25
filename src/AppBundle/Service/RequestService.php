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

        return $this->request->getHost() === 'mawaqit.local';
    }
}
