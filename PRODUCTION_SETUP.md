# Production Deployment Guide - BYC Data Center

This guide outlines the steps required to deploy the **BYC Data Center** web application to a live production server.

---

## 1. Prerequisites
- **Web Server**: Apache (with `mod_rewrite` enabled) is recommended. Nginx and IIS are supported (see configuration below).
- **PHP**: PHP 7.4 or higher with the standard `pdo_mysql` extension installed and enabled.
- **Database**: MySQL 5.7+ or MariaDB 10.3+.

---

## 2. Database Initialization
1. Create a fresh MySQL database on your database server (e.g. `byc_church`).
2. Create a database user with full privileges on the new database.
3. Import the initial structure and seed data:
   - Import the [schema.sql](file:///c:/xampp/htdocs/BYC%20DATA%20CENTER/schema.sql) file directly via phpMyAdmin, cPanel, or using the MySQL command line:
     ```bash
     mysql -u your_user -p byc_church < schema.sql
     ```
   - Alternatively, if you deploy the files and visit the application directly, the connection script in `db.php` will automatically attempt to create the database and run the schema imports for you if the credentials are correct.

---

## 3. Configuration Setup
1. Copy the template file [config.example.php](file:///c:/xampp/htdocs/BYC%20DATA%20CENTER/config.example.php) to create `config.php` in the root folder:
   ```bash
   cp config.example.php config.php
   ```
2. Open `config.php` and update the connection details:
   ```php
   return [
       'host' => 'your_production_mysql_host',
       'db'   => 'byc_church',
       'user' => 'your_production_db_user',
       'pass' => 'your_secure_db_password',
       'charset' => 'utf8mb4',
       'port' => '3306'
   ];
   ```
   > [!WARNING]
   > Ensure that `config.php` is excluded from git commits (already configured in `.gitignore`) so you do not leak credentials in public repositories.

---

## 4. URL Rewriting & Extensionless Routes

### Apache Deployment (Default)
No extra configuration is needed. The [.htaccess](file:///c:/xampp/htdocs/BYC%20DATA%20CENTER/.htaccess) file in the root directory handles everything automatically:
- Redirects all standard `*.php` files to their clean, extensionless counterparts (e.g., `members.php` redirects to `members`).
- Internally maps clean URLs to run PHP files.
- Enforces SSL/HTTPS redirection on non-localhost hosts.
- Blocks public HTTP access to critical files (`config.php`, `schema.sql`, `*.log`, `.git`).

### Nginx Deployment
If you are deploying on Nginx, `.htaccess` is ignored. You must configure your server block (typically in `/etc/nginx/sites-available/default`) to map the clean URLs and protect files:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/html;
    index index.php;

    # Secure sensitive files
    location ~* \.(db|sql|log|sh|git|sqlite|example)$ {
        deny all;
    }
    location = /config.php {
        deny all;
    }

    # Hide and map .php extensions
    location / {
        # Check if the requested file exists, then try the file + .php, else index
        try_files $uri $uri.php $uri/ /index.php?$args;
    }

    # Pass PHP scripts to FastCGI server
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # Adjust PHP version as needed
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### IIS Deployment
If you are deploying on IIS, create a `web.config` file in the root folder with the following rules:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <!-- Enforce HTTPS -->
                <rule name="HTTPS Redirect" stopProcessing="true">
                    <match url="(.*)" />
                    <conditions>
                        <add input="{HTTPS}" pattern="off" ignoreCase="true" />
                        <add input="{HTTP_HOST}" pattern="^localhost" negate="true" />
                        <add input="{HTTP_HOST}" pattern="^127\.0\.0\.1" negate="true" />
                    </conditions>
                    <action type="Redirect" url="https://{HTTP_HOST}/{R:1}" redirectType="Permanent" />
                </rule>
                
                <!-- Protect Sensitive Files -->
                <rule name="Block Sensitive Files" stopProcessing="true">
                    <match url="\.(db|sql|log|sh|git|sqlite|example)$|^config\.php$" />
                    <action type="CustomResponse" statusCode="403" statusReason="Forbidden" statusDescription="Access is denied." />
                </rule>

                <!-- Hide .php extension externally -->
                <rule name="RedirectPHP" stopProcessing="true">
                    <match url="^(.*)\.php$" />
                    <conditions>
                        <add input="{THE_REQUEST}" pattern="\.php" />
                    </conditions>
                    <action type="Redirect" url="{R:1}" redirectType="Permanent" />
                </rule>

                <!-- Rewrite clean URLs internally -->
                <rule name="RewritePHP" stopProcessing="true">
                    <match url=".*" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                        <add input="{REQUEST_FILENAME}.php" matchType="IsFile" />
                    </conditions>
                    <action type="Rewrite" url="{R:0}.php" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
```

---

## 5. Security & Maintenance Checklist
1. **Disable Public Error Output**: Verify that `display_errors` is turned off on the production server (our database connection script automatically sets this to `0` when running outside of localhost).
2. **Log Permissions**: Ensure that the web server user has write permissions to the application directory to allow creating the `php_errors.log` file, if errors arise.
3. **Database Backups**: Schedule regular database dumps (`mysqldump`) for MySQL server databases to avoid data loss.
