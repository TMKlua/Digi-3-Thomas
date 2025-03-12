<?php

namespace App\Controller\Parameter;

use App\Entity\Customers;
use App\Entity\User;
use App\Repository\CustomersRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/parameter/customers')]
class CustomerController extends AbstractCrudController
{
    private CustomersRepository $customersRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        PermissionService $permissionService,
        Security $security,
        CustomersRepository $customersRepository
    ) {
        parent::__construct($entityManager, $validator, $logger, $permissionService, $security);
        $this->customersRepository = $customersRepository;
    }

    protected function getEntityClass(): string
    {
        return Customers::class;
    }

    protected function getRepository()
    {
        return $this->customersRepository;
    }

    protected function getEntityName(): string
    {
        return 'Client';
    }

    protected function getTemplatePrefix(): string
    {
        return 'parameter/customer';
    }

    protected function canView(): bool
    {
        return $this->permissionService->canViewCustomerList();
    }

    protected function canEdit(): bool
    {
        return $this->permissionService->hasPermission('edit_customers');
    }

    protected function canDelete(): bool
    {
        return $this->permissionService->hasPermission('delete_customers');
    }

    #[Route('/', name: 'app_parameter_customers')]
    public function index(): Response
    {
        // Vérifier si l'utilisateur est authentifié
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié');
        }

        // Vérifier si l'utilisateur peut voir la liste des clients
        if (!$this->canView()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les permissions nécessaires pour voir la liste des clients');
        }

        return $this->render('parameter/customer/index.html.twig', [
            'customers' => $this->customersRepository->findAll(),
            'user' => $this->security->getUser(),
            'canEdit' => $this->canEdit(),
            'canDelete' => $this->canDelete()
        ]);
    }

    protected function validateData(array $data): void
    {
        $requiredFields = ['name', 'street', 'zipcode', 'city', 'country'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ $field est obligatoire");
            }
        }

        if (strlen($data['name']) < 2) {
            throw new \InvalidArgumentException('Le nom du client est trop court');
        }

        if (!empty($data['vat']) && !preg_match('/^[A-Z]{2}\d{9,12}$/', $data['vat'])) {
            throw new \InvalidArgumentException('Le numéro de TVA doit être au format européen (ex: FR123456789)');
        }

        if (!empty($data['siren']) && !preg_match('/^\d{9}$/', $data['siren'])) {
            throw new \InvalidArgumentException('Le numéro SIREN doit contenir exactement 9 chiffres');
        }
    }

    protected function createEntity(array $data): object
    {
        $this->denyAccessUnlessGranted('create', null, 'Vous n\'avez pas les permissions nécessaires pour créer un client.');
        
        $customer = new Customers();
        $this->updateEntity($customer, $data);
        return $customer;
    }

    protected function updateEntity(object $entity, array $data): void
    {
        if (!$entity instanceof Customers) {
            throw new \InvalidArgumentException('L\'entité doit être un client');
        }

        $this->denyAccessUnlessGranted('edit', $entity, 'Vous n\'avez pas les permissions nécessaires pour modifier ce client.');

        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw new \RuntimeException('Utilisateur non authentifié');
        }

        $entity->setCustomerName($data['name'])
               ->setCustomerAddressStreet($data['street'])
               ->setCustomerAddressZipcode($data['zipcode'])
               ->setCustomerAddressCity($data['city'])
               ->setCustomerAddressCountry($data['country'])
               ->setCustomerVAT($data['vat'] ?? null)
               ->setCustomerSIREN($data['siren'] ?? null)
               ->setCustomerUpdatedAt(new \DateTime())
               ->setCustomerUpdatedBy($currentUser);
    }

    protected function getRequestData(Request $request): array
    {
        return [
            'name' => $request->request->get('customerName'),
            'street' => $request->request->get('customerAddressStreet'),
            'zipcode' => $request->request->get('customerAddressZipcode'),
            'city' => $request->request->get('customerAddressCity'),
            'country' => $request->request->get('customerAddressCountry'),
            'vat' => $request->request->get('customerVAT'),
            'siren' => $request->request->get('customerSIREN')
        ];
    }
}
