# How to setup project locally

## via docker and docker-compose
run in a terminal (preferably in a unix-environment or in wsl-2 if available)
```bash
docker-compose up
```
lets try out if it worked: http://localhost:80 

### find out database-server-ip
```bash
docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' build_db_1
```


## via XAMP
 TODO document
