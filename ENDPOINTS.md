# 📡 Nouveaux Endpoints API - Documentation Complète

## 🎯 Vue d'ensemble

Cette documentation couvre tous les nouveaux endpoints créés pour le système de live streaming payant, exposés via API Platform avec documentation Swagger automatique.

---

## 👥 GESTION DES UTILISATEURS

### POST `/api/register`

Enregistre un nouvel utilisateur ou retourne l'utilisateur existant s'il est déjà enregistré avec ce numéro de téléphone.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "fullName": "John Doe",
  "email": "john@example.com",
  "phone": "243999999999"
}
```

**📋 Paramètres :**
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `fullName` | string | ✅ | Nom complet de l'utilisateur |
| `email` | string | ❌ | Adresse email (optionnel) |
| `phone` | string | ✅ | Numéro de téléphone |

**✅ Réponse de succès (201) :**
```json
{
  "id": 1,
  "fullName": "John Doe",
  "email": "john@example.com",
  "phone": "243999999999",
  "isOnline": false,
  "lastActivity": null,
  "createdAt": "2026-02-13T10:30:00+00:00"
}
```

**❌ Réponses d'erreur :**
- **400** : Données invalides (validation échoue)
- **500** : Erreur serveur

### GET `/api/users`

Liste tous les utilisateurs enregistrés avec leur statut en ligne/hors ligne.

**🔒 Authentification :** Token Admin requis

**📝 Headers :**
```
Authorization: Bearer {admin_token}
```

**✅ Réponse de succès (200) :**
```json
[
  {
    "id": 1,
    "fullName": "John Doe",
    "email": "john@example.com",
    "phone": "243999999999",
    "isOnline": true,
    "lastActivity": "2026-02-13T10:35:00+00:00",
    "createdAt": "2026-02-13T10:30:00+00:00"
  },
  {
    "id": 2,
    "fullName": "Jane Smith",
    "email": null,
    "phone": "243888888888",
    "isOnline": false,
    "lastActivity": "2026-02-12T15:20:00+00:00",
    "createdAt": "2026-02-12T14:00:00+00:00"
  }
]
```

**📋 Logique du statut en ligne :**
- `isOnline: true` si l'utilisateur a eu une activité dans les 5 dernières minutes
- `lastActivity` : timestamp de la dernière activité connue

---

## 💰 GESTION DES PAIEMENTS

### POST `/api/payments/initiate`

Initie un processus de paiement pour un utilisateur.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "email": "user@example.com",
  "fullName": "John Doe",
  "phone": "243814063056",
  "paymentMethod": "mobile"
}
```

**📋 Paramètres :**
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `email` | string | ✅ | Email de l'utilisateur |
| `fullName` | string | ✅ | Nom complet |
| `phone` | string | ✅ | Numéro de téléphone |
| `paymentMethod` | string | ✅ | `card` ou `mobile` |

**✅ Réponse de succès (200) :**
```json
{
  "paymentId": 1,
  "status": "processing",
  "amount": "10.00",
  "paymentMethod": "mobile",
  "orderNumber": "ORDER123456",
  "userId": 3,
  "message": "Payment initiated with FlexPay - Data persisted"
}
```

**Pour les paiements par carte :**
```json
{
  "paymentId": 2,
  "status": "processing",
  "amount": "10.00",
  "paymentMethod": "card",
  "orderNumber": "CARD123456",
  "userId": 4,
  "redirectUrl": "https://flexpay-simulation.com/pay/...",
  "message": "Payment initiated with FlexPay - Data persisted"
}
```

**🗄️ Persistance :** Utilisateur et paiement sont automatiquement sauvegardés en base de données SQLite avec Doctrine ORM.

**❌ Réponses d'erreur :**
- **400** : Données invalides
- **500** : Erreur lors de la création du paiement

