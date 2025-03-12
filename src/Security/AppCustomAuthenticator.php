<?php

namespace App\Security;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_auth';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    public function supports(Request $request): bool
    {
        $isCorrectRoute = $request->attributes->get('_route') === self::LOGIN_ROUTE;
        $isPostMethod = $request->isMethod('POST');
        $hasLoginAction = $request->request->get('action') === 'login';
        
        // Log pour le débogage
        if ($isCorrectRoute && $isPostMethod) {
            $this->logger->debug('Tentative d\'authentification détectée', [
                'route' => $request->attributes->get('_route'),
                'method' => $request->getMethod(),
                'action' => $request->request->get('action'),
                'post_data' => $request->request->all()
            ]);
        }
        
        return $isCorrectRoute && $isPostMethod && $hasLoginAction;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');
        
        // Vérifications plus strictes
        if (empty($email)) {
            throw new CustomUserMessageAuthenticationException('L\'email ne peut pas être vide.');
        }
        
        if (empty($password)) {
            throw new CustomUserMessageAuthenticationException('Le mot de passe ne peut pas être vide.');
        }
        
        if (empty($csrfToken)) {
            $this->logger->error('Token CSRF manquant dans la requête');
            throw new CustomUserMessageAuthenticationException('Le jeton CSRF est manquant.');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('authenticate', $csrfToken)) {
            $this->logger->error('Token CSRF invalide', [
                'token' => $csrfToken
            ]);
            throw new CustomUserMessageAuthenticationException('Token CSRF invalide. Veuillez réessayer.');
        }

        // Journaliser le token CSRF pour le débogage
        $this->logger->debug('Token CSRF reçu', [
            'token' => $csrfToken,
            'email' => $email
        ]);

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    /**
     * Vérifie si un token CSRF est valide
     */
    private function isCsrfTokenValid(string $id, string $token): bool
    {
        $expectedToken = $this->csrfTokenManager->getToken($id)->getValue();
        $this->logger->debug('Vérification du token CSRF', [
            'id' => $id,
            'token_reçu' => $token,
            'token_attendu' => $expectedToken,
        ]);
        return $this->csrfTokenManager->isTokenValid(new CsrfToken($id, $token));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
        }
        
        // Redirection basée sur le rôle de l'utilisateur
        // Utiliser une seule route 'app_dashboard' pour tous les rôles pour simplifier
        $targetPath = 'app_dashboard';
        
        // Si vous avez besoin de routes spécifiques par rôle plus tard, vous pourrez les ajouter ici
        // $targetPath = match($user->getUserRole()->value) {
        //     User::ROLE_ADMIN => 'app_dashboard',
        //     User::ROLE_RESPONSABLE => 'app_dashboard',
        //     User::ROLE_PROJECT_MANAGER => 'app_dashboard',
        //     default => 'app_dashboard'
        // };

        return new RedirectResponse($this->urlGenerator->generate($targetPath));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->logger->warning('Échec d\'authentification', [
            'exception' => $exception->getMessage()
        ]);
        
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}