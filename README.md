# Polaris

Polaris is a lightweight, modular **PHP framework** designed for structured and scalable web applications. It follows the **Model-View-Controller (MVC)** pattern and provides built-in support for dynamic routing, database interaction, and template rendering.

Polaris is optimized for **fast development**, ensuring clean separation of concerns and simplifying common backend operations. It is particularly suited for **projects requiring efficient AJAX handling, modular routing, and security-first design**.

## Key Features

- **Dynamic Routing**: Polaris handles URL routing through a centralized router.
- **MVC Architecture**: Implements a structured separation between models, views, and controllers.
- **Database Management**: Uses a database abstraction layer for MySQL.
- **AJAX Integration**: Supports seamless client-server communication via JSON responses.
- **Template Engine**: Provides dynamic HTML rendering using embedded templates.
- **Logging System**: Uses Monolog for application logging.

## Technologies Used

- **PHP 8.2+**
- **Apache with .htaccess Support**
- **MySQL / MariaDB**
- **JavaScript (jQuery, AJAX)**
- **Composer**

## Project Structure

```markdown
📂 src/
 ├── kernel.php         # Polaris request handler
 ├── polaris.php        # Framework initialization
 ├── .htaccess          # Apache configuration for routing
 ├── composer.json
 ├── config.ini         # Database configuration
 ├── 📂 app/            # Polaris Core Modules
 │   ├── Router.php     # Routing handler
 │   ├── Model.php      # Database abstraction layer
 │   ├── ViewEngine.php # Template compiler
 │   ├── sdk.php        # Utility functions (debugging, redirection)
 │   ├── app.php        # Global template helpers
 │   ├── labels.json    # Application labels (multi-language)
 │   └── Logger.php     # Monolog integration
 │
 ├── 📂 assets/         # Static files (images, icons, fonts)
 ├── 📂 css/            # Styling with CSS
 │
 ├── 📂 init/           # Database setup
 │   └── 📂 polaris/    # Polaris core settings
 │       ├── init.php   # Framework initialization script
 │       └── init.sql   # SQL scripts
 │
 ├── 📂 js/             # Client-side scripts (AJAX, UI interactions)
 ├── 📂 models/         # Database models
 ├── 📂 pages/          # Views and controllers per section
 │   └── Example/
 │       ├── Example.html
 │       ├── Example.js
 │       └── Example.php
 │
 ├── 📂 vendor/
 ├── .gitignore
 └── README.md
```

## Installation & Configuration

### 1. Clone the Repository
```bash
git clone git@github.com:DCV05/Polaris.git
cd Polaris
```

### 2. Configure Apache
Modify Apache’s configuration (`apache2.conf` on Debian/Ubuntu) to allow `.htaccess`:

```apache
<Directory /var/www/html/project>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Restart Apache:
```bash
sudo systemctl restart apache2
```

### 3. Set Up Database
Update `config.ini` with database credentials:

```ini
[mysql]
db_server   = localhost
db_user     = root
db_password = root
db_sys      = polaris
db_project  = polaris_project
```

### 3. Set Up Composer
Install the Composer dependencies:

```bash
composer install
```

---

## Creating a new page

The Polaris framework manages page routing dynamically using the `polaris_pages` table in the `polaris` database. This table stores the necessary information to map URLs to their corresponding files.

#### Table Schema

| Column       | Type            | Attributes       | Description |
|-------------|----------------|-----------------|-------------|
| page_id     | INT(11)        | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for each page |
| url         | VARCHAR(100)   | NOT NULL        | URL path for the page |
| redirect    | VARCHAR(255)   | NOT NULL        | Redirect URL (if applicable) |
| page_title  | VARCHAR(100)   | NOT NULL        | Display title of the page |
| file        | VARCHAR(100)   | NOT NULL        | Path to the page file in the `pages/` directory |
| title_seo   | VARCHAR(100)   | NOT NULL        | SEO-friendly title |

#### Example Entry

| url       | redirect | page_title | file         | title_seo |
|-----------|----------|------------|-------------|------------|
| /debug    |          | Debug      | Debug/Debug | Debug      |

To insert this entry into the database, run the following SQL query:

```sql
INSERT INTO `polaris_pages` (`url`, `redirect`, `page_title`, `file`, `title_seo`) VALUES
('/debug', '', 'Debug', 'Debug/Debug', 'Debug');
```

### Create the Necessary Files
Once the entry is created in the database, ensure the corresponding files exist within the `pages/` directory:

```bash
mkdir -p src/pages/Debug
cd src/pages/Debug
```

Then create the required files:

```bash
touch Debug.html Debug.js Debug.php
```

### Create the Controller
Each page requires a dedicated controller. Below is an example of a basic controller:

```php
<?php

class DebugController
{
    public function index(): void
    {
        return;
    }
}

?>
```

---

## Polaris View Engine
Polaris includes a **View Engine** that allows dynamic rendering of HTML templates using embedded placeholders and function calls.

### View Engine Features
- **Dynamic Variable Injection**: Use [[ variable_name ]] to insert dynamic content, including page-specific metadata, database values, and system configurations.
- **Function Execution**: Call general functions using [[ func | &function_name ]]. Functions prefixed with & are global functions defined in app.php.
- **Controller Method Execution**: Call methods from the current page’s controller using [[ func | method_name ]] (without &).
- **Asset Path Resolution**: Dynamically load scripts, stylesheets, or assets using [[ polaris.actual_dir ]]/[[ polaris.page.page_title ]].js.
- **Modular Interface Loading**: Dynamically include UI components defined in app.php with [[ func | &panel_interface ]].

### Example View Template

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <title>[[ polaris.page.title_seo ]]</title>
  [[ func | &headers ]]
</head>

<body>

  [[ func | &panel_interface ]]
  
  <div class="page-content">
    <div class="mx-auto w-full p-2 md:p-4">
      [[ func | activity_details ]]

      [[ func | attendance_list_link ]]

      <hr class="my-12">

      [[ func | table_participants ]]
    </div>
  </div>

  <script src="[[ polaris.actual_dir ]]/[[ polaris.page.page_title ]].js"></script>
</body>

</html>
```

---

## Contribution & Contact
For feature requests, bug reports, or contributions, open an issue on GitHub or contact **daniel.correa@kodalogic.com**.
