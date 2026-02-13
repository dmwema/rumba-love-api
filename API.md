# 📡 API Live Streaming Payant - Documentation Complète

## 🎪 Vue d'ensemble

Cette API permet de gérer un système complet de live streaming payant pour un concert unique. Elle inclut la gestion des utilisateurs, paiements, codes d'accès sécurisés, et administration.

### ✨ Fonctionnalités principales

- **🎫 Système de paiement intégré** avec génération automatique de codes d'accès
- **🔐 Sécurité avancée** avec JWT et chiffrement AES des URLs de stream
- **👨‍💼 Panel d'administration** complet
- **📺 Accès live sécurisé** avec tokens temporaires
- **✅ Validation stricte** de toutes les données

---

## 🔐 Authentification et Sécurité

### JWT Tokens

L'API utilise deux types de tokens JWT :

#### 🔑 Token Administrateur
- **Durée** : 1 heure
- **Rôles** : `ROLE_ADMIN`
- **Utilisation** : Accès au panel d'administration

#### 🎟️ Token Live Access
- **Durée** : 5 minutes
- **Rôles** : `ROLE_LIVE_ACCESS`
- **Utilisation** : Accès au stream en direct

### Chiffrement AES

Toutes les URLs de stream sont chiffrées en base de données avec AES-256-GCM.

### Sécurité des codes d'accès

- **Format** : `CINE-XXXXXXXX` (8 caractères alphanumériques)
- **Unicité** : Chaque code est unique en base de données
- **Usage unique** : Un code ne peut être utilisé qu'une seule fois
- **Expiration** : 24 heures après génération

---

## 🌐 Endpoints API

### Base URL
```
http://localhost:8000/api
```

### Headers communs
```http
Content-Type: application/json
Authorization: Bearer {token}  # Pour les routes protégées
```

---

## 🎪 ÉVÉNEMENT PUBLIC

### GET `/event`

Retourne les informations publiques du concert.

**🔓 Authentification :** Non requise

**📝 Exemple de requête :**
```bash
GET /api/event HTTP/1.1
Host: localhost:8000
```

**✅ Réponse de succès (200) :**
```json
{
  "id": 1,
  "title": "Concert Live - Artiste Mystère",
  "description": "Un concert exceptionnel en direct. Réservez votre place dès maintenant !",
  "imageUrl": "https://example.com/concert-image.jpg",
  "price": "10.00",
  "isActive": false,
  "liveDate": "2026-02-15T20:00:00+00:00"
}
```

**❌ Réponse d'erreur (404) :**
```json
{
  "error": "No live event available"
}
```

---

## 💳 PAIEMENT

### POST `/payment/initiate`

Initie un processus de paiement pour un utilisateur.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "email": "user@example.com",
  "fullName": "John Doe",
  "phone": "+243123456789",
  "paymentMethod": "card"
}
```

**📋 Paramètres :**

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `email` | string | ✅ | Email valide de l'utilisateur |
| `fullName` | string | ✅ | Nom complet (2-255 caractères) |
| `phone` | string | ❌ | Numéro de téléphone international |
| `paymentMethod` | string | ✅ | `card` ou `mobile` |

**✅ Réponse de succès (201) :**
```json
{
  "paymentId": 123,
  "status": "pending",
  "amount": "10.00",
  "paymentMethod": "card",
  "message": "Payment initiated successfully"
}
```

**❌ Réponses d'erreur :**

**400 - Données invalides :**
```json
{
  "errors": [
    "email: This value is not a valid email address.",
    "paymentMethod: The value you selected is not a valid choice."
  ]
}
```

### POST `/payment/confirm`

Confirme un paiement et génère automatiquement un code d'accès.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "paymentId": 123
}
```

**📋 Paramètres :**

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `paymentId` | integer | ✅ | ID du paiement à confirmer |

