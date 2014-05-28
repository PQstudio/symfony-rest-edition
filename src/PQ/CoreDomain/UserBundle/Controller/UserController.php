<?php
namespace PQ\CoreDomain\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use PQstudio\RestUtilityBundle\Exception\PQHttpException;
use PQstudio\RestUtilityBundle\Controller\PQRestController;

use PQ\CoreDomain\UserBundle\Entity\User;
use PQ\CoreDomain\UserBundle\Event\UserEvent;
use PQ\CoreDomain\UserBundle\Event\UserEvents;

use Mailgun\Mailgun;


class UserController extends PQRestController
{
    /**
     * Returns collection of Post
     *
     */
    public function getUsersAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $this->permissionDenied();
        }

        $this->setOffsetAndLimit($request);

        $users = $this->get('user_repository')->findAll($this->limit, $this->offset);
        $count = $this->get('user_repository')->findAllCount();

        $this->meta->setCount($count);

        $view = $this->makeView(
            200,
            ['meta' => $this->meta->build(), 'users' => $users],
            ['GET', 'Default'],
            false
        );

        return $this->handleView($view);
    }

    /**
     * Finds user by token
     */
    public function getUsersMeAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();

        $this->exist($user);

        if (false === $this->get('security.context')->isGranted('VIEW', $user)) {
            $this->permissionDenied();
        }

        $view = $this->makeView(
            200,
            ['user' => $user],
            ['GET', 'Default'],
            true
        );

        return $this->handleView($view);
    }

    public function putUsersForgotpasswordAction(Request $request)
    {
        $email = $request->request->get('email');
        $user = $this->get('user_repository')->findByEmail($email);

        $view = $this->view();

        if(!$user instanceof User) {
            $code = 204;
            $view
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $user->setChangePassToken($this->get('fos_user.util.token_generator')->generateToken());
        $user->setChangePassTokenDate(new \DateTime('now'));

        $this->get('user_repository')->update($user);

        $this->get('event_dispatcher')->dispatch(UserEvents::ForgotPasswordRequest, new UserEvent($user));

        $code = 204;
        $view
             ->setStatusCode($code)
        ;

        return $this->handleView($view);
    }

    public function patchUsersChangepasswordAction(Request $request)
    {
        $token = $request->request->get('token');
        $password = $request->request->get('password');

        $user = $this->get('user_repository')->findByChangePassToken($token);

        $view = $this->view();

        if(!$user instanceof User) {
            $code = 400;
            $this->meta->setStatusCode($code)
                       ->setError('token_doesnt_exist')
                       ->setErrorMessage('Providen token does not exist in the database')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        if($password === null) {
            $code = 400;
            $this->meta->setStatusCode($code)
                       ->setError('password_not_set')
                       ->setErrorMessage('There were no password set')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $date = new \DateTime('now');
        $date->modify('-10 minutes');
        if($user->getChangePassTokenDate() <= $date) {
            $code = 422;
            $this->meta->setStatusCode($code)
                       ->setError('token_time_expired')
                       ->setErrorMessage('Token time expired')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;

            $user->setChangePassToken(null);
            $user->setChangePassTokenDate(null);

            $this->get('user_repository')->update($user);

            return $this->handleView($view);
        }

        $user->setPlainPassword($password);
        $validation = $this->validate($user, ['Profile']);

        if($validation !== true) {
            $code = 400;
            $this->meta->setStatusCode($code)
                       ->setError('validation_error')
                       ->setErrorMessage('There was a problem with request validation')
            ;
            $view->setData(['meta' => $this->meta->build(), 'user' => $validation])
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $user->setChangePassToken(null);
        $user->setChangePassTokenDate(null);

        $this->get('user_repository')->update($user);

        $this->get('event_dispatcher')->dispatch(UserEvents::ForgotPasswordChanged, new UserEvent($user));

        $code = 204;
        $view
             ->setStatusCode($code)
        ;

        return $this->handleView($view);
    }

    /**
     * Returns single User by id.
     *
     */
    public function getUserAction($id)
    {
        $user = $this->get('user_repository')->find($id);

        $this->exist($user);

        if (false === $this->get('security.context')->isGranted('VIEW', $user)) {
            $this->permissionDenied();
        }

        $view = $this->makeView(
            200,
            ['user' => $user],
            ['GET', 'Default'],
            true
        );

        return $this->handleView($view);
    }

    /**
     * Adds new User.
     *
     */
    public function postUserAction(Request $request)
    {
        $user = $this->deserialize(
            $request->getContent(),
            $this->container->getParameter('user_entity'),
            ['POST'],
            ['Profile']
        );

        $this->get('user_repository')->add($user);
        $this->get('event_dispatcher')->dispatch(UserEvents::Register, new UserEvent($user));

        $view = $this->makeView(
            201,
            ['meta' => $this->meta->build(), 'user' => $user],
            'GET',
            true
        );

        return $this->handleView($view);
    }

    public function patchUserAction(Request $request, $id)
    {
        $user = $this->get('user_repository')->find($id);
        $this->exist($user);

        if (false === $this->get('security.context')->isGranted('EDIT', $user)) {
            $this->permissionDenied();
        }

        $user = $this->deserialize(
            $request->getContent(),
            $this->container->getParameter('user_entity'),
            ['PATCH'],
            ['Profile'],
            $id
        );

        $view = $this->view();

        $encoderService = $this->get('security.encoder_factory');
        $encoder = $encoderService->getEncoder($user);

        if($user->getCurrentPassword() != null && false === $encoder->isPasswordValid($user->getPassword(), $user->getCurrentPassword(), $user->getSalt())) {
            $code = 422;
            $this->meta->setStatusCode($code)
                       ->setError('bad_current_password')
                       ->setErrorMessage('Bad current password provided.')
            ;
            $view->setData(['meta' => $this->meta->build()])
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $this->get('user_repository')->update($user);

        $view = $this->makeView(
            204,
            ['meta' => $this->meta->build()],
            [],
            true
        );

        return $this->handleView($view);
    }

    public function putUsersConfirmemailAction(Request $request)
    {
        $token = $request->query->get('token');

        $user = $this->get('user_repository')->findByConfirmationToken($token);

        $view = $this->view();

        if(!$user instanceof User) {
            $code = 422;
            $this->meta->setError('token_not_correct')
                       ->setErrorMessage('Token is not correct')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $this->get('user_repository')->update($user);

        $code = 204;
        $this->meta->setStatusCode($code);
        $view->setData($this->meta->build())
             ->setStatusCode($code)
        ;

        return $this->handleView($view);
    }

    public function putUsersConfirmchangeemailAction(Request $request)
    {
        $token = $request->query->get('token');

        $user = $this->get('user_repository')->findByRevertToken($token);

        $view = $this->view();

        if(!$user instanceof User) {
            $code = 422;
            $this->meta->setError('token_not_correct')
                       ->setErrorMessage('Token is not correct')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $user->setRevertToken(null);
        $user->setRevertTokenDate(null);
        $user->setOldEmail(null);

        $this->get('user_repository')->update($user);

        $code = 204;
        $view
             ->setStatusCode($code)
        ;

        return $this->handleView($view);
    }

    public function putUsersRevertemailAction(Request $request)
    {
        $token = $request->query->get('token');

        $user = $this->get('user_repository')->findByRevertToken($token);

        $view = $this->view();

        if(!$user instanceof User) {
            $code = 422;
            $this->meta->setError('token_not_correct')
                       ->setErrorMessage('Token is not correct')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $date = new \DateTime('now');
        $date->modify('-72 hours');
        if($user->getRevertTokenDate() <= $date) {
            $code = 422;
            $this->meta->setError('token_time_expired')
                       ->setErrorMessage('Token time expired')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;

            $user->setRevertToken(null);
            $user->setRevertTokenDate(null);
            $user->setOldEmail(null);

            $this->get('user_repository')->update($user);

            return $this->handleView($view);
        }

        $user->setRevertToken(null);
        $user->setRevertTokenDate(null);
        $user->setEmail($user->getOldEmail());
        $user->setOldEmail(null);
        $user->setPassword(null);
        $user->isEmailReverted = true;

        $this->get('user_repository')->update($user);

        $code = 204;
        $view
             ->setStatusCode($code)
        ;

        return $this->handleView($view);
    }

    public function putUsersResendemailAction(Request $request, $id)
    {
        $user = $this->get('user_repository')->find($id);
        $this->exist($user);

        if (false === $this->get('security.context')->isGranted('OWNER', $user)) {
            $this->permissionDenied();
        }

        $view = $this->view();

        if($user->getConfirmationToken() == null) {
            $code = 422;
            $this->meta->setError('email_already_confirmed')
                       ->setErrorMessage('Email is already confirmed')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $user->setConfirmationToken($this->get('fos_user.util.token_generator')->generateToken());

        $this->get('user_repository')->update($user);

        $templateName = "PQUserBundle:User:confirmEmail.email.twig";
        $data = ['user' => $user];
        $to = $user->getEmail();

        $this->get('mailer.twig')->send($templateName, $data, $this->container->getParameter('mailer.from'), $to, $this->container->getParameter('mailer.fromName'));

        $code = 204;
        $this->meta->setStatusCode($code);
        $view->setData($this->meta->build())
             ->setStatusCode($code)
        ;

        return $this->handleView($view);
    }

    //public function postUsersConfirmationGenerateAction(Request $request)
    //{
        //$email = $request->request->get('email');
        //$user = $this->get('user_repository')->findByEmail($email);

        //$view = $this->view();

        //if(!$user instanceof User) {
            //$code = 400;
            //$this->meta->setStatusCode($code)
                       //->setError('email_doesnt_exist')
                       //->setErrorMessage('Providen email does not exist in the database')
            //;
            //$view->setData(['meta' => $this->meta->build()])
                 //->setStatusCode($code)
            //;
            //return $this->handleView($view);
        //}

        //if($user->getConfirmationToken() === null) {
            //$code = 400;
            //$this->meta->setStatusCode($code)
                       //->setError('token_not_set')
                       //->setErrorMessage('There were no token set to given email')
            //;
            //$view->setData(['meta' => $this->meta->build()])
                 //->setStatusCode($code)
            //;
            //return $this->handleView($view);
        //}

        //$user->setConfirmationToken($this->get('fos_user.util.token_generator')->generateToken());

        //$this->get('user_repository')->update($user);

        //$code = 204;
        //$this->meta->setStatusCode($code);
        //$view->setData(['meta' => $this->meta->build()])
             //->setStatusCode($code)
        //;

        //return $this->handleView($view);
    //}



    /**
     * Deletes given User.
     *
     */
    public function deleteUserAction($id)
    {
        $user = $this->get('user_repository')->find($id);
        $this->exist($user);

        if (false === $this->get('security.context')->isGranted('DELETE', $user)) {
            $this->permissionDenied();
        }

        $user = $this->deserialize(
            $request->getContent(),
            $this->container->getParameter('user_entity'),
            ['PATCH'],
            ['Profile'],
            $id
        );

        $view = $this->view();

        $encoderService = $this->get('security.encoder_factory');
        $encoder = $encoderService->getEncoder($user);

        if($user->getCurrentPassword() == null || false === $encoder->isPasswordValid($user->getPassword(), $user->getCurrentPassword(), $user->getSalt())) {
            $code = 422;
            $this->meta->setStatusCode($code)
                       ->setError('wrong_current_password')
                       ->setErrorMessage('Wrong current password provided.')
            ;
            $view->setData(['meta' => $this->meta->build()])
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $this->get('user_repository')->remove($user);

        $code = 204;
        $this->meta->setStatusCode($code);
        $view->setData(['meta' => $this->meta->build()])
             ->setStatusCode($code)
        ;

        return $this->handleView($view);
    }

    /**
     * Get user notifications
     */
    public function getUsersNotificationsAction(Request $request, $id)
    {
        $user = $this->get('user_repository')->find($id);
        $this->exist($user);

        if (false === $this->get('security.context')->isGranted('OWNER', $user)) {
            $this->permissionDenied();
        }
        $this->setOffsetAndLimit($request);

        $notifications = $this->get('notification_repository')->findAllForUser($user, null, $this->limit, $this->offset);
        $count = $this->get('notification_repository')->findAllForUserCount($user, null);

        $notReadCount = $this->get('notification_repository')->findAllNotReadForUserCount($user, null);

        $this->meta->setCount($count);

        $meta = $this->meta->build();
        $meta['notReadCount'] = $notReadCount;

        $view = $this->makeView(
            200,
            ['meta' => $meta, 'notifications' => $notifications],
            ['GET'],
            false
        );

        return $this->handleView($view);
    }

    public function patchNotificationAction(Request $request, $id)
    {
        $notification = $this->get('notification_repository')->find($id);
        $this->exist($notification);

        if (false === $this->get('security.context')->isGranted('EDIT', $notification)) {
            $this->permissionDenied();
        }

        $notification = $this->deserialize(
            $request->getContent(),
            $this->container->getParameter('notification_entity'),
            ['PATCH'],
            [],
            $id
        );

        $view = $this->view();

        $this->get('notification_repository')->update($notification);

        $view = $this->makeView(
            204,
            ['meta' => $this->meta->build()],
            [],
            true
        );

        return $this->handleView($view);
    }

    /**
     * Finds users linked accounts
     */
    public function getUsersLinksAction($id)
    {
        $user = $this->get('user_repository')->find($id);
        $this->exist($user);

        if('employee' != $user->getType()) {
            $this->permissionDenied();
        }

        if (false === $this->get('security.context')->isGranted('OWNER', $user)) {
            $this->permissionDenied();
        }

        $providers = [];
        if($user->getPassword() != null || $user->getPassword() != "") {
            $providers['password'] = $user->getEmail();
        }

        if($user->getFacebookId() != null || $user->getFacebookId() != "") {
            $providers['facebook'] = $user->getFacebookId();
        }

        if($user->getGoogleId() != null || $user->getGoogleId() != "") {
            $providers['google'] = $user->getGoogleId();
        }

        if($user->getLinkedinId() != null || $user->getLinkedinId() != "") {
            $providers['linkedin'] = $user->getLinkedinId();
        }

        $view = $this->makeView(
            200,
            ['provider' => $providers],
            [],
            true
        );

        return $this->handleView($view);
    }

    /**
     * Link social network account
     */
    public function postUserLinkAction(Request $request, $id, $slug)
    {
        $user = $this->get('user_repository')->find($id);
        $this->exist($user);

        if('employee' != $user->getType()) {
            $this->permissionDenied();
        }

        if (false === $this->get('security.context')->isGranted('OWNER', $user)) {
            $this->permissionDenied();
        }

        $view = $this->view();

        if(false == in_array($slug, ['facebook', 'google', 'linkedin'])) {
            $code = 400;
            $this->meta->setError('unknown_provider')
                       ->setErrorMessage('Provider specified doesn\'t exist')
            ;
            $view->setData(['meta' => $this->meta->build()])
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        $decoded = json_decode($request->getContent());
        $accessToken = $decoded->access_token;

        $httpClient = $this->get('guzzle.client');
        switch($slug) {
        case 'facebook':
            $request = $httpClient->get('https://graph.facebook.com/v2.0/me?access_token='.$accessToken);
            try {
                $request->send();
            } catch (\Exception $e){
                $code = 400;
                $this->meta->setError('wrong_access_token')
                           ->setErrorMessage('Provided access_token is incorrect or expired')
                ;
                $view->setData($this->meta->build())
                     ->setStatusCode($code)
                ;
                return $this->handleView($view);
            }
            $response = $request->getResponse();

            $json = $response->json();
            $facebookId = $json['id'];

            $fbUser = $this->get('user_repository')->findByFacebookId($facebookId);
            if(null != $fbUser) {
                $code = 422;
                $this->meta->setError('accout_already_linked')
                           ->setErrorMessage('Account is already linked')
                ;
                $view->setData($this->meta->build())
                     ->setStatusCode($code)
                ;
                return $this->handleView($view);
            }

            $user->setFacebookId($facebookId);
            $user->setFacebookAccessToken($accessToken);

            break;
        case 'google':
            $request = $httpClient->get('https://www.googleapis.com/plus/v1/people/me?access_token='.$accessToken);
            try {
                $request->send();
            } catch (\Exception $e){
                $code = 400;
                $this->meta->setError('wrong_access_token')
                           ->setErrorMessage('Provided access_token is incorrect or expired')
                ;
                $view->setData($this->meta->build())
                     ->setStatusCode($code)
                ;
                return $this->handleView($view);
            }
            $response = $request->getResponse();

            $json = $response->json();
            $googleId = $json['id'];

            $googleUser = $this->get('user_repository')->findByGoogleId($googleId);
            if(null != $googleUser) {
                $code = 422;
                $this->meta->setError('accout_already_linked')
                           ->setErrorMessage('Account is already linked')
                ;
                $view->setData($this->meta->build())
                     ->setStatusCode($code)
                ;
                return $this->handleView($view);
            }

            $user->setGoogleId($googleId);
            $user->setGoogleAccessToken($accessToken);

            break;
        case 'linkedin':
            $request = $httpClient->get('https://api.linkedin.com/v1/people/~:(id,email-address)?format=json&oauth2_access_token='.$accessToken);
            try {
                $request->send();
            } catch (\Exception $e){
                $code = 400;
                $this->meta->setError('wrong_access_token')
                           ->setErrorMessage('Provided access_token is incorrect or expired')
                ;
                $view->setData($this->meta->build())
                     ->setStatusCode($code)
                ;
                return $this->handleView($view);
            }
            $response = $request->getResponse();

            $json = $response->json();
            $linkedinId = $json['id'];

            $linkedinUser = $this->get('user_repository')->findByLinkedinId($linkedinId);
            if(null != $linkedinUser) {
                $code = 422;
                $this->meta->setError('accout_already_linked')
                           ->setErrorMessage('Account is already linked')
                ;
                $view->setData($this->meta->build())
                     ->setStatusCode($code)
                ;
                return $this->handleView($view);
            }

            $user->setLinkedinId($linkedinId);
            $user->setLinkedinAccessToken($accessToken);

            break;
        }

        $this->get('user_repository')->update($user);

        $code = 201;
        $view->setStatusCode($code)
        ;

        return $this->handleView($view);
    }

    /**
     * Unlink social network account
     */
    public function deleteUserLinkAction($id, $slug)
    {
        $user = $this->get('user_repository')->find($id);
        $this->exist($user);

        if('employee' != $user->getType()) {
            $this->permissionDenied();
        }

        if (false === $this->get('security.context')->isGranted('OWNER', $user)) {
            $this->permissionDenied();
        }

        $providers = [];

        if($user->getFacebookId() != null || $user->getFacebookId() != "") {
            $providers['facebook'] = $user->getFacebookId();
        }

        if($user->getGoogleId() != null || $user->getGoogleId() != "") {
            $providers['google'] = $user->getGoogleId();
        }

        if($user->getLinkedinId() != null || $user->getLinkedinId() != "") {
            $providers['linkedin'] = $user->getLinkedinId();
        }

        $view = $this->view();

        if(false == in_array($slug, array_keys($providers))) {
            $code = 400;
            $this->meta->setError('unknown_provider')
                       ->setErrorMessage('Provider specified doesn\'t exist')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }


        if(($user->getPassword() == null || $user->getPassword() == "") &&
           (count($providers) == 1)) {
            $code = 400;
            $this->meta->setError('password_not_set')
                       ->setErrorMessage('Link cannot be deleted when there\'s no password set')
            ;
            $view->setData($this->meta->build())
                 ->setStatusCode($code)
            ;
            return $this->handleView($view);
        }

        switch($slug) {
        case 'facebook':
            $user->setFacebookId('');
            $user->setFacebookAccessToken('');
            break;
        case 'google':
            $user->setGoogleId('');
            $user->setGoogleAccessToken('');
            break;
        case 'linkedin':
            $user->setLinkedinId('');
            $user->setLinkedinAccessToken('');
            break;
        }

        $this->get('user_repository')->update($user);

        $code = 204;
        $view->setStatusCode($code)
        ;

        return $this->handleView($view);
    }
}
