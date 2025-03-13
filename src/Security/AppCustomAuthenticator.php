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

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_auth';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Cette méthode détermine si l'authenticator doit être utilisé pour la requête actuelle
     */
    public function supports(Request $request): bool
    {
        // Vérifier si c'est une requête POST vers la route de login
        $isLoginRoute = $request->attributes->get('_route') === self::LOGIN_ROUTE;
        $isPost = $request->isMethod('POST');
        
        // Journaliser pour le débogage
        if ($isLoginRoute && $isPost) {
            $this->logger->debug('AppCustomAuthenticator supporte cette requête', [
                'route' => $request->attributes->get('_route'),
                'method' => $request->getMethod(),
            ]);
        }
        
        return $isLoginRoute && $isPost;
    }

    public function authenticate(Request $request): Passport
    {
        try {
            // Récupérer les données du formulaire
            $formData = $request->request->all('login_form');
            
            $this->logger->debug('Données du formulaire reçues', [
                'formData' => $formData,
            ]);
            
            if (!is_array($formData) || !isset($formData['email']) || !isset($formData['password'])) {
                throw new CustomUserMessageAuthenticationException('Formulaire de connexion invalide.');
            }
            
            $email = trim($formData['email']);
            $password = $formData['password'];
            $csrfToken = $formData['_token'] ?? '';
            
            // Vérifications des champs obligatoires
            if (empty($email)) {
                throw new CustomUserMessageAuthenticationException('L\'email ne peut pas être vide.');
            }
            
            if (empty($password)) {
                throw new CustomUserMessageAuthenticationException('Le mot de passe ne peut pas être vide.');
            }

            // Stocker l'email pour l'afficher en cas d'erreur
            if ($request->hasSession()) {
                $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
            }

            $badges = [];
            
            // Vérifier que le token CSRF est présent
            if (empty($csrfToken)) {
                $this->logger->warning('Tentative de connexion sans token CSRF', [
                    'email' => $email,
                    'ip' => $request->getClientIp(),
                ]);
                throw new CustomUserMessageAuthenticationException('Erreur de sécurité: token CSRF manquant. Veuillez rafraîchir la page.');
            }
            
            // Utiliser le même identifiant que dans le type de formulaire
            $badges[] = new CsrfTokenBadge('login_form', $csrfToken);
            
            // Ajouter le badge RememberMe si la case est cochée
            if (isset($formData['remember_me']) && $formData['remember_me']) {
                $badges[] = new RememberMeBadge();
            }

            return new Passport(
                new UserBadge($email),
                new PasswordCredentials($password),
                $badges
            );
        } catch (CustomUserMessageAuthenticationException $e) {
            // Rethrow custom exceptions
            throw $e;
        } catch (\Exception $e) {
            // Log unexpected exceptions and convert to a user-friendly message
            $this->logger->error('Erreur inattendue lors de l\'authentification: ' . $e->getMessage(), [
                'exception' => $e,
                'ip' => $request->getClientIp(),
            ]);
            throw new CustomUserMessageAuthenticationException('Une erreur est survenue lors de la connexion. Veuillez réessayer.');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        try {
            // Récupérer l'email depuis le formulaire de manière sécurisée
            $formData = $request->request->all('login_form');
            $email = is_array($formData) && isset($formData['email']) ? $formData['email'] : 'unknown';
            
            $this->logger->warning('Échec d\'authentification', [
                'message' => $exception->getMessage(),
                'email' => $email,
                'ip' => $request->getClientIp(),
            ]);
            
            // Stocker l'erreur dans la session pour l'afficher sur la page de connexion
            if ($request->hasSession()) {
                $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
            }

            return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
        } catch (\Exception $e) {
            // En cas d'erreur, journaliser et rediriger vers la page de connexion
            $this->logger->error('Erreur lors du traitement de l\'échec d\'authentification: ' . $e->getMessage(), [
                'exception' => $e,
                'ip' => $request->getClientIp(),
            ]);
            return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
        }
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}