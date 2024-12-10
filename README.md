# MidLii

A fork of the Kolyma VidLii code + patches. 

The motivation for the project was that no one could get the VidLii source code to work, so I decided to fix it myself for fun. I used different source codes that became available over time and patched the code to make it work.

Most of the site is already working. If you have any problems, you can open an issue, remembering that only the Docker setup is supported.

## Docker

You **NEED** to run the commands on a non-root user and you have to have Docker installed and running.

- `git clone https://github.com/MidLii/MidLii`
- Get the uid `id -u`  and the gid `id -g` of the logged user
- Edit args in docker-compose.yml file - UID, GID, and env if necessary
- `sudo docker compose up -d --build`
- Service will be exposed at 7890 and Phpmyadmin at 7891. It's recommended you use a reverse proxy using Nginx/Caddy/etc if you wanna use a domain - figure it out yourself

# Disclaimer
This project is intended for personal use only.