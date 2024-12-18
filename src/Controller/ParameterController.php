<?php

namespace App\Controller;

use App\Form\EmailUpdateType;
use App\Form\PasswordUpdateType;
use App\Form\SearchFormType;
use App\Form\AppFormParameterType;
use App\Entity\Parameters;
use App\Entity\User;
use App\Entity\Customers;
use App\Entity\Tasks;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;


class ParameterController extends AbstractController
{
    #[Route('/parameter/app_configuration', name: 'app_parameter_app_configuration', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {   
        // Get current user
        $user = $security->getUser();
        $createForm = $this->createForm(AppFormParameterType::class);
        $searchForm = $this->createForm(SearchFormType::class);

        // $showAll = $searchForm->get('showAll')->getData();
        // $currentDateTime = new \DateTime();
        // // Construire la requête QueryBuilder
        // $parameters = $entityManager->getRepository(Parameter::class)->createQueryBuilder('p');

        // // Filtrer les enregistrements actifs si "showAll" n'est pas coché
        // $parameters->andWhere('p.paramDateFrom <= :currentDate')
        //     ->andWhere('p.paramDateTo >= :currentDate')
        //     ->setParameter('currentDate', $currentDateTime)
        //     ->getQuery()
        //     ->getResult();

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
            'searchForm' => $searchForm->createView(), // Passer le formulaire à la vue
            'createForm' => $createForm->createView(),
            'parameters' => $parameters,
            'user' => $user
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
            $qb = $entityManager->getRepository(Parameters::class)->createQueryBuilder('p');

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

        // Récupérer tous les paramètres après la mise à jour
        $allParameters = $entityManager->getRepository(Parameters::class)->findAll();

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
        $parameter = new Parameters();

        // Créer le formulaire et gérer la requête
        $form = $this->createForm(AppFormParameterType::class, $parameter);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Récupérer les valeurs des dates
                $paramDateFrom = $parameter->getParamDateFrom();
                $paramDateTo = $parameter->getParamDateTo();

