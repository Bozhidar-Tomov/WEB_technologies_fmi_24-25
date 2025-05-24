# Setup

Create file named `mongo_uri.php` in the same directory as this file with the following content:

```php
    <?php
    $uri = "mongodb+srv://<USER>:<PASSWORD>@cluster0.n0aysat.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0";
```

Replace `<USER>` and `<PASSWORD>` with your actual MongoDB Atlas credentials.
