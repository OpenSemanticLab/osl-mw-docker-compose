# osl-mw-docker-compose
Docker Compose for Mediawiki + OpenSemanticLab

Please note that this is currently a pure software stack without any content. 
We will add content packages soon.

## Deploy

Clone & init the repo

```bash
git clone https://github.com/OpenSemanticLab/osl-mw-docker-compose
cd osl-mw-docker-compose
sudo chown -R www-data:www-data mediawiki/data
```

Set the config parameters in .env

Example:
```
MW_HOST_PORT=8081
MW_SITE_SERVER=http://localhost:80
MW_SITE_NAME=Wiki
MW_SITE_LANG=en
MW_TIME_ZONE=Europe/Berlin
MW_ADMIN_PASS=change_me
MW_DB_PASS=change_me
MW_PAGE_PACKAGES=org.open-semantic-lab.core;org.open-semantic-lab.demo
MW_AUTOIMPORT_PAGES=true

MYSQL_HOST_PORT=3307
MYSQL_ROOT_PASSWORD=change_me

DRAWIO_HOST_PORT=8082
DRAWIO_SERVER=http://drawio:80

GRAPHDB_HOST_PORT=9999

SSH_HOST_PORT=221
SSH_PASS=change_me
```

Run

```bash
docker compose up
```

Reset

```bash
docker compose down
sudo rm -R mysql/data && sudo rm -R blazegraph/data && sudo rm -R mediawiki/data/
docker compose up
```
