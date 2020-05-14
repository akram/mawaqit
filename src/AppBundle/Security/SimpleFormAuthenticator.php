<?php

namespace AppBundle\Security;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrueValidator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimpleFormAuthenticatorInterface;

class SimpleFormAuthenticator implements SimpleFormAuthenticatorInterface
{
    private $encoder;
    private $recaptchaValidator;
    private $requestStack;

    public function __construct(
        UserPasswordEncoderInterface $encoder,
        IsTrueValidator $recaptchaValidator,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory
    ) {
        $this->encoder = $encoder;
        $this->recaptchaValidator = $recaptchaValidator;
        $this->requestStack = $requestStack;
        $this->formFactory = $formFactory;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {

        $this->checkRecaptcha();

        try {
            $user = $userProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $exception) {
            // CAUTION: this message will be returned to the client
            // (so don't put any un-trusted messages / error strings here)
            throw new CustomUserMessageAuthenticationException('Invalid username or password');
        }

        $currentUser = $token->getUser();

        if ($currentUser instanceof UserInterface) {
            if ($currentUser->getPassword() !== $user->getPassword()) {
                throw new BadCredentialsException('The credentials were changed from another session.');
            }
        } else {
            if ('' === ($givenPassword = $token->getCredentials())) {
                throw new BadCredentialsException('The given password cannot be empty.');
            }
            if (!$this->encoder->isPasswordValid($user, $givenPassword)) {
                throw new BadCredentialsException('The given password is invalid.');
            }
        }

        return new UsernamePasswordToken(
            $user,
            $user->getPassword(),
            $providerKey,
            $user->getRoles()
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof UsernamePasswordToken
            && $token->getProviderKey() === $providerKey;
    }

    public function createToken(Request $request, $username, $password, $providerKey)
    {
        return new UsernamePasswordToken($username, $password, $providerKey);
    }

    private function checkRecaptcha()
    {
        $form = $this->formFactory->create(
            EWZRecaptchaType::class,
            null,
            [
                'required' => true,
                'constraints' => array(
                    new IsTrue()
                )
            ]
        );

        $recaptchaResponse = $this->requestStack->getMasterRequest()->get('g-recaptcha-response');

        $form->submit($recaptchaResponse);

        if (!$form->isValid()) {
            throw new CustomUserMessageAuthenticationException($form->getErrors()->current()->getMessage());
        }
    }
}