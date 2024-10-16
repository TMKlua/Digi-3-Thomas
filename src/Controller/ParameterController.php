<?php

namespace App\Controller;

use App\Form\SearchFormType;
use App\Form\AppFormParameterType;
use App\Entity\Parameter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class ParameterController extends AbstractController
{
    #[Route('/parameter/app_configuration', name: 'app_parameter_app_configuration', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $searchForm = $this->createForm(SearchFormType::class);

        // $createForm = $this->createForm(AppFormParameterType::class);
        $parameters = $entityManager->getRepository(Parameter::class)->findAll();

        return $this->render('parameter/config.html.twig', [
            'searchForm' => $searchForm->createView(), // Passer le formulaire à la vue
            // 'createForm' => $createForm->createView(),
            'parameters' => $parameters
        ]);
    }


    #[Route('/parameter/search', name: 'app_ajax_search', methods: ['POST'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer le formulaire
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        // Initialiser la variable des paramètres
        $parameters = [];
        $currentDateTime = new \DateTime();

        // Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $searchTerm = $form->get('searchTerm')->getData();
            $showAll = $form->get('showAll')->getData();

            // Construire la requête QueryBuilder
            $qb = $entityManager->getRepository(Parameter::class)->createQueryBuilder('p');

            // Appliquer les filtres selon le terme de recherche
            if ($searchTerm) {
                $qb->andWhere('p.paramKey LIKE :searchTerm')
                    ->setParameter('searchTerm', '%' . $searchTerm . '%');
            }

            // Filtrer les enregistrements actifs si "showAll" n'est pas coché
            if (!$showAll) {
                $qb->andWhere('p.paramDateFrom <= :currentDate')
                    ->andWhere('p.paramDateTo >= :currentDate')
                    ->setParameter('currentDate', $currentDateTime);
            }

            // Exécuter la requête pour obtenir les résultats
            $parameters = $qb->getQuery()->getResult();
        }

        // Générer le HTML avec Twig, même si le tableau est vide
        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
            'parameters' => $parameters,
        ]);

        // Retourner la réponse JSON contenant les résultats et le HTML généré
        return $this->json([
            'parameters' => $parameters, // Retourne les paramètres trouvés
            'html' => $html, // HTML à afficher dans le tableau
        ]);
    }

    #[Route('/parameter/delete/{id}', name: 'app_parameter_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer le paramètre à supprimer
        $parameter = $entityManager->getRepository(Parameter::class)->find($id);

        if (!$parameter) {
            return $this->json(['success' => false, 'message' => 'Paramètre non trouvé.'], 404);
        }

        // Supprimer le paramètre
        $entityManager->remove($parameter);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }


    #[Route('/parameter/create', name: 'app_parameter_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('parameter/config.html.twig'); // Assurez-vous de créer ce fichier Twig
    }

    #[Route('/parameter/generaux', name: 'app_parameter_generaux')]
    public function generaux(): Response
    {
        return $this->render('parameter/index.html.twig'); // Assurez-vous de créer ce fichier Twig
    }

    #[Route('/parameter/about', name: 'app_parameter_about')]
    public function about(): Response
    {
        return $this->render('parameter/about.html.twig'); // Assurez-vous de créer ce fichier Twig
    }
}
