<?php
// src/Controller/VisitesController.php
namespace App\Controller;

use App\Entity\Visites;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VisitesController extends AbstractController
{
    #[Route('/visite/ajouter', name: 'ajouter_visite', methods: ['GET', 'POST'])]
    public function ajouterVisite(Request $request, EntityManagerInterface $em): Response
    {
        $visite = new Visites();
        $visite->setStatut('En attente');
    
        if ($request->isMethod('POST')) {
            $user = $em->getRepository(User::class)->find($request->request->get('id_user'));
            
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('ajouter_visite');
            }
    
            $visite->setId_user($user);
            $visite->setDate(new \DateTime($request->request->get('date')));
            $visite->setHeure($request->request->get('heure'));
            $visite->setMotif($request->request->get('motif'));
    
            $em->persist($visite);
            $em->flush();
    
            $this->addFlash('success', 'Visite ajoutée avec succès (ID: '.$visite->getId().')');
            return $this->redirectToRoute('afficher_visite');
        }
    
        // Récupérer tous les utilisateurs pour le formulaire
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->render('visites/ajouter_visite.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/visite/afficher', name: 'afficher_visite', methods: ['GET'])]
    public function listeVisites(EntityManagerInterface $em): Response
    {
        $visites = $em->getRepository(Visites::class)->findAll();

        return $this->render('visites/afficher_visite.html.twig', [
            'visites' => $visites
        ]);
    }


    #[Route('/visite/modifier/{id}', name: 'modifier_visite', methods: ['GET', 'POST'])]
    public function modifierVisite(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $visite = $em->getRepository(Visites::class)->find($id);
        
        if (!$visite) {
            $this->addFlash('error', 'Visite non trouvée');
            return $this->redirectToRoute('afficher_visite');
        }

        if ($request->isMethod('POST')) {
            $user = $em->getRepository(User::class)->find($request->request->get('id_user'));
            
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('modifier_visite', ['id' => $id]);
            }

            $visite->setId_user($user);
            $visite->setDate(new \DateTime($request->request->get('date')));
            $visite->setHeure($request->request->get('heure'));
            $visite->setMotif($request->request->get('motif'));
            $visite->setStatut($request->request->get('statut'));

            $em->flush();

            $this->addFlash('success', 'Visite modifiée avec succès');
            return $this->redirectToRoute('afficher_visite');
        }

        // Récupérer tous les utilisateurs pour le formulaire
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->render('visites/modifier_visite.html.twig', [
            'visite' => $visite,
            'users' => $users
        ]);
    }

    #[Route('/visite/supprimer/{id}', name: 'supprimer_visite', methods: ['POST'])]
    public function supprimerVisite(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $visite = $em->getRepository(Visites::class)->find($id);
        
        if (!$visite) {
            $this->addFlash('error', 'Visite non trouvée');
            return $this->redirectToRoute('afficher_visite');
        }

        // Vérifier le token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$visite->getId(), $request->request->get('_token'))) {
            $em->remove($visite);
            $em->flush();
            
            $this->addFlash('success', 'Visite supprimée avec succès');
        } else {
            $this->addFlash('error', 'Token CSRF invalide');
        }

        return $this->redirectToRoute('afficher_visite');
    }
}