**✅ Réponse de succès (200) :**
```json
{
  "paymentId": 123,
  "status": "success",
  "transactionReference": "TXN-ABC123",
  "message": "Payment confirmed successfully. Access code generated."
}
```

**❌ Réponses d'erreur :**

**404 - Paiement introuvable :**
```json
{
  "error": "Payment not found or already processed"
}
```

---

## 🎫 VALIDATION DE CODE

### POST `/code/validate`

Valide un code d'accès et génère un token temporaire pour l'accès live.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "code": "CINE-A1B2C3D4"
}
```

**📋 Paramètres :**

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `code` | string | ✅ | Code d'accès au format CINE-XXXXXXXX |

**✅ Réponse de succès (200) :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJjb2RlIjoiQ0lORS1BMUIyQzNENCIsInJvbGVzIjpbIlJPTEVfTElWRV9BQ0NFU1MiXSwiZXhwIjoxNzM5NjY4ODAwLCJpYXQiOjE3Mzk2Njg1MDAsInR5cGUiOiJsaXZlX2FjY2VzcyJ9...",
  "expiresIn": 300,
  "message": "Access code validated successfully"
}
```

**❌ Réponses d'erreur :**

**400 - Code invalide/expiré :**
```json
{
  "error": "Invalid or expired access code"
}
```

---

## 📺 ACCÈS LIVE

### GET `/live/watch`

Accède au stream en direct avec un token temporaire.

**🔒 Authentification :** Token Live Access requis

**📝 Headers :**
```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

**✅ Réponse de succès (200) :**
```json
{
  "streamUrl": "https://real-stream-url.com/live",
  "title": "Concert Live - Artiste Mystère",
  "isLive": true
}
```

**❌ Réponses d'erreur :**

**401 - Token manquant/invalide :**
```json
{
  "error": "Missing or invalid authorization header"
}
```

**404 - Aucun événement actif :**
```json
{
  "error": "No active live event"
}
```

---

## 👨‍💼 ADMINISTRATION

### POST `/auth/admin`

Authentifie un administrateur et retourne un token JWT.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "username": "fils@cinefilm.cd",
  "password": "p@ssword123654"
}
```

**✅ Réponse de succès (200) :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### GET `/admin/users`

Liste tous les utilisateurs enregistrés.

**🔒 Authentification :** Token Admin requis

**📝 Headers :**
```http
Authorization: Bearer {admin_token}
```

**✅ Réponse de succès (200) :**
```json
[
  {
    "id": 1,
    "email": "user@example.com",
    "fullName": "John Doe",
    "phone": "+243123456789",
    "createdAt": "2026-02-13T10:30:00+00:00",
    "paymentsCount": 1,
    "accessCodesCount": 1
  }
]
```

### GET `/admin/payments`

Liste tous les paiements.

**🔒 Authentification :** Token Admin requis

**✅ Réponse de succès (200) :**
```json
[
  {
    "id": 123,
    "user": {
      "id": 1,
      "email": "user@example.com",
      "fullName": "John Doe"
    },
    "amount": "10.00",
    "status": "success",
    "paymentMethod": "card",
    "transactionReference": "TXN-ABC123",
    "createdAt": "2026-02-13T10:30:00+00:00"
  }
]
```

### GET `/admin/accesscodes`

Liste tous les codes d'accès.

**🔒 Authentification :** Token Admin requis

**✅ Réponse de succès (200) :**
```json
[
  {
    "id": 1,
    "user": {
      "id": 1,
      "email": "user@example.com"
    },
    "code": "CINE-A1B2C3D4",
    "isUsed": false,
    "usedAt": null,
    "expiresAt": "2026-02-14T10:30:00+00:00",
    "createdAt": "2026-02-13T10:30:00+00:00"
  }
]
```

### PUT `/admin/event/update-stream`

Met à jour l'URL du stream (sera automatiquement chiffrée).

**🔒 Authentification :** Token Admin requis

**📝 Corps de la requête :**
```json
{
  "streamUrl": "https://real-stream-platform.com/live/concert123"
}
```

