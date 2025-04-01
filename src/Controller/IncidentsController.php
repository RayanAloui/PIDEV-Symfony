<?php

namespace App\Controller;

use App\Entity\Incidents;
use App\Entity\User;
use App\Entity\Visites;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
class IncidentsController extends AbstractController
{
    #[Route('/incidents/ajouter', name: 'ajouter_incident', methods: ['GET', 'POST'])]
    public function ajouterIncident(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $incident = new Incidents();
    
        // Récupérer uniquement les utilisateurs qui ont au moins une visite
        $users = $em->createQuery(
            'SELECT u FROM App\Entity\User u WHERE EXISTS (
                SELECT v FROM App\Entity\Visites v WHERE v.id_user = u.id
            )'
        )->getResult();
    
        $selectedUser = null;
        $visites = [];
    
        // Si la méthode est POST, récupérer l'utilisateur et ses visites
        if ($request->isMethod('POST')) {
            $userId = $request->request->get('id_user');
            $selectedUser = $em->getRepository(User::class)->find($userId);
    
            if ($selectedUser) {
                // Récupérer les visites de cet utilisateur
                $visites = $em->getRepository(Visites::class)->findBy(['id_user' => $selectedUser]);
    
                // Vérifier si l'utilisateur a des visites
                if (count($visites) === 0) {
                    $this->addFlash('error', 'Cet utilisateur n\'a aucune visite enregistrée.');
                }
    
                // Si la visite est choisie, l'ajouter à l'incident
                $visiteId = $request->request->get('id_visite');
                if ($visiteId) {
                    $visite = $em->getRepository(Visites::class)->find($visiteId);
                    if ($visite && $visite->getId_user() === $selectedUser) {
                        $incident->setId_user($selectedUser);
                        $incident->setId_visite($visite);
                        $incident->setDescription($request->request->get('description'));
                        $incident->setDateincident(new \DateTime($request->request->get('dateincident')));
    
                        // Validation de l'incident
                        $errors = $validator->validate($incident);
                        if (count($errors) > 0) {
                            // Si des erreurs sont détectées, afficher les messages d'erreur
                            $errorsArray = [];
                            foreach ($errors as $error) {
                                $errorsArray[$error->getPropertyPath()][] = $error;
                            }
                            return $this->render('incidents/ajouter_incident.html.twig', [
                                'users' => $users,
                                'selectedUser' => $selectedUser,
                                'visites' => $visites,
                                'incident' => $incident,
                                'errors' => $errorsArray,
                            ]);
                        }
    
                        // Si aucune erreur, persister l'incident
                        $em->persist($incident);
                        $em->flush();
    
                        $this->addFlash('success', 'Incident ajouté avec succès !');
                        return $this->redirectToRoute('afficher_incident');
                    }
                    $this->addFlash('error', 'La visite sélectionnée n\'appartient pas à cet utilisateur.');
                }
            }
        }
    
        return $this->render('incidents/ajouter_incident.html.twig', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'visites' => $visites,
            'incident' => $incident,
        ]);
    }

// Dans le contrôleur

#[Route('/incidents/visites/{userId}', name: 'get_visites_for_user', methods: ['GET'])]
public function getVisitesForUser(int $userId, EntityManagerInterface $em): JsonResponse
{
    // Récupérer l'utilisateur sélectionné
    $user = $em->getRepository(User::class)->find($userId);

    if (!$user) {
        return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
    }

    // Récupérer les visites de cet utilisateur
    $visites = $em->getRepository(Visites::class)->findBy(['id_user' => $user]);

    // Transformer les visites en un tableau pour la réponse JSON
    $visitesData = [];
    foreach ($visites as $visite) {
        $visitesData[] = [
            'id' => $visite->getId(),
            'date' => $visite->getDate()->format('d/m/Y'),
            'motif' => $visite->getMotif(),
        ];
    }

    return new JsonResponse($visitesData);
}





    #[Route('/incidents', name: 'afficher_incident', methods: ['GET'])]
    public function listeIncidents(EntityManagerInterface $em): Response
    {
        $incidents = $em->getRepository(Incidents::class)->findAll();

        return $this->render('incidents/afficher_incident.html.twig', [
            'incidents' => $incidents
        ]);
    }

    #[Route('/incidents/modifier/{id}', name: 'modifier_incident', methods: ['GET', 'POST'])]
    public function modifierIncident(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $incident = $em->getRepository(Incidents::class)->find($id);

        if (!$incident) {
            $this->addFlash('error', 'Incident non trouvé');
            return $this->redirectToRoute('afficher_incident');
        }

        if ($request->isMethod('POST')) {
            $user = $em->getRepository(User::class)->find($request->request->get('id_user'));
            $visite = $em->getRepository(Visites::class)->find($request->request->get('id_visite'));

            if (!$user || !$visite) {
                $this->addFlash('error', 'Utilisateur ou visite non trouvé');
                return $this->redirectToRoute('modifier_incident', ['id' => $id]);
            }

            $incident->setId_user($user);
            $incident->setId_visite($visite);
            $incident->setDescription($request->request->get('description'));
            $incident->setDateincident(new \DateTime($request->request->get('dateincident')));

            $em->flush();

            $this->addFlash('success', 'Incident modifié avec succès');
            return $this->redirectToRoute('afficher_incident');
        }

        $users = $em->getRepository(User::class)->findAll();
        $visites = $em->getRepository(Visites::class)->findAll();

        return $this->render('incidents/modifier_incident.html.twig', [
            'incident' => $incident,
            'users' => $users,
            'visites' => $visites
        ]);
    }

    #[Route('/incidents/supprimer/{id}', name: 'supprimer_incident', methods: ['POST'])]
    public function supprimerIncident(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $incident = $em->getRepository(Incidents::class)->find($id);

        if (!$incident) {
            $this->addFlash('error', 'Incident non trouvé');
            return $this->redirectToRoute('afficher_incident');
        }

        if ($this->isCsrfTokenValid('delete'.$incident->getId(), $request->request->get('_token'))) {
            $em->remove($incident);
            $em->flush();
            
            $this->addFlash('success', 'Incident supprimé avec succès');
        } else {
            $this->addFlash('error', 'Token CSRF invalide');
        }

        return $this->redirectToRoute('afficher_incident');
    }
}