                // Vérification de la cohérence des dates
                if ($paramDateFrom instanceof \DateTimeInterface && $paramDateTo instanceof \DateTimeInterface) {
                    // Vérifier que la date de début est avant la date de fin
                    if ($paramDateFrom <= $paramDateTo) {
                        // Persister le nouveau paramètre dans la base de données
                        $entityManager->persist($parameter);
                        $entityManager->flush();

                        // Recharger les paramètres mis à jour
                        $parameters = $entityManager->getRepository(Parameters::class)->findAll();

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
                                'paramDateFrom' => $paramDateFrom->format('Y-m-d H:i'), // Formatage des dates
                                'paramDateTo' => $paramDateTo->format('Y-m-d H:i'),
                            ],
                            'html' => $html, // Renvoie le HTML mis à jour
                        ]);
                    } else {
                        // Si la date de début est postérieure à la date de fin
                        return $this->json([
                            'success' => false,
                            'message' => 'La date de début ne peut pas être après la date de fin.',
                        ]);
                    }
                } else {
                    // Si les dates ne sont pas valides
                    return $this->json([
                        'success' => false,
                        'message' => 'Les dates ne sont pas valides.',
                    ]);
                }
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
    #[Route('/parameter/users', name: 'app_parameter_users', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        // Get current user
        $currentUser = $this->getUser();
        
        // Get all users
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('parameter/users.html.twig', [
            'user' => $currentUser,  // For header template
            'users' => $users       // For users list
        ]);
    }
    #[Route('/parameter/user/add', name: 'app_parameter_user_add', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $user = new User();
            $user->setUserFirstName($request->request->get('firstName'))
                ->setUserLastName($request->request->get('lastName'))
                ->setUserEmail($request->request->get('email'))
                ->setUserRole($request->request->get('role'));

            // Generate a random password (you might want to send this via email)
            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = $passwordHasher->hashPassword($user, $tempPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'User created successfully',
                'tempPassword' => $tempPassword // In production, send this via email instead
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error creating user: ' . $e->getMessage()
            ], 400);
        }
    }
    #[Route('/parameter/user/delete/{id}', name: 'app_parameter_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $user = $entityManager->getRepository(User::class)->find($id);
            
            if (!$user) {
                throw new \Exception('User not found');
            }

            $entityManager->remove($user);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error deleting user: ' . $e->getMessage()
            ], 400);
        }
    }
    #[Route('/parameter/generaux', name: 'app_parameter_generaux')]
    #[IsGranted('ROLE_USER')]
    public function generaux(Request $request, EntityManagerInterface $entityManager, Security $security, UserPasswordHasherInterface $passwordHasher): Response
    
    {
        $user = $security->getUser(); // Récupérer l'utilisateur connecté

        // Vérifier si l'utilisateur est connecté
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour changer votre adresse e-mail.');
            return $this->redirectToRoute('app_auth'); // Redirection vers la page de connexion ou autre
        }
        // Créer les formulaires
        $emailForm = $this->createForm(EmailUpdateType::class, $user);
        $passwordForm = $this->createForm(PasswordUpdateType::class, $user);

        // Gérer le formulaire d'email
        $emailForm->handleRequest($request);
        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            // Vérifier si le mot de passe actuel est correct
            $actualPassword = $emailForm->get('password')->getData();
            if ($passwordHasher->isPasswordValid($user, $actualPassword)) {
                // Récupérer le nouvel email
                $newEmail = $emailForm->get('email')->getData();
                
                // Vérifier si l'email est différent de l'actuel
                if ($newEmail === $user->getUserEmail()) {
                    $this->addFlash('error', 'Le nouvel email doit être différent de l\'actuel');
                } else {
                    // Mettre à jour l'email
                    $user->setUserEmail($newEmail);
                    
                    // Enregistrer les changements
                    $entityManager->persist($user);
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Email mis à jour avec succès');
                    return $this->redirectToRoute('app_parameter_generaux');
                }
            } else {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect');
            }
        } else {
            // Si le formulaire n'est pas valide, afficher les erreurs
            foreach ($emailForm->getErrors(true, true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        // Gérer le formulaire de password
        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $actualPassword = $passwordForm->get('actual_password')->getData();
            $newPassword = $passwordForm->get('password')->getData();

            // Vérifier si le mot de passe actuel est correct
            if ($passwordHasher->isPasswordValid($user, $actualPassword)) {
                // Vérifier que le nouveau mot de passe est différent de l'ancien
                if ($passwordHasher->isPasswordValid($user, $newPassword)) {
                    $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
                } else {
                    // Hacher et mettre à jour le nouveau mot de passe
                    $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                    
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', 'Mot de passe mis à jour avec succès');
                    return $this->redirectToRoute('app_parameter_generaux');
                }
            } else {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect');
            }
        } else {
            // Si le formulaire n'est pas valide, afficher les erreurs
            foreach ($passwordForm->getErrors(true, true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        // Gérer le formulaire d'image de profil
        if ($request->isMethod('POST') && $request->files->has('profile_picture')) {
            $file = $request->files->get('profile_picture');
            
            // Vérifier le token CSRF
            if (!$this->isCsrfTokenValid('upload_photo', $request->headers->get('X-CSRF-TOKEN'))) {
                return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
            }

            // Vérifier que le fichier est une image
            if ($file && in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
                // Vérifier la taille du fichier (max 5MB)
                if ($file->getSize() > 5 * 1024 * 1024) {
                    return $this->json(['success' => false, 'error' => 'Le fichier est trop volumineux (max 5MB)']);
                }

                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $filePath = 'uploads/profile_pictures/' . $filename;

                try {
                    $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/profile_pictures', $filename);
                    
                    $user->setUserAvatar('/uploads/profile_pictures/' . $filename);
                    $entityManager->persist($user);
                    $entityManager->flush();

                    return $this->json([
                        'success' => true,
                        'newProfilePictureUrl' => $user->getUserAvatar()
                    ]);
                } catch (\Exception $e) {
                    return $this->json(['success' => false, 'error' => 'Erreur lors de l\'upload du fichier']);
                }
            } else {
                return $this->json(['success' => false, 'error' => 'Format de fichier non valide. Veuillez télécharger une image.']);
            }
        }
        return $this->render('parameter/index.html.twig', [
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView(),
            'user' => $user,
        ]);
    }
    #[Route('/parameter/about', name: 'app_parameter_about')]
    #[IsGranted('ROLE_USER')]
    public function about(Security $security): Response
    {
        $user = $security->getUser(); // Get current user

        // Check if user is authenticated
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('parameter/about.html.twig', [
            'user' => $user  // Pass the user to the template
        ]);
    }

    #[Route('/parameter/customers', name: 'app_parameter_customers', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function customers(EntityManagerInterface $entityManager): Response
    {
        // Get current user
        $currentUser = $this->getUser();
        
        // Get all customers
        $customers = $entityManager->getRepository(Customers::class)->findAll();

        return $this->render('parameter/customers.html.twig', [
            'user' => $currentUser,
            'customers' => $customers
        ]);
    }

    #[Route('/parameter/customer/add', name: 'app_parameter_customer_add', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addCustomer(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Get current user
            $currentUser = $this->getUser();
            if (!$currentUser) {
                throw new \Exception('User not authenticated');
            }

            $customer = new Customers();
            $customer->setCustomerName($request->request->get('name'))
                ->setCustomerAddressStreet($request->request->get('street'))
                ->setCustomerAddressZipcode($request->request->get('zipcode'))
                ->setCustomerAddressCity($request->request->get('city'))
                ->setCustomerAddressCountry($request->request->get('country'))
                ->setCustomerVAT($request->request->get('vat'))
                ->setCustomerSIREN($request->request->get('siren'))
                ->setCustomerReference($request->request->get('reference'))
                ->setCustomerUserMaj($currentUser->getId());

            // Handle dates if provided
            if ($request->request->get('dateFrom')) {
                $customer->setCustomerDateFrom(new \DateTime($request->request->get('dateFrom')));
            }
            if ($request->request->get('dateTo')) {
                $customer->setCustomerDateTo(new \DateTime($request->request->get('dateTo')));
            }

            $entityManager->persist($customer);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Client ajouté avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors de l\'ajout du client: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/parameter/customer/delete/{id}', name: 'app_parameter_customer_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteCustomer(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $customer = $entityManager->getRepository(Customers::class)->find($id);
            
            if (!$customer) {
                throw new \Exception('Client non trouvé');
            }

            $entityManager->remove($customer);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/parameter/projects', name: 'app_parameter_projects')]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function projects(EntityManagerInterface $entityManager): Response
    {
        // Get current user
        $currentUser = $this->getUser();
        
        // Get all tasks (projects)
        $projects = $entityManager->getRepository(Tasks::class)
            ->createQueryBuilder('t')
            ->where('t.taskType = :type')
            ->setParameter('type', 'PROJECT')
            ->getQuery()
            ->getResult();

        return $this->render('parameter/projects.html.twig', [
            'user' => $currentUser,
            'projects' => $projects
        ]);
    }

    #[Route('/parameter/project/add', name: 'app_parameter_project_add', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function addProject(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $currentUser = $this->getUser();
            if (!$currentUser) {
                throw new \Exception('User not authenticated');
            }

            $project = new Tasks();
            $project->setTaskType('PROJECT')
                ->setTaskName($request->request->get('name'))
                ->setTaskText($request->request->get('description'))
                ->setTaskComplexity($request->request->get('complexity'))
                ->setTaskPriority($request->request->get('priority'))
                ->setTaskUser($currentUser->getId())
                ->setTaskUserMaj($currentUser->getId());

            // Handle dates
            if ($request->request->get('targetStartDate')) {
                $project->setTaskTargetStartDate(new \DateTime($request->request->get('targetStartDate')));
            }
            if ($request->request->get('targetEndDate')) {
                $project->setTaskTargetEndDate(new \DateTime($request->request->get('targetEndDate')));
            }
            
            $entityManager->persist($project);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Projet ajouté avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors de l\'ajout du projet: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/parameter/project/delete/{id}', name: 'app_parameter_project_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function deleteProject(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $project = $entityManager->getRepository(Tasks::class)->find($id);
            
            if (!$project) {
                throw new \Exception('Projet non trouvé');
            }

            $entityManager->remove($project);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Projet supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 400);
        }
    }

}
