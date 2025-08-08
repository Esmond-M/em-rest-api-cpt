# EM REST API CPT Plugin

A WordPress plugin to create and populate a custom post type using the REST API.  
Easily send data to your site and manage it as "API Data" posts.

**Project:** [GitHub Repository](https://github.com/Esmond-M/em-rest-api-cpt)  
**Author:** [esmondmccain.com](https://esmondmccain.com/)

---

## Features

- Registers a custom post type: **API Data**
- Accepts data via REST API endpoint
- Secures data submission using WordPress user authentication
- Simple JSON format for posting data
- Easily extendable for custom fields or workflows

---

## Installation

1. [Download the latest release](https://github.com/Esmond-M/em-rest-api-cpt/blob/main/build/em-rest-api-cpt.zip)
2. Upload the `em-rest-api-cpt.zip` file to your `/wp-content/plugins/` directory.
3. Extract the zip file. The plugin folder should be named `em-rest-api-cpt`.
4. Activate the plugin via **Plugins > Installed Plugins** in your WordPress admin.
5. You will now see a new post type called **API Data** in your dashboard.

---

## Usage

- **REST API Endpoint:**  
  ```
  https://yoursite.domain/wp-json/esmond-api/v1/receive
  ```

- **JSON Format for POST requests:**  
  ```json
  {
    "name": "",
    "password": "",
    "title": "",
    "body": ""
  }
  ```
  - Use a valid WordPress username and password for authentication.
  - `title` and `body` will be used for the new API Data post.

- **Example cURL request:**
  ```sh
  curl -X POST https://yoursite.domain/wp-json/esmond-api/v1/receive \
    -H "Content-Type: application/json" \
    -d '{"name":"yourusername","password":"yourpassword","title":"Post Title","body":"Post Content"}'
  ```

---

## Extending

- Add custom fields or meta boxes to the `API Data` post type as needed.
- Modify the endpoint handler for additional validation or workflows.

---

## Support

For issues, suggestions, or contributions, please use the [GitHub Issues](https://github.com/Esmond-M/em-rest-api-cpt/issues) page.

---

*Maintained by [Esmond McCain](https://esmondmccain.com/).
