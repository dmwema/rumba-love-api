# 📖 Documentation API - Guide d'accès

## ⚠️ IMPORTANT : URLs Correctes

### 🚨 Problème Courant
Si vous accédez à `http://localhost:8080/api`, vous verrez du JSON brut. **Ce n'est pas la documentation !**

### ✅ URLs Correctes

#### Interface Swagger UI (Recommandé)
```
http://localhost:8000/api/docs
```
**OU si vous utilisez le port 8080 :**
```
http://localhost:8080/api/docs
```

#### Point d'entrée API (JSON brut)
```
http://localhost:8000/api/  ← Retourne du JSON
```

## 🎯 Accès à la Documentation Interactive

### Interface Swagger UI (Recommandé)
```
http://localhost:8000/api/docs
```

Cette interface vous permet de :
- ✅ Explorer tous les endpoints de l'API
- ✅ Tester les endpoints en temps réel
- ✅ Voir les modèles de données
- ✅ Authentifier avec JWT
- ✅ Visualiser les réponses

### Formats de Documentation

#### JSON OpenAPI
```
http://localhost:8000/api/docs.json
```

#### YAML OpenAPI
```
http://localhost:8000/api/docs.yaml
```

## 🏗️ Structure de l'API

### Point d'entrée principal
```
GET http://localhost:8000/api/
```
Retourne la liste de toutes les ressources disponibles avec leurs URLs.

### Organisation par Tags

La documentation est organisée en sections logiques :

#### 🎪 Événement Public
- `GET /api/event` - Informations du concert

#### 💳 Paiement
- `POST /api/payments/initiate` - Initier un paiement
- `POST /api/payments/confirm` - Confirmer un paiement

#### 🎫 Validation de Code
- `POST /api/code/validate` - Valider un code d'accès

#### 📺 Accès Live
- `GET /api/live/watch` - Accéder au stream (nécessite token)

#### 👨‍💼 Administration
- `POST /api/admin/login` - Authentification admin
- `GET /api/admin/users` - Liste utilisateurs
- `GET /api/admin/payments` - Liste paiements
- `GET /api/admin/accesscodes` - Liste codes d'accès
- `PUT /api/admin/event/update-stream` - Modifier URL stream
- `PUT /api/admin/event/activate` - Activer/désactiver événement

## 🔐 Authentification dans Swagger

### Pour les endpoints admin :
1. Aller dans l'onglet "Authorize" 🔒
2. Entrer : `Bearer {votre_token_jwt}`
3. Cliquer sur "Authorize"

### Pour les endpoints live :
1. Obtenir un token via `POST /api/code/validate`
2. Utiliser ce token dans l'en-tête Authorization

## 🧪 Test des Endpoints

### Exemple complet de workflow :

1. **Obtenir les infos de l'événement**
   - Endpoint : `GET /api/event`
   - Pas d'authentification requise

2. **Initier un paiement**
   - Endpoint : `POST /api/payments/initiate`
   - Body :
   ```json
   {
     "email": "test@example.com",
     "fullName": "Test User",
     "paymentMethod": "card"
   }
   ```

3. **Confirmer le paiement**
   - Endpoint : `POST /api/payments/confirm`
   - Body :
   ```json
   {
     "paymentId": 1
   }
   ```

4. **Valider le code d'accès**
   - Endpoint : `POST /api/code/validate`
   - Body :
   ```json
   {
     "code": "CINE-A1B2C3D4"
   }
   ```
   - Retourne un token JWT temporaire

5. **Accéder au live**
   - Endpoint : `GET /api/live/watch`
   - Header : `Authorization: Bearer {token_du_code}`

## 📊 Modèles de Données

### Utilisateur (User)
```json
{
  "id": 1,
  "email": "user@example.com",
  "fullName": "John Doe",
  "phone": "+243123456789",
  "createdAt": "2026-02-13T10:30:00+00:00"
}
```

### Paiement (Payment)
```json
{
  "id": 123,
  "user": {...},
  "amount": "10.00",
  "status": "success",
  "paymentMethod": "card",
  "transactionReference": "TXN-ABC123",
  "createdAt": "2026-02-13T10:30:00+00:00"
}
```

### Code d'accès (AccessCode)
```json
{
  "id": 1,
  "user": {...},
  "code": "CINE-A1B2C3D4",
  "isUsed": false,
  "expiresAt": "2026-02-14T10:30:00+00:00",
  "createdAt": "2026-02-13T10:30:00+00:00"
}
```

## 🚀 Démarrage Rapide

### 1. Vérifier que le serveur tourne
```bash
# Port par défaut de Symfony
symfony serve
# OU
php bin/console cache:clear && symfony serve
```

### 2. URLs à tester (adapter le port selon votre configuration)

#### ✅ Documentation Swagger UI
- `http://localhost:8000/api/docs` (port Symfony par défaut)
- `http://localhost:8080/api/docs` (si vous utilisez le port 8080)

#### ✅ Documentation JSON
- `http://localhost:8000/api/docs.json`
- `http://localhost:8080/api/docs.json`

#### ✅ Point d'entrée API (retourne du JSON)
- `http://localhost:8000/api/`
- `http://localhost:8080/api/`

### 3. Dépannage

#### Si vous voyez du JSON au lieu de l'interface Swagger :

**❌ MAUVAISE URL :**
```
http://localhost:8080/api      ← Retourne du JSON brut
```

**✅ BONNE URL :**
```
http://localhost:8080/api/docs ← Interface Swagger UI
```

#### Vérifier le port du serveur :
```bash
# Voir les processus en cours
netstat -tulpn | grep :8000
# OU
netstat -tulpn | grep :8080
```

#### Changer le port si nécessaire :
```bash
# Démarrer sur le port 8080
symfony serve --port=8080

# OU utiliser PHP directement
php -S localhost:8080 -t public/
```

### 4. Tester l'API
Utiliser l'interface Swagger pour explorer et tester les endpoints en temps réel.

## 🔧 Configuration CORS

L'API accepte les requêtes depuis n'importe quelle origine (`*`) pour faciliter les tests.

## 📞 Support

- **Documentation complète** : `API.md`
- **Guide de déploiement** : `README.md`
- **Interface interactive** : `/api/docs`

---

**🎉 Votre API est maintenant documentée avec Swagger/OpenAPI !**