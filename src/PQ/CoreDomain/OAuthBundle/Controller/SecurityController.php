<?php
namespace PQ\CoreDomain\OAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends Controller
{
    public function invalidateTokenAction(Request $request)
    {
        $auth = $request->server->get('HTTP_AUTHORIZATION');
        //$headerToken = $request->request->get('access_token');
        $headerToken = substr($auth, 7);
        $tokenManager = $this->get('fos_oauth_server.access_token_manager');
        $token = $tokenManager->findTokenBy(['token' => $headerToken]);

        if(null === $token) {
            return new JsonResponse(['error' => 'token_null'], 400);
        }

        $tokenManager->deleteToken($token);

        return new JsonResponse([], 204);
    }

    public function checkTokenAction(Request $request)
    {
        $auth = $request->server->get('HTTP_AUTHORIZATION');
        //$headerToken = $request->request->get('access_token');
        $headerToken = substr($auth, 7);
        $tokenManager = $this->get('fos_oauth_server.access_token_manager');
        $token = $tokenManager->findTokenBy(['token' => $headerToken]);

        if(null === $token) {
            return new JsonResponse(['status' => 'unauthorized'], 200);
        }

        return new JsonResponse(['status' => 'authorized'], 200);
    }
}
