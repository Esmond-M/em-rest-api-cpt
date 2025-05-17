Project: [https://github.com/Esmond-M/em-rest-api-cpt](https://github.com/Esmond-M/em-rest-api-cpt)<br>
Author: [esmondmccain.com](https://esmondmccain.com/)

## Features
Use REST API to populate custom post type. 
 ## Installation

1. Download the latest version from [https://github.com/Esmond-M/em-rest-api-cpt/blob/main/build/em-rest-api-cpt.zip](https://github.com/Esmond-M/em-rest-api-cpt/blob/main/build/em-rest-api-cpt.zip).
2. Upload `em-rest-api-cpt` zip to the `/wp-content/plugins/` directory.
3. Extract zip folder. Folder name of plugin should be "em-rest-api-cpt".
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. New Post type "API Data" will now be available.
6. Endpoint is ``https://yoursite.domain/wp-json/esmond-api/v1/receive``
7. JSON format for posting `` {
"name":"",
"password":"",
"title":"",
"body":""
} `` .
Use the login from user on your WordPress installation.
