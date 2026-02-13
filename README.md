# API Live Streaming Payant - Concert

API complète pour gérer un système de live streaming payant avec génération de codes d'accès uniques.

## 📖 Documentation

### Documentation Complète
- **[ENDPOINTS.md](ENDPOINTS.md)** - Documentation détaillée de tous les nouveaux endpoints
- **[API.md](API.md)** - Documentation technique complète de l'API
- **[SWAGGER_README.md](SWAGGER_README.md)** - Guide d'utilisation de Swagger

### Documentation Interactive

### 🔗 Accès à la documentation Swagger/OpenAPI

⚠️ **IMPORTANT** : Adaptez le port selon votre configuration serveur (8000 par défaut, ou 8080 si modifié)

#### Interface Documentation Interactive
- **Swagger UI** : `http://localhost:8000/api/docs` (interface graphique)
- **OpenAPI JSON** : `http://localhost:8000/api/docs.json` (format JSON)
- **OpenAPI YAML** : `http://localhost:8000/api/docs.yaml` (format YAML)

#### Point d'entrée API
- **Entrypoint** : `http://localhost:8000/api/` (liste toutes les ressources disponibles - retourne du JSON)

🚨 **Note** : `/api` retourne du JSON brut, utilisez `/api/docs` pour l'interface Swagger !

### 🏷️ Tags dans la documentation

La documentation est organisée par tags :
- **Événement Public** - Accès aux informations du concert
- **Paiement** - Initiation et confirmation des paiements
- **Validation de Code** - Validation des codes d'accès
- **Accès Live** - Streaming sécurisé
- **Administration** - Panel d'administration

## 🚀 Fonctionnalités

- ✅ **Événement public** : Affichage des informations du concert
- ✅ **Inscription utilisateurs** : Enregistrement avec fullname, email optionnel, téléphone
- ✅ **Statuts utilisateurs** : Suivi en ligne/hors ligne avec dernière activité
- ✅ **Paiement intégré** : Initiation et confirmation avec FlexPay
- ✅ **Codes d'accès** : Génération automatique et validation
- ✅ **Accès live sécurisé** : Stream protégé avec JWT temporaire
- ✅ **Administration complète** : Gestion utilisateurs, paiements, codes
- ✅ **Accès public** : Toutes les routes accessibles sans authentification
- ✅ **API Platform** : Documentation Swagger automatique

### 🌐 Tous les Endpoints Publics

**Toutes les routes sont maintenant publiques et ne nécessitent aucune authentification.**

#### 👥 Gestion Utilisateurs
- `POST /api/register` - Enregistrement avec gestion doublons
- `GET /api/users` - Liste avec statuts en ligne (`isOnline`, `lastActivity`)

#### 💰 Paiements FlexPay
- `POST /api/payments/initiate` - Initiation paiement (mobile/carte)
- `POST /api/payments/confirm` - Confirmation + génération code automatique
- `GET /api/payments` - Liste complète paiements

#### 🎫 Validation Codes
- `POST /api/validate` - Validation code + token live 5min
- `GET /api/access_codes` - Liste codes d'accès

#### 🎬 Accès Stream Public
- `GET /api/live/watch` - Accès stream via variable d'environnement `STREAM_URL`
- Configuration sans base de données, directement via `.env.local`

### 🎯 Configuration du Stream

L'URL du stream live est configurée via la variable d'environnement `STREAM_URL` :

```bash
# Dans .env.local
STREAM_URL=https://votre-plateforme-stream.com/live/concert
```

**Voir [STREAM_CONFIG_README.md](STREAM_CONFIG_README.md) pour la configuration complète.**

### ⚠️ Note Importante : API Publique

**Toutes les routes de cette API sont maintenant publiques et ne nécessitent aucune authentification.** Cela inclut :
- Accès aux données utilisateurs
- Historique des paiements
- Liste des codes d'accès
- Accès au stream live

**Utilisez cette API uniquement dans un environnement de confiance ou ajoutez une authentification supplémentaire si nécessaire.**

## 🏗️ Architecture

### Entités Doctrine

1. **User** : Utilisateurs finaux
2. **Payment** : Paiements (Pending/Success/Failed)
3. **AccessCode** : Codes d'accès uniques (CINE-XXXX)
4. **LiveEvent** : Événement de streaming
5. **AdminUser** : Administrateurs

### Services

- **StreamUrlEncryptionService** : Chiffrement AES des URLs
- **AccessCodeService** : Gestion des codes d'accès
- **LiveAccessTokenService** : JWT temporaires pour l'accès live
- **PaymentService** : Logique de paiement

## 🔐 Sécurité

- **JWT** pour l'administration (1 heure)
- **JWT live access** temporaires (5 minutes)
- **Chiffrement AES-256-GCM** pour les URLs de stream
- **Validation Symfony** stricte
- **Gestion d'erreurs** globale

## 📡 API Endpoints

### 🎪 Événement Public

