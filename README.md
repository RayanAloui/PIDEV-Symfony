# PIDEV-Symfony

🧒 OrphanCare Web  
OrphanCare Web est une application web développée avec le framework **Symfony** pour faciliter la gestion quotidienne des centres d’accueil d’orphelins. Elle offre une interface web moderne et sécurisée pour gérer les utilisateurs, les orphelins, les visites, les donations, les réclamations et les activités.

---

📌 Modules principaux  
👤 **Gestion des utilisateurs** : inscription, authentification, rôles, et permissions.  
🧒 **Gestion des orphelins** : ajout, mise à jour, historique, affectation à un tuteur.  
🏥 **Gestion des visites** : planification, enregistrement et suivi des visites.  
💰 **Gestion des donations** : suivi des dons financiers ou matériels.  
🗣️ **Gestion des réclamations** : dépôt, traitement et réponse aux réclamations.  
🎨 **Gestion des activités** : création et organisation d’activités pour les orphelins.

---

🛠️ Technologies utilisées  
- PHP 8.x  
- Symfony 6.x  
- Twig (moteur de templates)  
- Doctrine ORM  
- MySQL via XAMPP phpMyAdmin  
- Composer pour la gestion des dépendances  
- Bootstrap 5 pour le frontend

---

🖥️ Fonctionnalités principales  
- Interface web responsive et accessible  
- Système d’authentification sécurisé  
- Navigation fluide entre les modules  
- Génération de QR Code pour les profils  
- Exportation en PDF des données  
- Statistiques visuelles (graphiques)  
- Notifications automatiques par email

---

📦 Installation

1. Clonez le dépôt :
```bash
git clone https://github.com/RayanAloui/PIDEV-Symfony.git
````
cd OrphanCare_Web_Symfony

2. Installez les dépendances PHP et JS :
composer install
npm install && npm run dev

3. Configurez la base de données dans le fichier .env :
DATABASE_URL="mysql://root:password@127.0.0.1:3306/orphancare"

4. Créez et migrez la base :
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

5. Lancez le serveur :
symfony server:start

📁 Structure du projet
```
OrphanCare_Web/
├── assets/
├── config/
├── migrations/
├── public/
│   └── index.php
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Form/
│   ├── Repository/
│   └── Security/
├── templates/
│   ├── base.html.twig
│   └── ...
├── translations/
├── var/
├── vendor/
├── .env
├── composer.json
└── README.md
```

🤝 Contributeurs

- Aloui Ahmed Rayen

- Louay El Amari

- Ben Abdelkader Sami

- Gasmi Riadh

- Malki Youssef

- Belhadej Salah Sarra



