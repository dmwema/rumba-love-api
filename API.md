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
# Toutes les routes sont publiques - pas d'authentification requise
```

### Nouveaux Endpoints Ajoutés

Cette API inclut maintenant tous les endpoints demandés :

- **👥 Gestion Utilisateurs** : Enregistrement, listing avec statuts en ligne
- **💰 Gestion Paiements** : Initiation et confirmation avec FlexPay
- **🎫 Validation Codes** : Vérification et génération de tokens live
- **📊 Administration Complète** : Gestion de tous les aspects système

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

## 👥 GESTION DES UTILISATEURS

### POST `/register`

Enregistre un nouvel utilisateur ou retourne l'utilisateur existant.

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
| `phone` | string | ✅ | Numéro de téléphone (stocké dans le paiement) |

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

### GET `/users`

Liste tous les utilisateurs avec leur statut en ligne/hors ligne.

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

### POST `/payments/initiate`

Initie un processus de paiement pour un utilisateur.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "email": "user@example.com",
  "fullName": "John Doe",
  "phone": "243999999999",
  "paymentMethod": "card"
}
```

**📋 Paramètres :**
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `email` | string | ✅ | Email de l'utilisateur |
| `fullName` | string | ✅ | Nom complet |
| `phone` | string | ✅ | Numéro de téléphone (stocké dans le paiement) |
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

**🗄️ Persistance :** Toutes les données (utilisateur + paiement) sont automatiquement sauvegardées en base de données SQLite avec Doctrine ORM.

**❌ Réponses d'erreur :**
- **400** : Données invalides
- **500** : Erreur lors de la création du paiement

### POST `/payments/confirm`

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

### POST `/payments/check-status`

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

**💡 Notes importantes :**
- **Stockage du numéro :** Le numéro de téléphone est maintenant stocké directement dans l'entité Payment pour les paiements mobiles
- **Génération d'access code :** Quand un paiement passe au statut "success", un code d'accès unique est automatiquement généré pour l'utilisateur (valable 24h)
- **Code existant :** Si l'utilisateur a déjà un code d'accès valide, celui-ci est réutilisé au lieu d'en générer un nouveau
- **Mise à jour automatique du statut :** Le statut du paiement est automatiquement mis à jour en base de données selon le résultat FlexPay
- **Numéro de test :** Le numéro `243999999999` est traité comme un paiement de test et passe automatiquement au statut "success"
- **Session persistante :** Après validation du code, un token de session est généré pour éviter de redemander le code lors des prochaines connexions

**❌ Réponses d'erreur :**
- **400** : paymentId manquant ou paiement sans référence de transaction
- **404** : Paiement non trouvé
- **500** : Erreur lors de la vérification

### POST `/api/card-payments/initiate`

Initie un paiement par carte bancaire auprès de FlexPay.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "email": "user@example.com",
  "fullName": "John Doe"
}
```

**ℹ️ Note :** Contrairement aux paiements mobiles, les informations de carte bancaire ne sont pas collectées côté API. L'utilisateur est redirigé vers FlexPay pour saisir ses informations de paiement en toute sécurité.

**📋 Paramètres :**
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `email` | string | ✅ | Email de l'utilisateur |
| `fullName` | string | ✅ | Nom complet de l'utilisateur |

**✅ Réponse de succès (200) :**
```json
{
  "paymentId": 123,
  "status": "processing",
  "amount": "5.00",
  "paymentMethod": "card",
  "orderNumber": "CARD-123456789-1234567890",
  "redirectUrl": "https://cardpayment.flexpay.cd/pay/CARD-123456789-1234567890",
  "message": "Card payment initiated. Redirect user to FlexPay."
}
```

**📋 Processus de paiement par carte :**
1. **Initiation** : Collecte des informations de carte et création du paiement
2. **Redirection** : Utilisateur redirigé vers FlexPay pour paiement sécurisé
3. **Callback** : FlexPay appelle `/api/card-payments/callback` avec le résultat
4. **Finalisation** : Génération d'access code si paiement réussi

**❌ Réponses d'erreur :**
- **400** : Données de carte invalides ou manquantes
- **500** : Erreur lors de l'initiation du paiement

### POST `/api/card-payments/callback`

Callback automatique appelé par FlexPay après traitement du paiement par carte.

**🔓 Authentification :** Non requise (appelé par FlexPay)

**📝 Paramètres (query ou body) :**
- `orderNumber` : Numéro de commande FlexPay
- `status` : Statut du paiement (`success`, `failed`, `cancelled`)

**✅ Réponse :**
```json
{
  "message": "Payment callback processed",
  "orderNumber": "CARD-123456789-1234567890",
  "status": "success"
}
```

### POST `/card-payments/initiate`

Initie un paiement par carte bancaire avec redirection vers FlexPay.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
{
  "email": "user@example.com",
  "fullName": "John Doe"
}
```

