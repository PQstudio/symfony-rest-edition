<?php

namespace Acme\DemoBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use JMS\DiExtraBundle\Annotation as DI;
use PQstudio\RestUtilityBundle\Controller\PQRestController;
use PQstudio\RateLimitBundle\Exception\RateLimitException;

class DemoController extends PQRestController
{
    /**
     * Returns collection of Medication
     *
     */
    public function getMedsAction(Request $request)
    {
        $this->exist(1);

        $meds = ['taest' => 'taestttttt'];
        $this->meta->setStatusCode('200');
        $view = $this->makeView(
            200,
            ['meta' => $this->meta->build(), 'medications' => $meds],
            'GET',
            false
        );
        //$data = ['test' => 'testttttt'];
        //$view = $this->view($data, 200)
        //;

        return $this->handleView($view);
    }

    public function postMedsAction(Request $request)
    {
        $this->exist(1);

        $meds = ['taestpost' => 'taestttttt'];
        $this->meta->setStatusCode('200');
        $view = $this->makeView(
            200,
            ['meta' => $this->meta->build(), 'medications' => $meds],
            'GET',
            false
        );
        //$data = ['test' => 'testttttt'];
        //$view = $this->view($data, 200)
        //;

        return $this->handleView($view);
    }

}
