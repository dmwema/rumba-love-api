# Configuration de l'URL du Stream Live

## 🎯 Vue d'ensemble

L'URL du stream live est maintenant configurée via une **variable d'environnement** au lieu d'être modifiée via l'administration. Cela simplifie considérablement le système et évite les problèmes de base de données.

## 🔧 Configuration

### 1. Créer ou modifier le fichier `.env.local`

Créez un fichier `.env.local` dans la racine du projet (ce fichier n'est pas commité dans Git) :

```bash
# Créer le fichier
touch .env.local
```

### 2. Ajouter la variable STREAM_URL

Ajoutez cette ligne dans `.env.local` :

```env
# URL du stream live - REMPLACEZ PAR VOTRE URL RÉELLE
STREAM_URL=https://votre-plateforme-stream.com/live/concert-stream
```

**Exemples d'URLs valides :**
```env
# YouTube Live
STREAM_URL=https://www.youtube.com/watch?v=VIDEO_ID

# Twitch
STREAM_URL=https://www.twitch.tv/votre_chaine

# Plateforme personnalisée
STREAM_URL=https://streaming.votre-domaine.com/live/concert

# URL directe (RTMP, HLS, etc.)
STREAM_URL=https://cdn.streaming.com/concert.m3u8
```

### 3. Redémarrer le serveur

Après avoir modifié `.env.local`, redémarrez le serveur Symfony :

```bash
# Arrêter le serveur actuel
# Puis redémarrer
symfony serve --port=8000
```

## ✅ Vérification

### Tester la configuration

Une fois configuré, vous pouvez vérifier que l'URL est correctement chargée :

```bash
# Via l'endpoint admin (avec token JWT)
curl -X PUT http://localhost:8000/api/admin/event/update-stream \
  -H "Authorization: Bearer {votre_token_admin}"

# Réponse attendue :
{
  "message": "Stream URL is configured via STREAM_URL environment variable",
  "currentUrl": "https://votre-plateforme-stream.com/live/concert-stream",
  "configMethod": "environment_variable",
  "note": "Modify the STREAM_URL environment variable to change the stream URL"
}
```

### Tester l'accès au stream

```bash
# 1. Obtenir un token live via validation de code
curl -X POST http://localhost:8000/api/validate \
  -H "Content-Type: application/json" \
  -d '{"code": "CINE-A1B2C3D4"}'

# 2. Accéder au stream avec le token obtenu
curl -X GET http://localhost:8000/api/live/watch \
  -H "Authorization: Bearer {live_token}"

# Réponse attendue :
{
  "streamUrl": "https://votre-plateforme-stream.com/live/concert-stream",
  "title": "Concert Live Streaming",
  "isLive": true,
  "message": "Stream access granted"
}
```

## 🔒 Sécurité

- ✅ **Validation automatique** : L'URL est validée au démarrage
- ✅ **Aucune persistance** : L'URL n'est pas stockée en base de données
- ✅ **Configuration serveur** : Sécurisé au niveau infrastructure
- ✅ **Changement à chaud** : Modification possible sans redéploiement (redémarrage serveur requis)

## 🚀 Avantages

1. **Simplicité** : Plus besoin de gérer l'URL via l'administration
2. **Performance** : Pas d'accès base de données pour récupérer l'URL
3. **Sécurité** : Configuration au niveau serveur/infrastructure
4. **Flexibilité** : Changement facile selon l'environnement (dev/staging/prod)
5. **Fiabilité** : Pas de risque de corruption des données chiffrées

## 📋 Variables d'environnement disponibles

| Variable | Description | Exemple |
|----------|-------------|---------|
| `STREAM_URL` | URL du stream live | `https://stream.com/live` |
| `APP_ENV` | Environnement | `dev`, `prod` |
| `APP_SECRET` | Clé secrète | `your_secret_key` |

## 🔄 Migration depuis l'ancien système

Si vous aviez une URL configurée via l'administration :

1. **Récupérez l'URL actuelle** via l'endpoint admin
2. **Ajoutez-la dans `.env.local`**
3. **Redémarrez le serveur**
4. **Vérifiez le fonctionnement**

L'ancien système reste compatible mais est marqué comme OBSOLÈTE.