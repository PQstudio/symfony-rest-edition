<?php
namespace PQ\CoreDomain\OAuthBundle\GrantExtension;

use FOS\OAuthServerBundle\Storage\GrantExtensionInterface;
use OAuth2\Model\IOAuth2Client;
use JMS\DiExtraBundle\Annotation as DI;
use PQ\CoreDomain\UserBundle\Entity\User;

/**
 * @DI\Service("platform.grant_type.google_token")
 * @DI\Tag("fos_oauth_server.grant_extension", attributes = {"uri" = "http://platform.local/grant/google_token"})
 */
class GoogleTokenGrantExtension implements GrantExtensionInterface
{
    protected $userRepository;

    protected $httpClient;

    /**
     * @DI\InjectParams({
     *     "userRepository" = @DI\Inject("user_repository"),
     *     "httpClient" = @DI\Inject("guzzle.client")
     * })
     */
    public function __construct($userRepository, $httpClient)
    {
        $this->userRepository = $userRepository;
        $this->httpClient = $httpClient;
    }

    /*
     * {@inheritdoc}
     */
    public function checkGrantExtension(IOAuth2Client $client, array $inputData, array $authHeaders)
    {
        // Check that the input data is correct
        if (!isset($inputData['google_token'])) {
            return false;
        }

        $request = $this->httpClient->get('https://www.googleapis.com/plus/v1/people/me?access_token='.$inputData['google_token']);
        try {
            $request->send();
        } catch (\Exception $e){
            return false;
        }
        $response = $request->getResponse();

        $json = $response->json();
        $googleId = $json['id'];
        $email;
        foreach($json['emails'] as $em) {
            if($em['type'] == 'account') {
                $email = $em['value'];
            }
        }

        $userGoogle = $this->userRepository->findByGoogleId($googleId);
        if(null != $userGoogle) {
            $userGoogle->setGoogleAccessToken($inputData['google_token']);
            $this->userRepository->update($userGoogle);

            return array(
                'data' => $userGoogle
            );
        }

        $user = $this->userRepository->findByEmail($email);

        if(null != $user) {
            if(null == $user->getGoogleId() || "" == $user->getGoogleId()) {
                $user->setGoogleId($googleId);
                $user->setGoogleAccessToken($inputData['google_token']);
                $this->userRepository->update($user);
            }

            return array(
                'data' => $user
            );
        }

        $user = new User();
        $user->setEmail($email);
        $user->setGoogleId($googleId);
        $user->setGoogleAccessToken($inputData['google_token']);
        $this->userRepository->add($user);

        return array(
            'data' => $user
        );
    }
}
