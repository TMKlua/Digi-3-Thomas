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

#[Route('/parameter/app_configuration')]
#[IsGranted('ROLE_ADMIN')]
class ConfigController extends AbstractController
{
    #[Route('/', name: 'app_parameter_app_configuration', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {   
        $user = $security->getUser();
        $createForm = $this->createForm(AppFormParameterType::class);
        $searchForm = $this->createForm(SearchFormType::class);

        // Récupérer la date actuelle
        $currentDateTime = new \DateTime();
        $parameters = $entityManager->getRepository(Parameters::class)
            ->createQueryBuilder('p')
            ->where('p.paramDateFrom <= :currentDate')
            ->andWhere('p.paramDateTo >= :currentDate')
            ->setParameter('currentDate', $currentDateTime)
            ->getQuery()
            ->getResult();

        return $this->render('parameter/config.html.twig', [
            'searchForm' => $searchForm->createView(),
            'createForm' => $createForm->createView(),
            'parameters' => $parameters,
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
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $parameter = new Parameters();
        $form = $this->createForm(AppFormParameterType::class, $parameter);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $paramDateFrom = $parameter->getParamDateFrom();
                $paramDateTo = $parameter->getParamDateTo();

                if ($paramDateFrom instanceof \DateTimeInterface && $paramDateTo instanceof \DateTimeInterface) {
                    if ($paramDateFrom <= $paramDateTo) {
                        $entityManager->persist($parameter);
                        $entityManager->flush();

                        $parameters = $entityManager->getRepository(Parameters::class)->findAll();

                        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
                            'parameters' => $parameters,
                        ]);

                        return $this->json([
                            'success' => true,
                            'parameter' => [
                                'paramKey' => $parameter->getParamKey(),
                                'paramValue' => $parameter->getParamValue(),
                                'paramDateFrom' => $paramDateFrom->format('Y-m-d H:i'),
                                'paramDateTo' => $paramDateTo->format('Y-m-d H:i'),
                            ],
                            'html' => $html,
                        ]);
                    } else {
                        return $this->json([
                            'success' => false,
                            'message' => 'La date de début ne peut pas être après la date de fin.',
                        ]);
                    }
                } else {
                    return $this->json([
                        'success' => false,
                        'message' => 'Les dates ne sont pas valides.',
                    ]);
                }
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'message' => 'Formulaire invalide.',
                    'errors' => $errors,
                ]);
            }
        }

        return $this->json([
            'success' => false,
            'message' => 'Formulaire non soumis correctement.',
        ]);
    }
}