### POST `/api/payments/confirm`

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
  "amount": "10.00",
  "paymentMethod": "card",
  "transactionReference": "TXN-abc123def",
  "orderNumber": "ORD-123",
  "message": "Payment confirmed successfully. Access code generated."
}
```

**❌ Réponses d'erreur :**
- **400** : ID de paiement invalide
- **404** : Paiement non trouvé ou déjà traité
- **500** : Erreur lors du traitement du paiement

### POST `/api/payments/check-status`

Vérifier le statut d'un paiement FlexPay auprès du service de paiement.

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
| `paymentId` | integer | ✅ | ID du paiement à vérifier |

**✅ Réponse de succès (200) :**
```json
{
  "paymentId": 123,
  "status": "success",
  "orderNumber": "ORDER123456",
  "flexpayStatus": {
    "success": true,
    "waiting": false,
    "message": "Paiement effectué avec success"
  },
  "accessCode": {
    "code": "LIVE-ABC123XYZ",
    "expiresAt": "2024-02-14 10:30:00",
    "isUsed": false
  },
  "message": "Payment confirmed successfully. Access code generated."
}
```

**📋 Statuts possibles :**
- **`success`** : Paiement confirmé et réussi
- **`pending`** : Paiement en cours de traitement
- **`failed`** : Paiement échoué ou rejeté

**❌ Réponses d'erreur :**
- **400** : paymentId manquant ou paiement sans référence de transaction
- **404** : Paiement non trouvé
- **500** : Erreur lors de la vérification

**🔍 flexpayStatus :**
- **`success`** : true/false/null (null si vérification impossible)
- **`waiting`** : true si paiement en attente, false sinon
- **`message`** : Message détaillé de FlexPay

**💡 Notes importantes :**
- **Génération d'access code :** Quand un paiement passe au statut "success", un code d'accès unique est automatiquement généré pour l'utilisateur (valable 24h)
- **Code existant :** Si l'utilisateur a déjà un code d'accès valide, celui-ci est réutilisé au lieu d'en générer un nouveau
- **Mise à jour automatique du statut :** Le statut du paiement est automatiquement mis à jour en base de données selon le résultat FlexPay (success, failed, ou reste pending si en attente)
- **FlexPay indisponible :** Si FlexPay est indisponible, la route retourne le statut actuel du paiement depuis la base de données avec un message d'avertissement

### GET `/api/payments`

Liste tous les paiements effectués.

**🔒 Authentification :** Token Admin requis

**📝 Headers :**
```
Authorization: Bearer {admin_token}
```

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
    "transactionReference": "TXN-abc123def",
    "createdAt": "2026-02-13T10:30:00+00:00"
  }
]
```

---

## 🎫 VALIDATION DES CODES D'ACCÈS

### POST `/api/validate`

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
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expiresIn": 300,
  "message": "Access code validated successfully"
}
```

**📋 Actions effectuées :**
1. Le code est marqué comme utilisé
2. L'utilisateur est marqué comme en ligne
3. Un token JWT temporaire (5 minutes) est généré

**❌ Réponses d'erreur :**
- **400** : Code invalide, expiré ou déjà utilisé
- **500** : Erreur lors de la génération du token

### GET `/api/access_codes`

Liste tous les codes d'accès générés.

**🔒 Authentification :** Token Admin requis

**📝 Headers :**
```
Authorization: Bearer {admin_token}
```

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
    "isUsed": true,
    "usedAt": "2026-02-13T10:35:00+00:00",
    "expiresAt": "2026-02-14T10:30:00+00:00",
    "createdAt": "2026-02-13T10:30:00+00:00"
  }
]
```

---

## 🔄 WORKFLOW COMPLET

### 1. Enregistrement Utilisateur
```bash
POST /api/register
{
  "fullName": "John Doe",
  "phone": "243999999999"
}
```

### 2. Initiation du Paiement avec FlexPay
```bash
POST /api/payments/initiate
{
  "email": "user@example.com",
  "fullName": "John Doe",
  "phone": "243814063056",
  "paymentMethod": "mobile"
}
```

**Actions automatiques :**
- ✅ Enregistrement de l'utilisateur en base (avec déduplication par email)
- ✅ Création du paiement en base (status: processing)
- ✅ Appel API FlexPay (mobilePayment ou cardPayment)
- ✅ Mise à jour du paiement avec la référence FlexPay
- ✅ Retour des informations complètes de paiement

### 3. Confirmation du Paiement
```bash
POST /api/payments/confirm
{
  "paymentId": 123
}
# → Génère automatiquement un code d'accès
```

### 4. Validation du Code
```bash
POST /api/validate
{
  "code": "CINE-A1B2C3D4"
}
# → Retourne token JWT temporaire
```

### 5. Accès au Live
```bash
GET /api/live/watch
Authorization: Bearer {token_from_validation}
```

---

## 🛠️ Intégration FlexPay

Le service de paiement FlexPay est intégré dans `src/Service/Billing/PaymentService.php` avec :

- **Paiement mobile** via `/paymentService`
- **Paiement carte** via `/cardpayment`
- **Vérification du statut** via `/check/{orderNumber}`

