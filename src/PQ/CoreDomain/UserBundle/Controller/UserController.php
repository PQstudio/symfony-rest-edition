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
            //$code = 400;
            //$this->meta->setStatusCode($code)
                       //->setError('email_doesnt_exist')
                       //->setErrorMessage('Providen email does not exist in the database')
            //;
            //$view->setData(['meta' => $this->meta->build()])
                 //->setStatusCode($code)
            //;
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

        //var_dump($user->getCurrentPassword());die;

        if($user->getCurrentPassword() == null || false === $encoder->isPasswordValid($user->getPassword(), $user->getCurrentPassword(), $user->getSalt())) {
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

    //public function postUsersConfirmationAction(Request $request)
    //{
        //$token = $request->request->get('token');
        //$user = $this->get('user_repository')->findByConfirmationToken($token);

        //$view = $this->view();

        //if(!$user instanceof User) {
            //$code = 400;
            //$this->meta->setStatusCode($code)
                       //->setError('token_not_correct')
                       //->setErrorMessage('Token is not correct')
            //;
            //$view->setData(['meta' => $this->meta->build()])
                 //->setStatusCode($code)
            //;
            //return $this->handleView($view);
        //}

        //$user->setConfirmationToken(null);
        //$user->setEnabled(true);
        //if($user->getNewEmail() !== null) {
            //$user->setEmail($user->getNewEmail());
            //$user->setNewEmail(null);
        //}

        //$this->get('user_repository')->update($user);

        //$code = 204;
        //$this->meta->setStatusCode($code);
        //$view->setData(['meta' => $this->meta->build()])
             //->setStatusCode($code)
        //;

        //return $this->handleView($view);
    //}

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
}
