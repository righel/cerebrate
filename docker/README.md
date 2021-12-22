# How to run #

Dependencies:
  * `docker`. See [https://docs.docker.com/engine/installation](https://docs.docker.com/engine/installation)
  * `docker-compose`. See [docs.docker.com/compose/install](https://docs.docker.com/compose/install/)

In the root directory of this repository run:
```
$ docker-compose up -d
...
cerebrate_php-fpm   | Finished bootstrapping the containers.
cerebrate_php-fpm   | 
cerebrate_php-fpm   | ____ ____ ____ ____ ___  ____ ____ ___ ____ 
cerebrate_php-fpm   | |    |___ |__/ |___ |__] |__/ |__|  |  |___ 
cerebrate_php-fpm   | |___ |___ |  \ |___ |__] |  \ |  |  |  |___ 
cerebrate_php-fpm   | 
cerebrate_php-fpm   | 
cerebrate_php-fpm   | Starting php-fpm...
cerebrate_php-fpm   | [21-Dec-2021 14:32:26] NOTICE: fpm is running, pid 1
cerebrate_php-fpm   | [21-Dec-2021 14:32:26] NOTICE: ready to handle connections
``` 
_This will initialise and start all the containers._
* Once the Cerebrate banner is shown, you can browse the web: [https://localhost:8443](https://localhost:8443)
* Default credentials `admin:Password1234`

To run the containers in the background:
```
$ docker-compose up -d
...
Creating cerebrate_nginx    ... done
Creating cerebrate_mariadb ... done
Creating cerebrate_php-fpm  ... done
```

## Containers and volumes ##

Cerebrate is composed by the following containers:

| Service           | Base Image | External Port | Internal Port (not exposed) |
| ----------------- | ---------- | ------------- | --------------------------- |
| cerebrate_nginx   | nginx      | 8443          | 443                         |
| cerebrate_php-fpm | php-fpm    | -             | 9000                        |
| cerebrate_mariadb | mariadb    | -             | 3306                        |


## Configuration
The recommended way to customize your docker deployment is by introducing a `docker-compose.override.yml` file, you can use `docker-compose.override.dist.yml` as a template.
### PHP
If you want override or add new php settings you can add them to `php-fpm-conf-overrides.conf` or mount different config files if you wish:

```yaml
# docker-compose.override.yml
version: "3"
services:
  php-fpm:
    volumes:
      - ./20-xdebug.conf:/usr/local/etc/php-fpm.d/20-xdebug.conf
```

### SSL
By default [Nginx](https://www.nginx.com/) Dockefile generates a self-signed certificate.
You can override this certificate with:

```yaml
# docker-compose.override.yml
version: "3"
services:
  nginx:
    volumes:
      - ./docker/nginx/ssl:/etc/nginx/ssl
```

## Development
`docker-compose.override.dev.yml` offers a good set of customizations to ease Cerebrate development, such as installing Xdebug and exposing extra ports for internal containers.

Run:

```
$ docker-compose -f docker-compose.yml -f docker-compose.override.dev.yml up --build --force-recreate
...
cerebrate_php-fpm | Starting php-fpm...
cerebrate_php-fpm | [21-Dec-2021 16:53:38] NOTICE: fpm is running, pid 1
cerebrate_php-fpm | [21-Dec-2021 16:53:38] NOTICE: ready to handle connections
```

If you encounter permissions issues when trying to modify the source files from your host, add your user to www-data group:
```bash
sudo usermod -a -G www-data your_user
chgrp your_user .
chmod g+rwxs .
```

### Composer
You can run composer commands inside the container like this:
```
$ docker-compose exec -u www-data php-fpm composer install
```

### Xdebug
`docker-compose.override.dev.yml` adds the `docker-php-ext-xdebug.ini` php configuration file with Xdebug 3 enabled, by default connecting to the host `host.docker.internal` which resolves in the host of the docker engine and on the default `9003` port.

By default Xdebug will debug requests that have a `trigger`, for example `XDEBUG_SESSION_START` as a query string parameter.

To read more about Xdebug triggers:
* https://xdebug.org/docs/all_settings#start_with_request

Alternatively, to debug all requests, you can change the following setting:
```
# docker-php-ext-xdebug.ini
xdebug.start_with_request=yes
``` 

Sample config file for `launch.json` for VSCODE:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/app/": "${workspaceRoot}/app",
            },
        },
    ]
}
```
