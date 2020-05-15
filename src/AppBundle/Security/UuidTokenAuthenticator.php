<?php

namespace AppBundle\Security;

use AppBundle\Entity\User;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;


class UuidTokenAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{

    public const TOKEN_NAME = 'Api-Access-Token';
    public const SHORT_TOKEN_NAME = 't';

    public function createToken(Request $request, $providerKey)
    {
        $headers = $request->headers;
        $apiAccessToken = $headers->get(self::SHORT_TOKEN_NAME, $headers->get(self::TOKEN_NAME));

        if (!$apiAccessToken) {
            throw new AccessDeniedHttpException(self::TOKEN_NAME . ' header is required');
        }

        if (!Uuid::isValid($apiAccessToken)) {
            throw new AccessDeniedHttpException(self::TOKEN_NAME . ' header is not valid');
        }

        return new PreAuthenticatedToken(
            'anon.',
            $apiAccessToken,
            $providerKey
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken &&
            $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $apiAccessToken = $token->getCredentials();

        try {
            $user = $userProvider->loadUserByUsername($apiAccessToken);
        } catch (UsernameNotFoundException $e) {
            throw new AccessDeniedHttpException(sprintf('No user found for token "%s".', $apiAccessToken));
        }

        if (!$user instanceof UserInterface) {
            throw new AccessDeniedHttpException(sprintf('No user found for token "%s".', $apiAccessToken));
        }

        if (!$user->isEnabled()) {
            throw new AccessDeniedHttpException('User not enabled');
        }

        $this->checkQuota($user);

        return new PreAuthenticatedToken(
            $user,
            $apiAccessToken,
            $providerKey,
            $user->getRoles()
        );
    }

    private function checkQuota(User $user)
    {
        if ($user->getApiCallNumber() >= $user->getApiQuota()) {
            throw new AccessDeniedHttpException(sprintf('You have reached your quota %s of %s allowed',
                $user->getApiCallNumber(), $user->getApiQuota()));
        }
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw $exception;
    }

}
