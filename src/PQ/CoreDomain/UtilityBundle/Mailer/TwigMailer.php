<?php
namespace PQ\CoreDomain\UtilityBundle\Mailer;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("mailer.twig")
 */
class TwigMailer
{
    protected $mailer;
    protected $twig;

    /**
     * @DI\InjectParams({
     *     "twig" = @DI\Inject("twig"),
     *     "mailer" = @DI\Inject("mailer")
     * })
     */
    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    public function send($templateName, $context, $from, $to, $fromName)
    {
        //$to = "gregory90@gmail.com";
        $template = $this->twig->loadTemplate($templateName);
        $subject = $template->renderBlock('subject', $context);
        $textBody = $template->renderBlock('body_text', $context);
        $htmlBody = $template->renderBlock('body_html', $context);

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom(array($from => $fromName))
            ->setTo($to);

        if (!empty($htmlBody)) {
            $message->setBody($htmlBody, 'text/html')
                ->addPart($textBody, 'text/plain');
        } else {
            $message->setBody($textBody);
        }

        $this->mailer->send($message);
    }
}
