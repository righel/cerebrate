# Upgrade Cerebrate

To upgrade a local cerebrate installation, simply pull the new code from the remote `main` branch:

```bash
sudo -u www-data git -C /var/www/cerebrate/app/ pull origin main
```

If you need to use a proxy, you can pass them to the command like this:

```bash
https_proxy=http://proxy.local:8080 sudo -Eu www-data git -C /var/www/cerebrate/app/ pull origin main
```

To upgrade the database, login to the webinterface as administrator and call
http://cerebrate.local:8000/instance/migrationIndex
Also available from the menu in the interface as "Database migration".
Run all available upgrades.
