<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parameter')]
#[IsGranted('ROLE_USER')]
class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_parameter_about')]
    public function index(Security $security): Response
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('parameter/about.html.twig', [
            'user' => $user
        ]);
    }
}
