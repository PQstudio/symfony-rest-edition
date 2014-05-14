<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new JMS\AopBundle\JMSAopBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),

            new Snc\RedisBundle\SncRedisBundle(),
            new Misd\GuzzleBundle\MisdGuzzleBundle(),

            new cspoo\Swiftmailer\MailgunBundle\cspooSwiftmailerMailgunBundle(),
            new Dmishh\Bundle\RecaptchaBundle\RecaptchaBundle(),

            new FOS\RestBundle\FOSRestBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new FOS\UserBundle\FOSUserBundle(),

            new PQstudio\RestUtilityBundle\PQstudioRestUtilityBundle(),
            new PQstudio\RateLimitBundle\PQstudioRateLimitBundle(),

            new PQ\CoreDomain\OAuthBundle\PQOAuthBundle(),
            new PQ\CoreDomain\UserBundle\PQUserBundle(),
            new PQ\CoreDomain\UtilityBundle\PQUtilityBundle(),

        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');

        //$envParameters = $this->getEnvParameters();
        //$loader->load(function($container) use($envParameters) {
            //$container->getParameterBag()->add($envParameters);
        //});
    }
}