**✅ Réponse de succès (200) :**
```json
{
  "message": "Stream URL updated successfully"
}
```

### PUT `/admin/event/activate`

Active ou désactive l'événement de streaming.

**🔒 Authentification :** Token Admin requis

**📝 Corps de la requête :**
```json
{
  "isActive": true
}
```

**✅ Réponse de succès (200) :**
```json
{
  "message": "Event activated successfully",
  "isActive": true
}
```

---

## 📊 MODÈLES DE DONNÉES

### User
```json
{
  "id": "integer",
  "email": "string (unique)",
  "fullName": "string (2-255 chars)",
  "phone": "string (nullable)",
  "createdAt": "datetime"
}
```

### Payment
```json
{
  "id": "integer",
  "user": "User (relation)",
  "amount": "decimal (10,2)",
  "status": "enum: pending|success|failed",
  "paymentMethod": "enum: card|mobile",
  "transactionReference": "string (nullable)",
  "createdAt": "datetime"
}
```

### AccessCode
```json
{
  "id": "integer",
  "user": "User (relation)",
  "code": "string (unique, 12 chars)",
  "isUsed": "boolean",
  "usedAt": "datetime (nullable)",
  "expiresAt": "datetime",
  "createdAt": "datetime"
}
```

### LiveEvent
```json
{
  "id": "integer",
  "title": "string (255 chars)",
  "description": "text",
  "imageUrl": "string (nullable)",
  "price": "decimal (10,2)",
  "streamUrl": "text (encrypted)",
  "isActive": "boolean",
  "liveDate": "datetime",
  "createdAt": "datetime"
}
```

### AdminUser
```json
{
  "id": "integer",
  "email": "string (unique)",
  "roles": "array",
  "password": "string (hashed)"
}
```

---

## 🚨 CODES D'ERREUR

### Erreurs Globales

| Code | Description |
|------|-------------|
| `400` | Données invalides ou requête malformée |
| `401` | Authentification requise ou token invalide |
| `403` | Permissions insuffisantes |
| `404` | Ressource non trouvée |
| `500` | Erreur interne du serveur |

### Erreurs Spécifiques

#### Paiement
- `Payment not found or already processed` (404)
- `Invalid payment data` (400)

#### Code d'accès
- `Invalid or expired access code` (400)
- `Code already used` (400)

#### Live
- `No active live event` (404)
- `Unable to access stream` (500)

#### Administration
- `No live event found` (404)
- `Failed to encrypt stream URL` (500)

---

## 🔄 FLOWS UTILISATEUR

### Flow d'Achat Complet

```mermaid
sequenceDiagram
    participant U as Utilisateur
    participant F as Frontend
    participant A as API
    participant DB as Base de données

    F->>A: GET /api/event
    A->>DB: Récupérer événement
    A->>F: Données de l'événement

    U->>F: Soumettre formulaire paiement
    F->>A: POST /api/payment/initiate
    A->>DB: Créer User et Payment
    A->>F: paymentId

    Note over F: Simulation paiement réussi
    F->>A: POST /api/payment/confirm
    A->>DB: Marquer paiement success
    A->>DB: Générer AccessCode
    A->>F: Confirmation

    U->>F: Saisir code d'accès
    F->>A: POST /api/code/validate
    A->>DB: Valider et marquer code utilisé
    A->>F: Token JWT temporaire

    F->>A: GET /api/live/watch (avec token)
    A->>DB: Vérifier token et déchiffrer URL
    A->>F: URL du stream
```

### Flow Administrateur

```mermaid
sequenceDiagram
    participant A as Admin
    participant API as API Admin
    participant DB as Base de données

    A->>API: POST /auth/admin
    API->>DB: Vérifier credentials
    API->>A: Token JWT admin

    A->>API: PUT /admin/event/update-stream (avec token)
    API->>API: Chiffrer URL
    API->>DB: Sauvegarder URL chiffrée

    A->>API: PUT /admin/event/activate (avec token)
    API->>DB: Activer l'événement

    A->>API: GET /admin/users (avec token)
    API->>DB: Récupérer statistiques
    API->>A: Liste utilisateurs
```

