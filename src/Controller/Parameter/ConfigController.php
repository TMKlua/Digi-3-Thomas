<?php

namespace App\Controller\Parameter;

use App\Entity\Parameters;
use App\Form\Parameter\SearchFormType;
use App\Form\Parameter\AppFormParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/parameter/app_configuration')]
#[IsGranted('ROLE_ADMIN')]
class ConfigController extends AbstractController
{
    #[Route('/', name: 'app_parameter_app_configuration', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        EntityManagerInterface $entityManager, 
        Security $security
    ): Response {   
        $user = $security->getUser();
        $createForm = $this->createForm(AppFormParameterType::class);
        $searchForm = $this->createForm(SearchFormType::class);

        $currentDateTime = new \DateTime();
        $allParameters = $entityManager->getRepository(Parameters::class)
            ->findActiveParameters($currentDateTime);

        // Grouper les paramètres par catégorie
        $generalParameters = array_filter($allParameters, 
            fn($param) => $param->extractCategory() === 'general');
        $securityParameters = array_filter($allParameters, 
            fn($param) => $param->extractCategory() === 'security');
        $performanceParameters = array_filter($allParameters, 
            fn($param) => $param->extractCategory() === 'performance');
        $notificationsParameters = array_filter($allParameters, 
            fn($param) => $param->extractCategory() === 'notifications');

        return $this->render('parameter/config.html.twig', [
            'searchForm' => $searchForm->createView(),
            'createForm' => $createForm->createView(),
            'generalParameters' => $generalParameters,
            'securityParameters' => $securityParameters,
            'performanceParameters' => $performanceParameters,
            'notificationsParameters' => $notificationsParameters,
            'user' => $user
        ]);
    }

    #[Route('/search', name: 'app_ajax_search', methods: ['POST'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);
        $parameters = [];
        $currentDateTime = new \DateTime();

        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('searchTerm')->getData();
            $showAll = $form->get('showAll')->getData();
            $dateSelect = $form->get('dateSelect')->getData();

            $qb = $entityManager->getRepository(Parameters::class)->createQueryBuilder('p');

            if ($searchTerm) {
                $qb->andWhere('p.paramKey LIKE :searchTerm')
                    ->setParameter('searchTerm', '%' . $searchTerm . '%');
            }

            if (!$showAll) {
                $qb->andWhere('p.paramDateFrom <= :currentDate')
                    ->andWhere('p.paramDateTo >= :currentDate')
                    ->setParameter('currentDate', $currentDateTime);
            }

            if ($dateSelect) {
                $qb->andWhere('p.paramDateFrom <= :dateSelect')
                    ->andWhere('p.paramDateTo >= :dateSelect')
                    ->setParameter('dateSelect', $dateSelect);
            }

            $parameters = $qb->getQuery()->getResult();
        }

        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
            'parameters' => $parameters,
        ]);

        return $this->json([
            'parameters' => $parameters,
            'html' => $html,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_parameter_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $parameter = $entityManager->getRepository(Parameters::class)->find($id);

        if (!$parameter) {
            return $this->json(['success' => false, 'message' => 'Paramètre non trouvé.'], 404);
        }

        $currentDateTime = new \DateTime();
        if ($parameter->getParamDateTo() < $currentDateTime) {
            return $this->json(['success' => false, 'message' => 'Impossible de supprimer un paramètre non-actif.'], 403);
        }

        $parameter->setParamDateTo($currentDateTime);

        try {
            $entityManager->persist($parameter);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de la mise à jour.'], 500);
        }

        $allParameters = $entityManager->getRepository(Parameters::class)->findAll();

        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
            'parameters' => $allParameters,
        ]);

        return $this->json([
            'success' => true,
            'html' => $html,
            'parameters' => $allParameters,
        ]);
    }

    #[Route('/create', name: 'app_parameter_create', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $entityManager, 
        ValidatorInterface $validator
    ): JsonResponse {
        $parameter = new Parameters();
        $form = $this->createForm(AppFormParameterType::class, $parameter);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validation manuelle
            $errors = $validator->validate($parameter);
            
            if (count($errors) === 0) {
                try {
                    $entityManager->persist($parameter);
                    $entityManager->flush();

                    return $this->json([
                        'success' => true,
                        'message' => 'Paramètre créé avec succès',
                        'parameter' => [
                            'key' => $parameter->getParamKey(),
                            'value' => $parameter->getParamValue()
                        ]
                    ]);
                } catch (\Exception $e) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Erreur lors de la création du paramètre',
                        'error' => $e->getMessage()
                    ], 500);
                }
            } else {
                // Gestion des erreurs de validation
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $errorMessages
                ], 400);
            }
        }

        return $this->json([
            'success' => false,
            'message' => 'Formulaire non valide'
        ], 400);
    }
}
