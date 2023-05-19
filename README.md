# osl-mw-docker-compose
Docker Compose for Mediawiki + OpenSemanticLab

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

## Deploy

Clone & init the repo

```bash
git clone https://github.com/OpenSemanticLab/osl-mw-docker-compose
cd osl-mw-docker-compose
sudo chown -R www-data:www-data mediawiki/data
```

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
