
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


1. Clone the repository:
   ```bash
   git clone https://github.com/vijay-mca/role_api.git
   cd role_api
## Installation
2. Install dependencies using Composer:
   ```bash
   composer install
   composer dump-autoload
3. Setup the database:
   ```bash
   mysql -u development -p role_app < role_app.sql
You can use the following default admin user to log in and test the API:

- **Email:** `admin@gmail.com`
- **Password:** `Admin@123`