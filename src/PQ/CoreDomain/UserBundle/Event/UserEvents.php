<?php
namespace PQ\CoreDomain\UserBundle\Event;

/**
 * Declares all events thrown for User
 */
final class UserEvents
{
    /**
     * Event occurs after user email has been changed.
     *
     * @var string
     */
    const EmailChange = 'pq.user.email.change';

    /**
     * Event after successfull registration
     */
    const Register = 'pq.user.register';

    /**
     * Event after successfull password reset request
     */
    const ForgotPasswordRequest = 'pq.user.forgot.password.request';

    /**
     * Event after successfull password reset change
     */
    const ForgotPasswordChanged = 'pq.user.forgot.password.changed';
}
