<?php

namespace App\Controller\Parameter;

use App\Entity\Customers;
use App\Repository\CustomersRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/parameter/customers')]
class CustomerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomersRepository $customersRepository,
        private ValidatorInterface $validator,
        private PermissionService $permissionService
    ) {}

    #[Route('/', name: 'app_parameter_customers')]
    public function index(): Response
    {
        // Vérifier les permissions
        if (!$this->permissionService->canAccessParameterPages()) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $currentUser = $this->getUser();
        $customers = $this->customersRepository->findAll();

        // Déterminer si l'utilisateur peut modifier/supprimer
        $canEdit = $this->permissionService->hasRole('ROLE_ADMIN');

        return $this->render('parameter/customers.html.twig', [
            'customers' => $customers,
            'user' => $currentUser,
            'canEdit' => $canEdit
        ]);
    }

    #[Route('/add', name: 'app_parameter_customer_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        // Seul l'admin peut ajouter des clients
        if (!$this->permissionService->hasRole('ROLE_ADMIN')) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $customerData = [
                'name' => $request->request->get('customerName'),
                'street' => $request->request->get('customerAddressStreet'),
                'zipcode' => $request->request->get('customerAddressZipcode'),
                'city' => $request->request->get('customerAddressCity'),
                'country' => $request->request->get('customerAddressCountry'),
                'vat' => $request->request->get('customerVAT'),
                'siren' => $request->request->get('customerSIREN')
            ];

            $customer = new Customers();
            $customer->setCustomerName($customerData['name'])
                     ->setCustomerAddressStreet($customerData['street'])
                     ->setCustomerAddressZipcode($customerData['zipcode'])
                     ->setCustomerAddressCity($customerData['city'])
                     ->setCustomerAddressCountry($customerData['country'])
                     ->setCustomerVAT($customerData['vat'])
                     ->setCustomerSIREN($customerData['siren'])
                     ->setCustomerUserMaj($this->getUser()->getId())
                     ->setCustomerDateFrom(new \DateTime());

            $errors = $this->validator->validate($customer);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($customer);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Client créé avec succès',
                'customerId' => $customer->getId()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/edit/{id}', name: 'app_parameter_customer_edit', methods: ['POST'])]
    public function edit(Request $request, int $id): JsonResponse
    {
        // Seul l'admin peut modifier des clients
        if (!$this->permissionService->hasRole('ROLE_ADMIN')) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $customer = $this->customersRepository->find($id);
            
            if (!$customer) {
                return $this->json([
                    'success' => false,
                    'error' => 'Client non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $customer->setCustomerName($request->request->get('customerName'))
                     ->setCustomerAddressStreet($request->request->get('customerAddressStreet'))
                     ->setCustomerAddressZipcode($request->request->get('customerAddressZipcode'))
                     ->setCustomerAddressCity($request->request->get('customerAddressCity'))
                     ->setCustomerAddressCountry($request->request->get('customerAddressCountry'))
                     ->setCustomerVAT($request->request->get('customerVAT'))
                     ->setCustomerSIREN($request->request->get('customerSIREN'))
                     ->setCustomerUserMaj($this->getUser()->getId());

            $errors = $this->validator->validate($customer);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Client modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{id}', name: 'app_parameter_customer_delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        // Seul l'admin peut supprimer des clients
        if (!$this->permissionService->hasRole('ROLE_ADMIN')) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $customer = $this->customersRepository->find($id);
            
            if (!$customer) {
                return $this->json([
                    'success' => false,
                    'error' => 'Client non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($customer);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
