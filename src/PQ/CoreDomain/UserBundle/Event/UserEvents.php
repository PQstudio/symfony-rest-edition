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
     * Event after successfull password change
     */
    const PasswordChangeGenerate = 'pq.user.passwordChange.generate';
}