---

## 🧪 TESTS ET EXEMPLES

### Compte de test administrateur
```json
{
  "email": "fils@cinefilm.cd",
  "password": "p@ssword123654"
}
```

### Exemple complet de session utilisateur

```bash
# 1. Obtenir les infos de l'événement
curl -X GET http://localhost:8000/api/event

# 2. Initier un paiement
curl -X POST http://localhost:8000/api/payment/initiate \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "fullName": "Test User",
    "phone": "+243123456789",
    "paymentMethod": "card"
  }'

# 3. Confirmer le paiement (simulé)
curl -X POST http://localhost:8000/api/payment/confirm \
  -H "Content-Type: application/json" \
  -d '{"paymentId": 1}'

# 4. Valider le code d'accès
curl -X POST http://localhost:8000/api/code/validate \
  -H "Content-Type: application/json" \
  -d '{"code": "CINE-A1B2C3D4"}'

# 5. Accéder au live avec le token
curl -X GET http://localhost:8000/api/live/watch \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
```

---

## 🔧 CONFIGURATION TECHNIQUE

### Variables d'environnement

```bash
# Base de données
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=change_this_passphrase_in_production

# CORS
CORS_ALLOW_ORIGIN=*
```

### Sécurité

- **Rate limiting** : Non implémenté (à ajouter en production)
- **HTTPS** : Obligatoire en production
- **Logs** : Erreurs automatiquement loggées
- **Monitoring** : À implémenter selon les besoins

### Performance

- **Cache** : Doctrine ORM avec cache activé
- **Database indexes** : Sur les champs fréquemment recherchés
- **Lazy loading** : Relations Doctrine optimisées

---

## 🚀 DÉPLOIEMENT

### Prérequis

- PHP 8.2+
- Symfony 7.4
- SQLite/PostgreSQL/MySQL
- OpenSSL (pour les clés JWT)

### Étapes de déploiement

1. **Cloner le projet**
   ```bash
   git clone <repository>
   cd api-rumba-love
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   # Éditer .env avec les vraies valeurs
   ```

4. **Générer les clés JWT**
   ```bash
   # Les clés existent déjà, à régénérer en production
   ```

5. **Migrer la base de données**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

6. **Charger les données de test**
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. **Vider le cache**
   ```bash
   php bin/console cache:clear
   ```

8. **Démarrer le serveur**
   ```bash
   symfony serve
   ```

---

## 📞 SUPPORT ET MAINTENANCE

### Logs

Les erreurs sont automatiquement loggées dans `var/log/dev.log` (dev) ou `var/log/prod.log` (prod).

### Monitoring

À implémenter :
- Métriques de performance
- Alertes sur erreurs critiques
- Monitoring des paiements

### Mises à jour

- **Sécurité** : Maintenir les dépendances à jour
- **Base de données** : Créer des migrations pour les changements de schéma
- **API** : Respecter la rétrocompatibilité

---

## 📋 CHECKLIST DE VALIDATION

### ✅ Fonctionnalités Core
- [x] Événement public accessible
- [x] Paiement avec génération de code
- [x] Validation de code avec token temporaire
- [x] Accès live sécurisé
- [x] Panel d'administration complet

### ✅ Sécurité
- [x] JWT pour l'administration
- [x] Tokens temporaires pour le live
- [x] Chiffrement AES des URLs
- [x] Validation des données
- [x] Gestion d'erreurs

### ✅ Qualité du Code
- [x] Typage strict PHP
- [x] Validation Symfony
- [x] Architecture propre (Services, DTOs)
- [x] Documentation complète
- [x] Tests de base

---

**🎉 L'API est maintenant complètement documentée et prête pour l'intégration frontend !**