# osl-mw-docker-compose
Docker Compose for Mediawiki + OpenSemanticLab


## Deploy

### Prerequisites 

Required

- [Docker](https://docs.docker.com/engine/install/)
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)

Optional
- [Nginx](https://docs.nginx.com/nginx/admin-guide/installing-nginx/installing-nginx-open-source/) for SSL Termination
- [Certbot](https://certbot.eff.org/instructions) to create SSL/TSL certs with [Let’s Encrypt](https://letsencrypt.org)

  
### Clone

Clone & init the repo

```bash
git clone https://github.com/OpenSemanticLab/osl-mw-docker-compose
cd osl-mw-docker-compose
sudo chown -R www-data:www-data mediawiki/data
```


### Config

Copy .env.example to .env
```
cp .env.example .env
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
MW_PAGE_PACKAGES=world.opensemantic.core;world.opensemantic.base;world.opensemantic.demo.common
MW_AUTOIMPORT_PAGES=true
MW_AUTOBUILD_SITEMAP=false

MYSQL_HOST_PORT=3307
MYSQL_ROOT_PASSWORD=change_me

DRAWIO_HOST_PORT=8082
DRAWIO_SERVER=http://drawio:80

GRAPHDB_HOST_PORT=9999
```

Optional partial overwrite of `docker-compose.yaml` with `docker-compose.override.yaml`, e. g.
```yaml
version: '3.8'

services:
    mediawiki:
        volumes:
            - ./mediawiki/config/logo.png:/var/www/html/w/logo.png
            - ./mediawiki/config/logo.svg:/var/www/html/w/logo.svg
            - ./mediawiki/extensions/MyCustomExtension:/var/www/html/w/extensions/MyCustomExtension
```


### Settings

You can add or overwrite mediawiki settings by editing `mediawiki/config/CustomSettings.php`,
e. g. to make your instance public readable add:
```php
####### Make it public ########
$wgGroupPermissions['*']['read'] = true;
```

Please note: Content packages defined by MW_PAGE_PACKAGES will be install automatically.
Optional packages listed [here](https://github.com/OpenSemanticLab/PagePackages/blob/main/package_index.txt) can be installed under `<your wiki domain>/wiki/Special:Packages`. Package sources are hosted [here](https://github.com/orgs/OpenSemanticWorld-Packages/repositories).
To add additional optional packages, add 
```
$wgPageExchangePackageFiles[] = 'packages.json url';
```
e. g. 
```
$wgPageExchangePackageFiles[] = 'https://raw.githubusercontent.com/OpenSemanticWorld-Packages/world.opensemantic.meta.docs/main/packages.json';
```
to `mediawiki/config/CustomSettings.php`

### Run

```bash
docker compose up
```

Depending on the size of the packages defined in `MW_PAGE_PACKAGES` it will take some time to install them in the background.


## Maintenance

Run the following commands inside the mediawiki container if you run in one of the following problems

- missing semantic properties after backup restore
```bash
php /var/www/html/w/extensions/SemanticMediaWiki/maintenance/rebuildData.php
```

- no search results after backup restore
```bash
php /var/www/html/w/extensions/CirrusSearch/maintenance/ForceSearchIndex.php
```

- incorrect link labels (page name instead of display name) after template changes or large imports
```bash
php /var/www/html/w/maintenance/refreshLinks.php
```

- missing thumbnails for tif images
```bash
php /var/www/html/w/maintenance/refreshImageMetadata.php --force
```


## Backup
```bash
mkdir backup
docker-compose exec db /bin/bash -c 'mysqldump --all-databases -uroot -p"$MYSQL_ROOT_PASSWORD" 2>/dev/null | gzip | base64 -w 0' | base64 -d > backup/db_backup_$(date +"%Y%m%d_%H%M%S").sql.gz
tar -zcf backup/file_backup_$(date +"%Y%m%d_%H%M%S").tar mediawiki/data
```


## Reset

To reset your instance and destroy all data run

```bash
docker compose down -v
sudo rm -R mysql/data/* && sudo rm -R blazegraph/data/* && sudo rm -R mediawiki/data/*
docker compose up
```


## Restore

reset your instance first then import your backup
(get your container name, e. g. `docker-compose-osl-wiki_db_1`, with `docker ps -a`)
```bash
zcat backup/db_backup_<date>.sql.gz | docker exec -i <container> sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
tar -xf backup/file_backup_<date>.tar
chown -R www-data:www-data mediawiki/data
```
