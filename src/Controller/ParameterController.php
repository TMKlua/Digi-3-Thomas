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
        $createForm = $this->createForm(AppFormParameterType::class);
        $searchForm = $this->createForm(SearchFormType::class);
        // Récupérer la date actuelle
        $currentDateTime = new \DateTime();
        $parameters = $entityManager->getRepository(Parameter::class)
            ->createQueryBuilder('p')
            ->where('p.paramDateFrom <= :currentDate')
            ->andWhere('p.paramDateTo >= :currentDate')
            ->setParameter('currentDate', $currentDateTime)
            ->getQuery()
            ->getResult();

        return $this->render('parameter/config.html.twig', [
            'searchForm' => $searchForm->createView(), // Passer le formulaire à la vue
            'createForm' => $createForm->createView(),
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
            $dateSelect = $form->get('dateSelect')->getData();

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

            // Filtrer par la date sélectionnée si une date est fournie
            if ($dateSelect) {
                $qb->andWhere('p.paramDateFrom <= :dateSelect')
                    ->andWhere('p.paramDateTo >= :dateSelect')
                    ->setParameter('dateSelect', $dateSelect);
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

    #[Route('/parameter/delete/{id}', name: 'app_parameter_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        // Récupérer le paramètre à supprimer
        $parameter = $entityManager->getRepository(Parameter::class)->find($id);

        if (!$parameter) {
            return $this->json(['success' => false, 'message' => 'Paramètre non trouvé.'], 404);
        }
        $currentDateTime = new \DateTime();
        $parameter->setParamDateTo($currentDateTime);

        try {
            $entityManager->persist($parameter);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de la mise à jour.'], 500);
        }

        // Récupérer tous les paramètres après la mise à jour
        $allParameters = $entityManager->getRepository(Parameter::class)->findAll();

        // Générer le HTML pour le tableau avec les paramètres restants
        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
            'parameters' => $allParameters,
        ]);

        return $this->json([
            'success' => true,
            'html' => $html, // Retourne le HTML mis à jour
            'parameters' => $allParameters, // Retourne tous les paramètres restants
        ]);
    }


    #[Route('/parameter/create', name: 'app_parameter_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Créer une nouvelle instance de Parameter
        $parameter = new Parameter();
     

        // Créer le formulaire et gérer la requête
        $form = $this->createForm(AppFormParameterType::class, $parameter);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Persister le nouveau paramètre dans la base de données
                $entityManager->persist($parameter);
                $entityManager->flush();

                // Recharger les paramètres mis à jour
                $parameters = $entityManager->getRepository(Parameter::class)->findAll();
                // Générer le HTML mis à jour pour le tableau des paramètres
                $html = $this->renderView('parameter/tableau_parameter.html.twig', [
                    'parameters' => $parameters,
                ]);

                // Renvoyer la réponse avec le paramètre créé
                return $this->json([
                    'success' => true,
                    'parameter' => [
                        'paramKey' => $parameter->getParamKey(),
                        'paramValue' => $parameter->getParamValue(),
                        'paramDateFrom' => $parameter->getParamDateFrom()->format('Y-m-d H:i'), // Formatage des dates
                        'paramDateTo' => $parameter->getParamDateTo()->format('Y-m-d H:i'),

                    ],
                    'html' => $html, // Renvoie le HTML mis à jour
                ]);
            } else {
                // Collecter et retourner les erreurs de validation
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'message' => 'Formulaire invalide.',
                    'errors' => $errors, // Renvoie les détails des erreurs
                ]);
            }
        }

        // Si le formulaire n'est pas soumis correctement
        return $this->json([
            'success' => false,
            'message' => 'Formulaire non soumis correctement.',
        ]);
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
