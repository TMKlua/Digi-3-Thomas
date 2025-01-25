<?php

namespace App\Controller\Parameter;

use App\Entity\Customers;
use App\Entity\User;
use App\Repository\CustomersRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/parameter/customers')]
class CustomerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private CustomersRepository $customersRepository,
        private PermissionService $permissionService,
        private Security $security
    ) {}

    #[Route('/', name: 'app_parameter_customers')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié');
        }

        // Débogage
        $this->logger->info('Tentative d\'accès à la liste des clients', [
            'current_user_role' => $currentUser->getUserRole(),
            'current_user_email' => $currentUser->getUserEmail()
        ]);

        if (!$this->permissionService->canViewCustomerList()) {
            $this->logger->warning('Accès refusé à la liste des clients', [
                'current_user_role' => $currentUser->getUserRole()
            ]);
            throw $this->createAccessDeniedException('Accès non autorisé pour votre rôle');
        }

        // Récupérer tous les clients
        $customers = $this->customersRepository->findAll();

        // Déterminer les permissions selon le rôle
        $canEdit = $this->permissionService->canEditCustomer();
        $canDelete = $this->permissionService->canDeleteCustomer();

        return $this->render('parameter/customers.html.twig', [
            'user' => $currentUser,
            'customers' => $customers,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ]);
    }

    #[Route('/add', name: 'app_parameter_customer_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        if (!$this->permissionService->canEditCustomer()) {
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

            $this->validateCustomerData($customerData);

            $customer = new Customers();
            $customer->setCustomerName($customerData['name'])
                     ->setCustomerAddressStreet($customerData['street'])
                     ->setCustomerAddressZipcode($customerData['zipcode'])
                     ->setCustomerAddressCity($customerData['city'])
                     ->setCustomerAddressCountry($customerData['country'])
                     ->setCustomerVAT($customerData['vat'])
                     ->setCustomerSIREN($customerData['siren'])
                     ->setCustomerUserMaj($this->security->getUser()->getUserIdentifier())
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

            $this->logger->info('Client créé', [
                'id' => $customer->getId(),
                'name' => $customer->getCustomerName()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Client créé avec succès',
                'customerId' => $customer->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de création de client', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/edit/{id}', name: 'app_parameter_customer_edit', methods: ['POST'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $customerToEdit = $this->customersRepository->find($id);
        
        if (!$customerToEdit) {
            return $this->json([
                'success' => false,
                'error' => 'Client non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->permissionService->canManageCustomer($customerToEdit)) {
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

            $this->validateCustomerData($customerData);

            $customerToEdit->setCustomerName($customerData['name'])
                           ->setCustomerAddressStreet($customerData['street'])
                           ->setCustomerAddressZipcode($customerData['zipcode'])
                           ->setCustomerAddressCity($customerData['city'])
                           ->setCustomerAddressCountry($customerData['country'])
                           ->setCustomerVAT($customerData['vat'])
                           ->setCustomerSIREN($customerData['siren'])
                           ->setCustomerUserMaj($this->security->getUser()->getUserIdentifier());

            $errors = $this->validator->validate($customerToEdit);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            $this->logger->info('Client modifié', [
                'id' => $customerToEdit->getId(),
                'name' => $customerToEdit->getCustomerName()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Client modifié avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de modification de client', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{id}', name: 'app_parameter_customer_delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $customerToDelete = $this->customersRepository->find($id);
        
        if (!$customerToDelete) {
            return $this->json([
                'success' => false,
                'error' => 'Client non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->permissionService->canDeleteCustomer()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->entityManager->remove($customerToDelete);
            $this->entityManager->flush();

            $this->logger->info('Client supprimé', [
                'id' => $customerToDelete->getId(),
                'name' => $customerToDelete->getCustomerName()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de suppression de client', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    private function validateCustomerData(array $customerData): void
    {
        $requiredFields = ['name', 'street', 'zipcode', 'city', 'country'];
        
        foreach ($requiredFields as $field) {
            if (empty($customerData[$field])) {
                throw new \InvalidArgumentException("Le champ $field est obligatoire");
            }
        }

        // Validation supplémentaire
        if (strlen($customerData['name']) < 2) {
            throw new \InvalidArgumentException('Le nom du client est trop court');
        }

        if (!empty($customerData['vat']) && !preg_match('/^[A-Z]{2}\d{9,12}$/', $customerData['vat'])) {
            throw new \InvalidArgumentException('Le numéro de TVA doit être au format européen (ex: FR123456789)');
        }

        if (!empty($customerData['siren']) && !preg_match('/^\d{9}$/', $customerData['siren'])) {
            throw new \InvalidArgumentException('Le numéro SIREN doit contenir exactement 9 chiffres');
        }
    }
}