#### GET `/api/event`
Retourne les informations du concert.

**Réponse :**
```json
{
  "id": 1,
  "title": "Concert Live - Artiste Mystère",
  "description": "Un concert exceptionnel...",
  "imageUrl": "https://example.com/image.jpg",
  "price": "10.00",
  "isActive": false,
  "liveDate": "2026-02-15T20:00:00+00:00"
}
```

### 💳 Paiement

#### POST `/api/payments/initiate`
Initie un paiement.

**Corps :**
```json
{
  "email": "user@example.com",
  "fullName": "John Doe",
  "phone": "+243123456789",
  "paymentMethod": "card"
}
```

**Réponse :**
```json
{
  "paymentId": 1,
  "status": "pending",
  "amount": "10.00",
  "paymentMethod": "card",
  "message": "Payment initiated successfully"
}
```

#### POST `/api/payments/confirm`
Confirme un paiement (génère automatiquement un code d'accès).

**Corps :**
```json
{
  "paymentId": 1
}
```

### 🎫 Validation de Code

#### POST `/api/code/validate`
Valide un code d'accès et génère un token live temporaire.

**Corps :**
```json
{
  "code": "CINE-A1B2C3D4"
}
```

**Réponse :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "expiresIn": 300,
  "message": "Access code validated successfully"
}
```

### 📺 Accès Live

#### GET `/api/live/watch`
Accède au stream (nécessite le token live).

**Headers :**
```
Authorization: Bearer {live_access_token}
```

**Réponse :**
```json
{
  "streamUrl": "https://stream.example.com/live",
  "title": "Concert Live - Artiste Mystère",
  "isLive": true
}
```

### 👨‍💼 Administration

#### POST `/api/login`
Authentification utilisateur avec API Platform.

**Corps :**
```json
{
  "username": "fils@cinefilm.cd",
  "password": "p@ssword123654"
}
```

#### GET `/api/admin/users`
Liste tous les utilisateurs.

#### GET `/api/admin/payments`
Liste tous les paiements.

#### GET `/api/admin/accesscodes`
Liste tous les codes d'accès.

#### PUT `/api/admin/event/update-stream`
Met à jour l'URL du stream (chiffrée).

**Corps :**
```json
{
  "streamUrl": "https://real-stream-url.com/live"
}
```

#### PUT `/api/admin/event/activate`
Active/désactive l'événement.

**Corps :**
```json
{
  "isActive": true
}
```

## 🛠️ Installation & Configuration

### 1. Installation des dépendances
```bash
composer install
```

### 2. Configuration de la base de données
```bash
# Modifier DATABASE_URL dans .env si nécessaire
# Par défaut : SQLite (var/data.db)
```

### 3. Génération des clés JWT
```bash
# Les clés sont déjà générées dans config/jwt/
# Mot de passe : change_this_passphrase_in_production
```

### 4. Migration de la base de données
```bash
php bin/console doctrine:migrations:migrate
```

### 5. Chargement des données de test
```bash
php bin/console doctrine:fixtures:load
```

### 6. Démarrage du serveur
```bash
php bin/console cache:clear
symfony serve
```

## 🔑 Comptes de Test

### Administrateur
- **Email** : fils@cinefilm.cd
- **Password** : p@ssword123654

## 🧪 Tests

### Routes publiques (pas d'authentification)
- GET `/api/event`

### Flow complet de test
1. **Initier un paiement** : POST `/api/payments/initiate`
2. **Confirmer le paiement** : POST `/api/payments/confirm`
3. **Valider le code** : POST `/api/code/validate`
4. **Accéder au live** : GET `/api/live/watch` (avec token)

### Administration
1. **Login utilisateur** : POST `/api/login`
2. **Mettre à jour l'URL du stream** : PUT `/api/admin/event/update-stream`
3. **Activer l'événement** : PUT `/api/admin/event/activate`

## ⚠️ Points Importants

- **L'URL du stream n'est jamais visible** dans le frontend
- **Les codes d'accès sont uniques** et à usage unique
- **Les tokens live expirent** après 5 minutes
- **Toutes les URLs de stream sont chiffrées** en base de données
- **Validation stricte** sur toutes les entrées
- **Gestion d'erreurs globale** pour les API calls

## 🔒 Sécurité

- **Chiffrement AES-256-GCM** pour les URLs sensibles
- **JWT avec expiration** pour l'accès admin et live
- **Validation de données** stricte
- **Protection CSRF** et CORS configurés
- **Logs d'erreurs** automatiques

## 📋 TODO pour la Production

- [ ] Configurer une vraie base de données (PostgreSQL/MySQL)
- [ ] Intégrer un vrai système de paiement (Stripe, PayPal, etc.)
- [ ] Configurer un vrai stockage pour les clés JWT
- [ ] Mettre en place du monitoring et des logs
- [ ] Configurer HTTPS obligatoire
- [ ] Mettre en place des tests automatisés
- [ ] Configurer les variables d'environnement de production