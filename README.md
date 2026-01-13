# 🎁 GiftList

Une application de liste de souhaits collaborative, moderne et **éco-conçue**.

## 🌿 Éco-conception & "e-responsable"

Ce projet a été développé avec une approche **"e-responsable"**, visant à minimiser l'empreinte numérique et la consommation de ressources.

*   **Architecture optimisée** : Utilisation du framework Symfony 8 (PHP 8.4) pour des performances natives élevées et une consommation mémoire réduite.
*   **Minimalisme** : Pas de framework frontend lourd (React/Vue/Angular) inutiles pour ce cas d'usage. Utilisation de Twig et de CSS natif pour un rendu côté serveur ultra-rapide et léger.
*   **Base de données efficace** : Compatible avec SQLite (très faible empreinte locale) ou PostgreSQL optimisé.
*   **Ressources statiques** : Aucun appel externe bloquant, polices optimisées, pas de trackers publicitaires.

## 🚀 Installation

### Prérequis
*   Docker & Docker Compose
*   (Optionnel) PHP 8.4+ et Composer pour le développement local sans Docker.

### Démarrage rapide

1.  **Cloner le projet**
    ```bash
    git clone https://github.com/votre-user/giftlist.git
    cd giftlist
    ```

2.  **Lancer les conteneurs**
    ```bash
    docker compose up -d
    ```

3.  **Installer les dépendances**
    ```bash
    docker compose exec php composer install
    ```
    *(Si vous n'avez pas de conteneur PHP dédié dans compose.yaml, lancez `composer install` localement)*

4.  **Préparer la base de données**
    ```bash
    php bin/console doctrine:migrations:migrate
    ```

5.  **Accéder à l'application**
    Ouvrez `http://127.0.0.1:8000` (ou le port configuré par votre serveur web local/Docker).

## 🏗️ Architecture

*   **Backend** : Symfony 8 (MVC)
*   **Frontend** : Twig + CSS Natif (Variables CSS, Flexbox/Grid) + JS Vanilla (pour le scraping uniquement).
*   **Base de données** : PostgreSQL / SQLite.
*   **Sécurité** : Authentification standard Symfony avec hachage de mot de passe sécurisé.

## 🤝 Contribuer

Les contributions respectant la philosophie éco-responsable sont les bienvenues. Merci de veiller à ne pas introduire de bibliothèques lourdes sans justification majeure.

---
*Développé avec passion pour un web plus durable.*