**ℹ️ Note :** Contrairement aux paiements mobiles, les informations de carte bancaire ne sont pas collectées côté API. L'utilisateur est redirigé vers FlexPay pour saisir ses informations de paiement en toute sécurité.

**📋 Paramètres :**
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `email` | string | ✅ | Email de l'utilisateur |
| `fullName` | string | ✅ | Nom complet |

**✅ Réponse de succès (200) :**
```json
{
  "paymentId": 123,
  "status": "processing",
  "amount": "5.00",
  "paymentMethod": "card",
  "orderNumber": "CARD-123-1640995200",
  "redirectUrl": "https://cardpayment.flexpay.cd/pay/CARD-123-1640995200",
  "message": "Card payment initiated. Redirect user to FlexPay."
}
```

**📋 Flux de paiement par carte :**
1. **Initiation** : Appel `/card-payments/initiate` avec les détails de carte
2. **Redirection** : Rediriger l'utilisateur vers l'`redirectUrl` fourni
3. **Traitement** : FlexPay traite le paiement
4. **Callback** : FlexPay appelle automatiquement `/card-payments/callback`
5. **Finalisation** : Système met à jour le statut et génère l'access code

**❌ Réponses d'erreur :**
- **400** : Données de carte invalides ou manquantes
- **500** : Erreur lors de l'initiation du paiement

### GET/POST `/card-payments/callback`

Endpoint de callback appelé automatiquement par FlexPay après traitement du paiement.

**🔓 Authentification :** Non requise (appelé par FlexPay)

**📝 Paramètres (query ou POST) :**
- `orderNumber` : Numéro de commande FlexPay
- `status` : Statut du paiement (`success`, `failed`, `cancelled`)

### GET `/payments`

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

## 🎬 ACCÈS AU STREAM LIVE

### POST `/api/live/watch`

Accéder au stream en direct avec validation du code d'accès ou token de session.

**🔓 Authentification :** Non requise

**📝 Corps de la requête :**
```json
// Première connexion (avec code d'accès)
{
  "code": "CINE-9C52QW4"
}

// Connexions suivantes (avec token de session)
{
  "sessionToken": "abc123def456ghi789"
}
```

**📋 Paramètres :**
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `code` | string | ❌* | Code d'accès valide (première utilisation) |
| `sessionToken` | string | ❌* | Token de session (utilisations suivantes) |

*Un des deux paramètres est requis

**✅ Réponse de succès (200) :**
```json
{
  "streamUrl": "https://configured-stream-url.com/live",
  "title": "Concert Live Streaming",
  "isLive": true,
  "message": "Stream access granted",
  "sessionToken": "abc123def456ghi789",
  "user": {
    "id": 1,
    "fullName": "John Doe",
    "email": "john@example.com"
  }
}
```

**📋 Configuration :**
- L'URL du stream est configurée via la variable d'environnement `STREAM_URL`
- Validation du code d'accès en base de données
- Marquage automatique du code comme utilisé
- Mise à jour du statut en ligne de l'utilisateur

**❌ Réponses d'erreur :**
- **400** : Code manquant, invalide ou expiré
- **500** : URL du stream non configurée
- **500** : URL du stream non configurée

---

## 🎫 VALIDATION DES CODES D'ACCÈS

### POST `/validate`

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

### GET `/access_codes`

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

## 💳 PAIEMENT

### POST `/payment/initiate`

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
| `email` | string | ✅ | Email valide de l'utilisateur |
| `fullName` | string | ✅ | Nom complet (2-255 caractères) |
| `phone` | string | ❌ | Numéro de téléphone international |
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

