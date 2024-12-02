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
```env
MW_HOST_PORT=8081 # the port mediawiki exposes on the host
MW_SITE_SERVER=http://localhost:8081
MW_SITE_NAME=Wiki # the name of your instance
MW_SITE_LANG=en # the site language. Others than 'en' are not supported
MW_TIME_ZONE=Europe/Berlin # your time zone
MW_ADMIN_PASS=change_me123 # the password of the 'Admin' account
MW_DB_PASS=change_me123 # the db password of the user 'mediawiki'
# the packages to install (multi-line)
MW_PAGE_PACKAGES="
world.opensemantic.core;
world.opensemantic.base;
world.opensemantic.demo.common;
"
MW_AUTOIMPORT_PAGES=true # if true, packages are installed / updated at start
MW_AUTOBUILD_SITEMAP=false # if true, the sitemap is periodically build (exposes a page for public instances)

MYSQL_HOST_PORT=3307 # the port mysql exposes on the host
MYSQL_ROOT_PASSWORD=change_me123 # the password of the 'root' account

DRAWIO_HOST_PORT=8082 # the port mysql exposes on the host
DRAWIO_SERVER=http://localhost:8081 # the address under which drawio is reachable for the mediawiki container

GRAPHDB_HOST_PORT=9999 # the port blazegraph exposes on the host
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


### Run

```bash
docker compose up
```

Depending on the size of the packages defined in `MW_PAGE_PACKAGES` it will take some time to install them in the background.

You can now login (e. g. at http://localhost:8081/wiki/Main_Page) with user 'Admin' and the `MW_ADMIN_PASS` you set in the .env file.

### Settings

You can add or overwrite mediawiki settings by editing `mediawiki/config/CustomSettings.php`.

You need to re-run `docker compose up` to apply them

#### Public Instance
To make your instance public readable add:
```php
####### Make it public ########
$wgGroupPermissions['*']['read'] = true;
```

#### Addtional content packages
Please note: Content packages defined by MW_PAGE_PACKAGES will be install automatically.
Optional packages listed [here](https://github.com/OpenSemanticLab/PagePackages/blob/main/package_index.txt) can be installed under `<your wiki domain>/wiki/Special:Packages`. Package sources are hosted [here](https://github.com/orgs/OpenSemanticWorld-Packages/repositories).
To add additional optional packages, add 
```php
$wgPageExchangePackageFiles[] = 'packages.json url';
```
e. g. 
```
$wgPageExchangePackageFiles[] = 'https://raw.githubusercontent.com/OpenSemanticWorld-Packages/world.opensemantic.meta.docs/main/packages.json';
```
to `mediawiki/config/CustomSettings.php`

#### Allow additional file uploads
Insecure in public instances!

Example:
```php
$additionalFileExtensions = [ 'py', 'exe' ];
$wgFileExtensions = array_merge( $wgFileExtensions, $additionalFileExtensions );
$wgProhibitedFileExtensions = array_diff( $wgProhibitedFileExtensions, $additionalFileExtensions );
$wgMimeTypeExclusions = array_diff( $wgMimeTypeExclusions, [ 'application/x-msdownload' ]); # for .exe

# allow any upload - insecure in public instances!
# $wgStrictFileExtensions = false;
# $wgCheckFileExtensions = false;
# $wgVerifyMimeType = false;
```

#### Important page content
If your instance is public, make sure to add a privacy policy to `/wiki/Site:Privacy_policy` and legal informations to `/wiki/Site:General_disclaimer`.
You may also create a single page with all necessary informations and point with a redirect from other pages to it: `#REDIRECT [[Site:General_disclaimer]]`

#### Email service
If you don't have an email server yet (optional, but necessary for notification and password resets, etc.), you can use [docker-mailserver](https://github.com/docker-mailserver/docker-mailserver)

#### Optional Extensions
- wfLoadExtension( 'Widgets' );
- wfLoadExtension( 'TwitterTag' ); # Not GDPR conform!
- wfLoadExtension( 'WebDAV' ); # Allows access to uploaded files via WebDAV (e. g. directly with MS Word)
- wfLoadExtension( 'RdfExport' ); # exposes an DCAT catalog at `/api.php?action=catalog&format=json&rdf_format=turtle` and allows OWL ontology export (use only in public instances, requires SPARQL-Store)

#### SMW Store
Currently the default is blazegraph as SPARQL-Store. Since blazegraph is no longer maintained we are transitioning to use Apache Jena Fuseki.
To switch to Fuseki, add the following settings to your CustomSettings.php file:
```php
$smwgSparqlRepositoryConnector = 'fuseki';
$smwgSparqlEndpoint["query"] = 'http://fuseki:3030/ds/sparql';
$smwgSparqlEndpoint["update"] = 'http://fuseki:3030/ds/update';
```

and run the stack with
```bash
docker compose --profile fuseki up
```

Note: A full data rebuild is required to populate the new store.

to run include sparklis SPARQL editor, run
```bash
docker compose --profile fuseki --profile sparklis up
```
or
```bash
COMPOSE_PROFILES=fuseki,sparklis docker compose up
```

If you do not need a SPARQL endpoint, you can switch to SMWElasticStore by reusing the elasticsearch container:
```php
$smwgDefaultStore = 'SMWElasticStore';
$smwgElasticsearchEndpoints = [
    [
        'host' => 'elasticsearch',
        'port' => 9200,
        'scheme' => 'http'
    ]
];
```

Note: Switch store types requires to re-setup the store.
```bash
php /var/www/html/w/extensions/SemanticMediaWiki/maintenance/setupStore.php
php /var/www/html/w/extensions/SemanticMediaWiki/maintenance/rebuildElasticIndex.php
php /var/www/html/w/extensions/SemanticMediaWiki/maintenance/rebuildData.php
```


## Maintenance

### Mediawiki
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

### MySQL
Large mysql binlog files (see https://askubuntu.com/questions/1322041/how-to-solve-increasing-size-of-mysql-binlog-files-problem)

List files
```bash
docker-compose exec db /bin/bash -c 'exec echo "SHOW BINARY LOGS;" | mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
```

Delete files
```bash
docker-compose exec db /bin/bash -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
mysql> PURGE BINARY LOGS TO 'binlog.000123';
```

### Docker
Docker log file size is unlimited in the default settings, see
https://stackoverflow.com/questions/42510002/docker-how-to-clear-the-logs-properly-for-a-docker-container
 
To inspect the file size, run
```bash
du -sh --  /var/lib/docker/containers/*/*-json.log
```

To reset those file (remove all content), run
```bash
truncate -s 0 /var/lib/docker/containers/**/*-json.log
```

To change the setting, adapt `/etc/docker/daemon.json`
```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "1g",
    "max-file": "1"
  }
}
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
This is also required if you change the database passwords after the first run.

## Restore

reset your instance first then import your backup
(get your container name, e. g. `docker-compose-osl-wiki_db_1`, with `docker ps -a`)
```bash
zcat backup/db_backup_<date>.sql.gz | docker exec -i <container> sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
tar -xf backup/file_backup_<date>.tar
chown -R www-data:www-data mediawiki/data
```