### Configuration FlexPay
```php
private $mobileBaseUrlFlexPay = 'https://backend.flexpay.cd/api/rest/v1/';
private $cardBaseUrlFlexPay = 'https://cardpayment.flexpay.cd/v1.1/pay';
private $token = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...';
```

---

## 📊 Modèles de Données Étendus

### Utilisateur avec Statut
```json
{
  "id": "integer",
  "fullName": "string",
  "email": "string|null",
  "phone": "string",
  "isOnline": "boolean",
  "lastActivity": "datetime|null",
  "createdAt": "datetime"
}
```

### Paiement Complet
```json
{
  "id": "integer",
  "user": "User (relation)",
  "amount": "decimal",
  "status": "pending|success|failed",
  "paymentMethod": "card|mobile",
  "transactionReference": "string|null",
  "createdAt": "datetime"
}
```

### Code d'Accès
```json
{
  "id": "integer",
  "user": "User (relation)",
  "code": "string (unique)",
  "isUsed": "boolean",
  "usedAt": "datetime|null",
  "expiresAt": "datetime",
  "createdAt": "datetime"
}
```

---

## 🔐 Sécurité et Authentification

### Tokens JWT
- **Admin Token** : Valide 1 heure pour l'administration
- **Live Access Token** : Valide 5 minutes pour l'accès streaming

### Chiffrement
- URLs de stream chiffrées avec AES-256-GCM
- Clés de chiffrement dans la configuration

### Validation
- Validation stricte des données avec Symfony Validator
- Vérification des formats (email, téléphone, etc.)
- Protection contre les injections

---

## 🧪 Tests et Exemples

### Créer un utilisateur
```bash
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Test User",
    "phone": "243999999999"
  }'
```

### Initier un paiement
```bash
curl -X POST http://localhost:8080/api/payments/initiate \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "fullName": "Test User",
    "phone": "243999999999",
    "paymentMethod": "card"
  }'
```

### Valider un code
```bash
curl -X POST http://localhost:8080/api/validate \
  -H "Content-Type: application/json" \
  -d '{
    "code": "CINE-A1B2C3D4"
  }'
```

---

## 📖 Documentation API

### Swagger UI
```
http://localhost:8080/api/docs
```

### OpenAPI JSON
```
http://localhost:8080/api/docs.json
```

Tous les endpoints sont automatiquement documentés avec :
- Descriptions détaillées
- Exemples de requêtes/réponses
- Codes d'erreur
- Paramètres requis/optionnels

---

## 🔒 ENDPOINTS DE SÉCURITÉ RENFORCÉE

### GET `/api/live/watch`

Accès public direct au stream live via variable d'environnement.

**✅ Réponse de succès (200) :**
```json
{
  "streamUrl": "https://configured-stream-url.com/live",
  "title": "Concert Live Streaming",
  "isLive": true,
  "message": "Stream access granted"
}
```

**❌ Réponses d'erreur :**
- **401** : Token manquant ou invalide
- **500** : URL du stream non configurée

**🛡️ Fonctionnalités :**
- Configuration via variable d'environnement `STREAM_URL`
- Pas d'accès base de données requis
- Validation automatique de l'URL

### PUT `/api/admin/event/update-stream` (OBSOLÈTE)

**OBSOLÈTE** : L'URL du stream est configurée via la variable d'environnement `STREAM_URL`.

**🔒 Authentification :** Token Admin requis

**✅ Réponse informative (200) :**
```json
{
  "message": "Stream URL is configured via STREAM_URL environment variable",
  "currentUrl": "https://configured-stream-url.com/live",
  "configMethod": "environment_variable",
  "note": "Modify the STREAM_URL environment variable to change the stream URL"
}
```

### POST `/api/admin/stream/secure-access` (OBSOLÈTE)

**OBSOLÈTE** : Utilisez `GET /api/live/watch` directement avec le token d'accès live.

**🔒 Authentification :** Token Admin requis

**✅ Réponse informative (200) :**
```json
{
  "message": "Use GET /api/live/watch with live access token",
  "streamEndpoint": "/api/live/watch",
  "note": "Stream URL is configured via STREAM_URL environment variable"
}
```

**🛡️ Niveaux de sécurité :**
- ✅ Token Admin valide
- ✅ Token Live Access valide et non expiré
- ✅ Code d'accès utilisé dans les 10 dernières minutes
- ✅ Utilisateur valide et existant
- ✅ Événement actif en direct
- ✅ Logging automatique de tous les accès
- ✅ Timestamp de validation temps réel

---

**🎉 L'API complète est maintenant opérationnelle avec tous les endpoints demandés et sécurisés !**