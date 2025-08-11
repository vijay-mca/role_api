
# Role-Based Access Control API

A PHP REST API for role and user management with JWT authentication, built with PHP 8.2 and MariaDB.

---

## Requirements

- PHP 8.2.12 or higher
- MariaDB 10.4.32 (compatible with MySQL)
- Composer (for dependency management)
- Web server (Apache, Nginx, or built-in PHP server)
- API PORT: 80
- Database Port: 3306

---

## Windows & XAMPP Setup

- **XAMPP** is recommended for easy setup on Windows.  
- Download and install XAMPP from [https://www.apachefriends.org/index.html](https://www.apachefriends.org/index.html).  
- Make sure Apache and MySQL services are running via XAMPP Control Panel.  
- PHP version in XAMPP should be 8.2.12 or higher (you may need to update XAMPP if older).  
- Place the project folder inside `xampp/htdocs` to serve it via Apache.  
- Configure `.env` accordingly to connect to MySQL (usually user: `root`, password: empty by default).

### Installing Composer on Windows

1. Download the Composer-Setup.exe installer from [https://getcomposer.org/download/](https://getcomposer.org/download/).  
2. Run the installer and follow the setup wizard:  
   - It will detect your PHP installation automatically (ensure PHP is installed or use XAMPP’s PHP).  
   - Select to add Composer to your system PATH for easy command-line use.  
3. After installation, open a new Command Prompt or PowerShell window and verify installation by running:

   ```bash
   composer --version
### Project Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/vijay-mca/role_api.git
   cd role_api
## Installation
2. Install dependencies using Composer:
   ```bash
   composer install
   composer dump-autoload
### Setup the database:
3. Create the database role_app in your MariaDB or MySQL server.
   ```bash
   mysql -u development -p role_app < role_app.sql
You can use the following default admin user to log in and test the API:

- **Email:** `admin@gmail.com`
- **Password:** `Admin@123`