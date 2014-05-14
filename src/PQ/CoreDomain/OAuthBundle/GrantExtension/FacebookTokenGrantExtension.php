<?php
namespace PQ\CoreDomain\OAuthBundle\GrantExtension;

use FOS\OAuthServerBundle\Storage\GrantExtensionInterface;
use OAuth2\Model\IOAuth2Client;
use JMS\DiExtraBundle\Annotation as DI;
use PQ\CoreDomain\UserBundle\Entity\User;

/**
 * @DI\Service("platform.grant_type.facebook_token")
 * @DI\Tag("fos_oauth_server.grant_extension", attributes = {"uri" = "http://platform.local/grant/facebook_token"})
 */
class FacebookTokenGrantExtension implements GrantExtensionInterface
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
        if (!isset($inputData['facebook_token'])) {
            return false;
        }

        $request = $this->httpClient->get('https://graph.facebook.com/v2.0/me?access_token='.$inputData['facebook_token']);
        try {
            $request->send();
        } catch (\Exception $e){
            return false;
        }
        $response = $request->getResponse();

        $json = $response->json();
        $facebookId = $json['id'];
        $email = $json['email'];

        $userFb = $this->userRepository->findByFacebookId($facebookId);
        if(null != $userFb) {
            $userFb->setFacebookAccessToken($inputData['facebook_token']);
            $this->userRepository->update($userFb);

            return array(
                'data' => $userFb
            );
        }

        $user = $this->userRepository->findByEmail($email);

        if(null != $user) {
            if(null == $user->getFacebookId() || "" == $user->getFacebookId()) {
                $user->setFacebookId($facebookId);
                $user->setFacebookAccessToken($inputData['facebook_token']);
                $this->userRepository->update($user);
            }

            return array(
                'data' => $user
            );
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFacebookId($facebookId);
        $user->setFacebookAccessToken($inputData['facebook_token']);
        $this->userRepository->add($user);

        return array(
            'data' => $user
        );
    }
}