**🗄️ Persistance :** Toutes les données (utilisateur + paiement) sont automatiquement sauvegardées en base de données SQLite avec Doctrine ORM.

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

### PUT `/admin/event/update-stream` (OBSOLÈTE)

**Cette endpoint est maintenant OBSOLÈTE.** L'URL du stream est configurée via la variable d'environnement `STREAM_URL`.

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

### POST `/admin/stream/secure-access` (OBSOLÈTE)

**Cette endpoint est maintenant OBSOLÈTE.** Utilisez directement `GET /api/live/watch` avec le token d'accès live.

**🔒 Authentification :** Token Admin requis

**✅ Réponse informative (200) :**
```json
{
  "message": "Use GET /api/live/watch with live access token",
  "streamEndpoint": "/api/live/watch",
  "note": "Stream URL is configured via STREAM_URL environment variable"
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
  "email": "string (unique, nullable)",
  "fullName": "string (2-255 chars)",
  "phone": "string (nullable)",
  "isOnline": "boolean",
  "lastActivity": "datetime (nullable)",
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

#### Utilisateurs
- `Invalid user data` (400)
- `User not found` (404)

#### Paiement
- `Payment not found or already processed` (404)
- `Invalid payment data` (400)
- `Payment processing failed` (500)

#### Code d'accès
- `Invalid or expired access code` (400)
- `Code already used` (400)
- `Access code validation failed` (500)

#### Live
- `No active live event` (404)
- `Unable to access stream` (500)

#### Administration
- `No live event found` (404)
- `Failed to encrypt stream URL` (500)
- `Invalid admin credentials` (401)

---

## 🔄 FLOWS UTILISATEUR

### Flow d'Achat Complet (Nouveau Système)

```mermaid
sequenceDiagram
    participant U as Utilisateur
    participant F as Frontend
    participant A as API
    participant DB as Base de données

    F->>A: GET /api/event
    A->>DB: Récupérer événement
    A->>F: Données de l'événement

    U->>F: Formulaire d'enregistrement
    F->>A: POST /api/register
    A->>DB: Créer ou récupérer User
    A->>F: Confirmation enregistrement

    U->>F: Soumettre formulaire paiement
    F->>A: POST /api/payments/initiate
    A->>DB: Créer Payment
    A->>F: paymentId

    Note over F: Paiement FlexPay (mobile/carte)
    F->>A: POST /api/payments/confirm
    A->>A: Intégrer FlexPay
    A->>DB: Marquer paiement success + Générer AccessCode
    A->>F: Confirmation + Code d'accès

    U->>F: Saisir code d'accès
    F->>A: POST /api/validate
    A->>DB: Valider code + Marquer user online
    A->>F: Token JWT temporaire (5 min)

    F->>A: GET /api/live/watch
    A->>DB: Vérifier token et déchiffrer URL
    A->>F: URL du stream
```

### Flow d'Administration Complet

```mermaid
sequenceDiagram
    participant A as Admin
    participant API as API Admin
    participant DB as Base de données

    A->>API: POST /auth/admin
    API->>API: Générer token JWT
    API->>A: Token admin (1 heure)

    A->>API: GET /api/users (avec token)
    API->>DB: Récupérer users avec statuts
    API->>A: Liste utilisateurs + statuts online

    A->>API: GET /api/payments (avec token)
    API->>DB: Récupérer paiements
    API->>A: Liste paiements avec statuts

    A->>API: GET /api/access_codes (avec token)
    API->>DB: Récupérer codes d'accès
    API->>A: Liste codes avec statuts

    A->>API: PUT /admin/event/update-stream (avec token)
    API->>API: Chiffrer URL
    API->>DB: Sauvegarder URL chiffrée

    A->>API: PUT /admin/event/activate (avec token)
    API->>DB: Activer/désactiver événement
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

### Exemple complet de session utilisateur (Nouveau Système)

