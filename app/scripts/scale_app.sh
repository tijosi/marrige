#!/bin/bash

# Variáveis
APP_NAME="marrige-back"
HEROKU_API_KEY="$HEROKU_API_KEY"

# Verifique o argumento de entrada
if [ "$1" == "start" ]; then
    # Comando para ligar a aplicação
    curl -n -X PATCH https://api.heroku.com/apps/$APP_NAME/formation \
    -H "Authorization: Bearer $HEROKU_API_KEY" \
    -H "Accept: application/vnd.heroku+json; version=3" \
    -H "Content-Type: application/json" \
    -d '{
      "updates": [
        {
          "type": "web",
          "quantity": 1
        }
      ]
    }'
elif [ "$1" == "stop" ]; then
    # Comando para desligar a aplicação
    curl -n -X PATCH https://api.heroku.com/apps/$APP_NAME/formation \
    -H "Authorization: Bearer $HEROKU_API_KEY" \
    -H "Accept: application/vnd.heroku+json; version=3" \
    -H "Content-Type: application/json" \
    -d '{
      "updates": [
        {
          "type": "web",
          "quantity": 0
        }
      ]
    }'
else
    echo "Uso: $0 {start|stop}"
    exit 1
fi
