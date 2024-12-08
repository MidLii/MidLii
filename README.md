# MidLii

A fork of the Kolyma VidLii code + patches. At the moment the code is very vulnerable and unstable, I do not recommend using it and exposing it on the internet without taking 
precautions, it should only be used for development purposes.

## Docker setup

- git clone this repository
- edit envs in docker-compose.yml file if necessary
- docker compose up -d
- service will be exposed at 7890 and phpmyadmin at 7891. do reverse proxy using nginx/caddy/etc if you wanna use a domain


