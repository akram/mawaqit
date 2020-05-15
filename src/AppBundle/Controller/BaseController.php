<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class BaseController extends Controller
{
    protected function token()
    {
        return strrev($this->getParameter("mawaqit_api_access_token"));
    }

}
