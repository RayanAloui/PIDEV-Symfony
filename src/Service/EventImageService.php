<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EventImageService
{
    private $httpClient;
    private $parameterBag;
    private $unsplashAccessKey;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag,
        string $unsplashAccessKey = 'I-yNOjKs2_EIl8p0-6LJ9wemef3me55-wt53m8mSns4'  // À remplacer par votre clé
    ) {
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
        $this->unsplashAccessKey = $unsplashAccessKey;
    }

    public function getImageForEvent(string $nom, string $description, string $lieu): ?string
    {
        // Extraire les mots-clés pertinents pour la recherche
        $keywords = $this->extractKeywords($nom, $description, $lieu);
        $searchQuery = implode(' ', $keywords);

        try {
            // Rechercher une image sur Unsplash
            $response = $this->httpClient->request('GET', 'https://api.unsplash.com/photos/random', [
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->unsplashAccessKey,
                ],
                'query' => [
                    'query' => $searchQuery,
                    'orientation' => 'landscape',
                ],
            ]);

            $data = $response->toArray();
            
            if (isset($data['urls']['regular'])) {
                // Télécharger l'image
                $imageUrl = $data['urls']['regular'];
                $filename = $this->downloadImage($imageUrl, $nom);
                return $filename;
            }
            
            return null;
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('Erreur lors de la récupération d\'image: ' . $e->getMessage());
            
            // En cas d'erreur, utiliser une image alternative de Lorem Picsum (gratuit, sans API key)
            return $this->getLoremPicsumImage($nom);
        }
    }

    private function extractKeywords(string $nom, string $description, string $lieu): array
    {
        // Convertir en minuscules
        $nom = strtolower($nom);
        $description = strtolower($description);
        $lieu = strtolower($lieu);
        
        // Mots à ignorer (stop words)
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'pour', 'par', 'avec', 'dans', 'sur', 'de', 'du', 'ce', 'cette', 'ces'];
        
        // Extraire les mots
        $allWords = array_merge(
            array_filter(explode(' ', $nom), function($word) use ($stopWords) {
                return !in_array($word, $stopWords) && strlen($word) > 2;
            }),
            array_filter(explode(' ', $lieu), function($word) use ($stopWords) {
                return !in_array($word, $stopWords) && strlen($word) > 2;
            })
        );
        
        // Ajouter quelques mots clés génériques liés aux événements caritatifs
        $allWords[] = 'charity';
        $allWords[] = 'event';
        
        // Limiter à 5 mots-clés
        return array_slice(array_unique($allWords), 0, 5);
    }

    private function downloadImage(string $imageUrl, string $eventName): string
    {
        // Créer un nom de fichier unique
        $filename = 'event_' . uniqid() . '_' . $this->slugify($eventName) . '.jpg';
        
        // Chemin où sauvegarder l'image
        $uploadsDirectory = $this->parameterBag->get('kernel.project_dir') . '/public/uploads/events';
        
        if (!file_exists($uploadsDirectory)) {
            mkdir($uploadsDirectory, 0777, true);
        }
        
        // Télécharger l'image
        $imageContent = file_get_contents($imageUrl);
        file_put_contents($uploadsDirectory . '/' . $filename, $imageContent);
        
        // Retourner le chemin relatif pour l'accès web
        return 'uploads/events/' . $filename;
    }

    private function getLoremPicsumImage(string $eventName): string
    {
        // Utiliser Lorem Picsum comme fallback (totalement gratuit)
        $width = 800;
        $height = 600;
        $picId = rand(1, 1000); // ID aléatoire pour varier les images
        
        $imageUrl = "https://picsum.photos/id/{$picId}/{$width}/{$height}";
        return $this->downloadImage($imageUrl, $eventName);
    }

    private function slugify(string $text): string
    {
        // Remplacer les caractères non alphanumériques par des tirets
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Translitérer
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Supprimer les caractères indésirables
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Trim
        $text = trim($text, '-');
        // Supprimer les tirets en double
        $text = preg_replace('~-+~', '-', $text);
        // Convertir en minuscules
        $text = strtolower($text);
        
        return empty($text) ? 'n-a' : $text;
    }
}