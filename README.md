# OpenSemanticLab Mediawiki Docker Compose

Docker Compose setup for OpenSemanticLab utilizing Semantic MediaWiki functionality. This guide will walk you through the prerequisites, deployment steps, maintenance procedures, and management of database volumes.

## Table of Contents <!-- omit in toc -->

- [OpenSemanticLab Mediawiki Docker Compose](#opensemanticlab-mediawiki-docker-compose)
  - [Prerequisites](#prerequisites)
    - [System](#system)
    - [Clone](#clone)
  - [Deploy](#deploy)
    - [Config](#config)
    - [Run](#run)
    - [Settings](#settings)
      - [Public Instance](#public-instance)
      - [Addtional content packages](#addtional-content-packages)
      - [Allow additional file uploads](#allow-additional-file-uploads)
      - [Important page content](#important-page-content)
      - [Email service](#email-service)
      - [Optional Extensions](#optional-extensions)
  - [Maintenance](#maintenance)
    - [Mediawiki](#mediawiki)
    - [MySQL](#mysql)
      - [List SQL log files](#list-sql-log-files)
      - [Delete SQL log files](#delete-sql-log-files)
    - [Docker Log Files](#docker-log-files)
    - [Persistent Database Volumes](#persistent-database-volumes)
      - [Backup](#backup)
      - [Reset](#reset)
      - [Restore](#restore)

## Prerequisites

### System

Required

- [Docker](https://docs.docker.com/engine/install/)
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)

Optional

- [Nginx](https://docs.nginx.com/nginx/admin-guide/installing-nginx/installing-nginx-open-source/) for SSL Termination
- [Certbot](https://certbot.eff.org/instructions) to create SSL/TSL certs with [Let’s Encrypt](https://letsencrypt.org)

### Clone

Clone this repository on branch `main` for AMD64 architecture

```bash
git clone https://github.com/OpenSemanticLab/osl-mw-docker-compose
```

or clone branch `arm64` for ARM64 architecture

```bash
git clone -b arm64 https://github.com/OpenSemanticLab/osl-mw-docker-compose
```

## Deploy

### Config

Copy .env.example to ``.env``

```bash
cp .env.example .env
```

> Set the config parameters in ``.env``, at least ADMIN_PASS!

### Run

```bash
docker compose up -d ; docker compose logs -f
```

> Depending on the size of the packages defined in `MW_PAGE_PACKAGES` it will take a few minutes to install them in the background.
> When using defaults in ``.env`` you can access OpenSemanticLab on [http://localhost:8081/wiki/Main_Page](http://localhost:8081/wiki/Main_Page) with user ``Admin`` and the password set in the ``.env`` file in previous step.

### Settings

Mediawiki settings can be overwritten by editing [CustomSettings.php](./mediawiki/config/CustomSettings.php). To apply the changes, simply run ``docker compose up -d`` again.

#### Public Instance

To make your instance public readable add the following lines to [CustomSettings.php](./mediawiki/config/CustomSettings.php):

```php
####### Make it public ########
$wgGroupPermissions['*']['read'] = true;
```

#### Addtional content packages

Please note: Content packages defined by MW_PAGE_PACKAGES will be install automatically.
Optional packages listed on [OSL GitHub PagePackages](https://github.com/OpenSemanticLab/PagePackages/blob/main/package_index.txt) and can be installed using `<your wiki domain>/wiki/Special:Packages`. Package sources are hosted on [OSW GitHub repositories](https://github.com/orgs/OpenSemanticWorld-Packages/repositories).

To add additional optional packages, add

```php
$wgPageExchangePackageFiles[] = 'packages.json url';
```

e.g.

```php
$wgPageExchangePackageFiles[] = 'https://raw.githubusercontent.com/OpenSemanticWorld-Packages/world.opensemantic.meta.docs/main/packages.json';
```

to `mediawiki/config/CustomSettings.php`

In order to add multiple packages that are listed in an index file, add it to the config as follows:

```php
$wgPageExchangeFileDirectories[] = 'https://raw.githubusercontent.com/<MyOrg>/PagePackages/refs/heads/main/package_index.txt';
```

In all cases additional packages are now __available__ for installation. Use `<your wiki domain>/wiki/Special:Packages` or the API to actually install them (more information see [Extension:Page_Exchange](https://www.mediawiki.org/wiki/Extension:Page_Exchange)).

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

Large mysql binlog files, [see Askubuntu.](https://askubuntu.com/questions/1322041/how-to-solve-increasing-size-of-mysql-binlog-files-problem)

#### List SQL log files

```bash
docker compose exec db /bin/bash -c 'exec echo "SHOW BINARY LOGS;" | mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
```

#### Delete SQL log files

```bash
docker compose exec db /bin/bash -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
mysql> PURGE BINARY LOGS TO 'binlog.000123';
```

### Docker Log Files

Docker log file size is unlimited in the default settings, [see Stackoverflow.](https://stackoverflow.com/questions/42510002/docker-how-to-clear-the-logs-properly-for-a-docker-container)

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

### Persistent Database Volumes

#### Backup

To create a dump of the database volumes, run

```bash
docker compose exec db /bin/bash -c 'mysqldump --all-databases -uroot -p"$MYSQL_ROOT_PASSWORD" 2>/dev/null | gzip | base64 -w 0' | base64 -d > backup/db_backup_$(date +"%Y%m%d_%H%M%S").sql.gz
```

#### Reset

To reset your instance and clear all data, run:

```bash
docker compose down -v
sudo rm -R mysql/data/* && sudo rm -R blazegraph/data/* && sudo rm -R mediawiki/data/*
docker compose up
```

> This is also required if you change the database passwords after the first run.

#### Restore

reset your instance first then import your backup
(get your container name, e. g. `docker-compose-osl-wiki_db_1`, with `docker ps -a`)

```bash
zcat backup/db_backup_<date>.sql.gz | docker exec -i <container> sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
tar -xf backup/file_backup_<date>.tar
chown -R www-data:www-data mediawiki/data
```
