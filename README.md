Google photo Bot
================

The script for streamline folders of photos and videos from Google photo on Google Drive. The script automatically creates folders with the year and months and moves there files with photos and videos according to the creation date in the file attributes.

####Usage:
1. composer install
2. Go to https://console.developers.google.com/ and create the project
3. Go to credentials for created project and create OAuth 2.0 identifier and download client_secret.json file
4. Move downloaded client_secret.json into app/config/credentials/client_secret.json
5. On the tab access window OAuth Access Request Window add ../auth/drive permission.
6. Run bin/console google:bot:start