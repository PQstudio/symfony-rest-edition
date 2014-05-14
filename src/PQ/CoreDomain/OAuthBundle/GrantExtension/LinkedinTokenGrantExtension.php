<?php
namespace PQ\CoreDomain\OAuthBundle\GrantExtension;

use FOS\OAuthServerBundle\Storage\GrantExtensionInterface;
use OAuth2\Model\IOAuth2Client;
use JMS\DiExtraBundle\Annotation as DI;
use PQ\CoreDomain\UserBundle\Entity\User;

/**
 * @DI\Service("platform.grant_type.linkedin_token")
 * @DI\Tag("fos_oauth_server.grant_extension", attributes = {"uri" = "http://platform.local/grant/linkedin_token"})
 */
class LinkedinTokenGrantExtension implements GrantExtensionInterface
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
        if (!isset($inputData['linkedin_token'])) {
            return false;
        }

        $request = $this->httpClient->get('https://api.linkedin.com/v1/people/~:(id,email-address)?format=json&oauth2_access_token='.$inputData['linkedin_token']);
        try {
            $request->send();
        } catch (\Exception $e){
            return false;
        }
        $response = $request->getResponse();

        $json = $response->json();
        $linkedinId = $json['id'];
        $email = $json['emailAddress'];

        $userLinkedin = $this->userRepository->findByLinkedinId($linkedinId);
        if(null != $userLinkedin) {
            $userLinkedin->setLinkedinAccessToken($inputData['linkedin_token']);
            $this->userRepository->update($userLinkedin);

            return array(
                'data' => $userLinkedin
            );
        }

        $user = $this->userRepository->findByEmail($email);

        if(null != $user) {
            if(null == $user->getLinkedinId() || "" == $user->getLinkedinId()) {
                $user->setLinkedinId($linkedinId);
                $user->setLinkedinAccessToken($inputData['linkedin_token']);
                $this->userRepository->update($user);
            }

            return array(
                'data' => $user
            );
        }

        $user = new User();
        $user->setEmail($email);
        $user->setLinkedinId($linkedinId);
        $user->setLinkedinAccessToken($inputData['linkedin_token']);
        $this->userRepository->add($user);

        return array(
            'data' => $user
        );
    }
}
