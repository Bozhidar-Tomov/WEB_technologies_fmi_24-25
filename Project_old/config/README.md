# PHP MongoDB Connection Setup (XAMPP Apache Server)

This project provides a PHP library for managing MongoDB connections using the XAMPP Apache Server environment.

## Setup process

1. Place the DLL file into the ext directory of your XAMPP PHP installation. Example path:

    ```plaintext
    C:\xampp\php\ext\php_mongodb.dll
    ```

2. Enable the extension.
    Open the `php.ini` located in the XAMPP PHP directory (e.g., `C:\xampp\php\php.ini`).
    Add the following line at the end or in the `Dynamic Extensions` section:

    ```plaintext
    extension=php_mongodb.dll
    ```

3. Restart the Apache server.
   You can do this from the XAMPP Control Panel.
