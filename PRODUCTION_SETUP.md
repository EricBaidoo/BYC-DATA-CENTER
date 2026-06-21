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
   - Import the [schema.sql](file:///c:/xampp/htdocs/BYC%20DATA%20CENTER/database/schema.sql) file directly via phpMyAdmin, cPanel, or using the MySQL command line:
     ```bash
     mysql -u your_user -p byc_church < database/schema.sql
     ```
   - Alternatively, if you deploy the files and visit the application directly, the database initialization script inside [db.php](file:///c:/xampp/htdocs/BYC%20DATA%20CENTER/includes/db.php) will automatically attempt to create the tables and run the schema imports for you if the connection is successful and the tables do not exist.

---

## 3. Configuration Setup
1. Copy the template environment file [.env.example](file:///c:/xampp/htdocs/BYC%20DATA%20CENTER/.env.example) to create `.env` in the root folder:
   ```bash
   cp .env.example .env
   ```
2. Open `.env` and update the connection and application details:
   ```env
   # Database configuration
   DB_HOST=your_production_mysql_host
   DB_PORT=3306
   DB_NAME=byc_church
   DB_USER=your_production_db_user
   DB_PASS=your_secure_db_password
   DB_CHARSET=utf8mb4

   # Application Configuration
   APP_ENV=production
   APP_URL=https://yourdomain.com

   # Default Admin Seed Configuration (Runs if users table is empty)
   ADMIN_USER=admin
   ADMIN_PASS=your_secure_admin_password
   ADMIN_NAME="BYC Administrator"
   ```
   > [!WARNING]
   > Ensure that `.env` is excluded from git commits (already configured in `.gitignore`) so you do not leak credentials in public repositories.

---

## 4. URL Rewriting & Extensionless Routes

### Apache Deployment (Default)
No extra configuration is needed. The [.htaccess](file:///c:/xampp/htdocs/BYC%20DATA%20CENTER/.htaccess) file in the root directory handles everything automatically:
- Redirects all standard `*.php` files to their clean, extensionless counterparts (e.g., `members.php` redirects to `members`).
- Internally maps clean URLs to run PHP files.
- Enforces SSL/HTTPS redirection on non-localhost hosts.
- Blocks public HTTP access to critical files (`.env`, `.env.example`, `database/schema.sql`, `*.log`, `.git`, and files in `includes/` or `database/` folders).

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

    # Secure sensitive files and system folders
    location ~* \.(db|sql|log|sh|git|sqlite|example|env)$ {
        deny all;
    }
    location ~ ^/(includes|database)/ {
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
                
                <!-- Protect Sensitive Files & Folders -->
                <rule name="Block Sensitive Files" stopProcessing="true">
                    <match url="\.(db|sql|log|sh|git|sqlite|example|env)$|^(includes|database)/" />
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