```bash
# 1. Obtenir les infos de l'événement
curl -X GET http://localhost:8000/api/event

# 2. S'enregistrer (ou récupérer utilisateur existant)
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Test User",
    "phone": "243999999999"
  }'

# 3. Initier un paiement
curl -X POST http://localhost:8000/api/payments/initiate \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "fullName": "Test User",
    "phone": "243999999999",
    "paymentMethod": "card"
  }'

# 4. Confirmer le paiement (avec FlexPay)
curl -X POST http://localhost:8000/api/payments/confirm \
  -H "Content-Type: application/json" \
  -d '{"paymentId": 1}'

# 5. Valider le code d'accès généré
curl -X POST http://localhost:8000/api/validate \
  -H "Content-Type: application/json" \
  -d '{"code": "CINE-A1B2C3D4"}'

# 6. Accéder au live avec le token
curl -X GET http://localhost:8000/api/live/watch
```

### Tests d'Administration

```bash
# 1. Connexion admin
curl -X POST http://localhost:8080/auth/admin \
  -H "Content-Type: application/json" \
  -d '{"username": "fils@cinefilm.cd", "password": "p@ssword123654"}'

# 2. Lister utilisateurs avec statuts
curl -X GET http://localhost:8080/api/users \
  -H "Authorization: Bearer {admin_token}"

# 3. Lister paiements
curl -X GET http://localhost:8080/api/payments \
  -H "Authorization: Bearer {admin_token}"

# 4. Lister codes d'accès
curl -X GET http://localhost:8080/api/access_codes \
  -H "Authorization: Bearer {admin_token}"
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

### Services Intégrés

- **FlexPay** : Service de paiement mobile et carte bancaire
- **API Platform** : Framework API avec documentation automatique
- **LexikJWT** : Gestion des tokens JWT
- **Doctrine ORM** : Mapping objet-relationnel

### Intégration FlexPay

Le service de paiement FlexPay est configuré avec :

```php
// Configuration dans src/Service/Billing/PaymentService.php
private $mobileBaseUrlFlexPay = 'https://backend.flexpay.cd/api/rest/v1/';
private $cardBaseUrlFlexPay = 'https://cardpayment.flexpay.cd/v1.1/pay';
private $token = 'Bearer {token_flexpay}';

// Méthodes disponibles :
- mobilePayment($operation) : Paiement mobile
- cardPayment($operation) : Paiement carte
- checkPaymentStatus($operation) : Vérification statut
```

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
- [x] Enregistrement utilisateurs avec statuts en ligne
- [x] Paiement intégré avec FlexPay (mobile/carte)
- [x] Validation de code avec génération automatique
- [x] Accès live sécurisé avec tokens temporaires
- [x] Panel d'administration complet
- [x] API Platform avec documentation Swagger
- [x] Gestion complète des spectateurs et paiements

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

---

## 📋 RÉSUMÉ DES NOUVEAUX ENDPOINTS

### 🎯 Tous les Endpoints Demandés Implémentés

| Endpoint | Méthode | Description | Authentification |
|----------|---------|-------------|------------------|
| `POST /api/register` | Enregistrement utilisateur | fullname, email?, phone | Non requise |
| `GET /api/users` | Liste utilisateurs + statuts | isOnline, lastActivity | Admin requis |
| `POST /api/payments/initiate` | Initier paiement | FlexPay integration | Non requise |
| `POST /api/payments/confirm` | Confirmer paiement | Génère code automatiquement | Non requise |
| `GET /api/payments` | Liste paiements | Tous les paiements système | Admin requis |
| `POST /api/validate` | Valider code accès | Génère token live | Non requise |
| `GET /api/access_codes` | Liste codes accès | Tous les codes générés | Admin requis |
| `POST /auth/admin` | Login administrateur | Retourne token JWT | Non requise |

### 🏗️ Architecture Complète

- **API Platform** : Framework moderne avec opérations personnalisées
- **DTOs** : Objets de transfert validés pour chaque endpoint
- **Contrôleurs** : Logique métier séparée et réutilisable
- **Services** : Intégration FlexPay et logique métier
- **Entités** : Modèles enrichis avec statuts en ligne

### 📚 Documentation Complète

- **API.md** : Cette documentation complète
- **ENDPOINTS.md** : Guide détaillé des nouveaux endpoints
- **README.md** : Guide de démarrage et configuration
- **Swagger UI** : Documentation interactive à `/api/docs`

**🎉 L'API complète de live streaming payant est maintenant 100% opérationnelle avec tous les endpoints demandés !**