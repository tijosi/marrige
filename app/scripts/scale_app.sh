#!/bin/bash

APP_NAME="marrige-back"

if [ "$1" == "start" ]; then
    heroku ps:scale web=1 --app $APP_NAME
elif [ "$1" == "stop" ]; then
    heroku ps:scale web=0 --app $APP_NAME
else
    echo "Uso: $0 {start|stop}"
    exit 1
fi
