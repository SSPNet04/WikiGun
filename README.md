# WikiGun

A gun encyclopedia web application built with PHP 8.2, MySQL 8.0, and Bootstrap 5.

---

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (includes Docker Compose)
- Git

---

## Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/SSPNet04/WikiGun.git
cd WikiGun
```

### 2. Start the containers

```bash
docker compose up --build -d
```

This will:
- Build the PHP + Apache app image
- Start a MySQL 8.0 database and automatically run the schema + seed data
- Expose the site on port **8080**

### 3. Open the site

```
http://localhost:8080
```

The admin panel is at:

```
http://localhost:8080/admin/
```

---

## Stopping the site

```bash
docker compose down
```

To also delete the database volume (removes all data):

```bash
docker compose down -v
```

---

## Project Structure

```
WikiGun/
├── admin/              # Admin panel (add/edit/delete all entities)
│   ├── index.php       # Tabbed admin dashboard
│   ├── firearm_form.php
│   └── action.php      # Handles all POST/CUD operations
├── assets/
│   ├── css/style.css
│   ├── js/filter.js
│   └── images/         # Uploaded images stored here
├── docker/
│   └── mysql/init.sql  # Auto-runs on first DB start (schema + seed data)
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── helpers.php
├── sql/
│   ├── schema.sql      # Database schema
│   └── seed.sql        # Sample data
├── index.php           # Firearms list (search, filter, sort)
├── firearm.php         # Firearm detail page
├── ammo.php
├── manufacturer.php
├── attachment.php
├── db.php              # Database connection
├── Dockerfile
└── docker-compose.yml
```

---

## Environment Variables

Configured in `docker-compose.yml`. Change these before deploying to production:

| Variable        | Default       | Description        |
|-----------------|---------------|--------------------|
| `DB_HOST`       | `db`          | MySQL host         |
| `DB_NAME`       | `wikign`      | Database name      |
| `DB_USER`       | `wikign`      | Database user      |
| `DB_PASS`       | `wikign_pass` | Database password  |

---

## Resetting the Database

If you need to re-run the seed data from scratch:

```bash
docker compose down -v
docker compose up --build -d
```

Removing the `db_data` volume forces MySQL to re-initialise from `docker/mysql/init.sql`.

---
## รายชื่อ
นายภูริชญ์ อำโพธิ์ศรี 6710545036

นายทอตะวัน เหลืองอร่ามชัย 6710615128

นายเพชรพงษ์ กันหมุด 6710615169