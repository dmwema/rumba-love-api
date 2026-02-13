#!/bin/bash

# Test de connexion administrateur
echo "🔐 Test de connexion administrateur..."

# 1. Tentative de connexion
echo "📤 Envoi de la requête de connexion..."
RESPONSE=$(curl -s -X POST http://localhost:8080/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "fils@cinefilm.cd",
    "password": "p@ssword123654"
  }')

echo "📥 Réponse reçue :"
echo "$RESPONSE" | jq . 2>/dev/null || echo "$RESPONSE"

# 2. Extraire le token si succès
TOKEN=$(echo "$RESPONSE" | jq -r '.token' 2>/dev/null)

if [ "$TOKEN" != "null" ] && [ -n "$TOKEN" ]; then
    echo ""
    echo "✅ Token JWT obtenu !"
    echo "🔑 Token: $TOKEN"

    # 3. Tester un endpoint protégé avec le token
    echo ""
    echo "🧪 Test d'un endpoint protégé..."
    USERS_RESPONSE=$(curl -s -X GET http://localhost:8080/api/admin/users \
      -H "Authorization: Bearer $TOKEN")

    echo "👥 Réponse utilisateurs :"
    echo "$USERS_RESPONSE" | jq . 2>/dev/null || echo "$USERS_RESPONSE"
else
    echo ""
    echo "❌ Échec de l'authentification"
fi