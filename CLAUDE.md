# SWWMA API - Backend Project Context

## Project Overview
This is the backend API for the Constituency Development Hub application, built with PHP and likely using a Laravel or similar framework. It serves the frontend Next.js application with RESTful API endpoints for data management.

## Key Technologies
- **Language**: PHP
- **Framework**: Likely Laravel or custom MVC (based on composer.json and structure)
- **Database**: MySQL (evidenced by SQL dump in frontend)
- **API Style**: RESTful
- **Authentication**: JWT or session-based
- **File System**: Local storage for uploads
- **Email**: PHPMailer or similar (worker.php suggests background processing)
- **CLI Tools**: Phinx for database migrations

## Project Structure
```
swwma-api/
├── app/                    # Application code (controllers, models, etc.)
├── public/                 # Publicly accessible assets
├── src/                    # Source code
├── database/               # Database migrations, seeds
├── postman/                # API collection for testing
├── requests/               # Request validation classes
├── templates/              # Email or document templates
├── vendor/                 # Composer dependencies
├── .env                    # Environment variables
├── .env.example            # Environment variable template
├── composer.json           # PHP dependencies
├── composer.lock           # Locked dependency versions
├── phinx.php               # Database migration configuration
├── worker.php              # Background job processor
├── generate-templates.php  # Template generation script
├── create-vhost.bat        # Virtual host creation script (Windows)
├── Dockerfile              # Containerization configuration
├── LICENSE                 # MIT License
└── ca.pem                  # SSL certificate authority
```

## Important Files
- **composer.json**: PHP dependencies and autoloading
- **phinx.php**: Database migration configuration
- **worker.php**: Background processing for queues/jobs
- **generate-templates.php**: Script for generating email/document templates
- **database/**: Contains migrations and seeders
- **postman/**: API documentation and testing collection
- **.env**: Environment configuration (database credentials, API keys, etc.)

## Development Commands
- **Composer**: `composer install` - Install PHP dependencies
- **Phinx**: `php vendor/bin/phinx migrate` - Run database migrations
- **Worker**: `php worker.php` - Start background job processor
- **Server**: Likely uses PHP built-in server or Apache/Nginx

## Environment Variables
Refer to `.env.example` for required environment variables. Key variables likely include:
- Database connection (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`)
- JWT secrets for authentication
- Email configuration (SMTP host, port, credentials)
- File upload paths and limits
- API base URLs
- Encryption keys

## API Structure
Based on the frontend's `api_specifications.json` and typical patterns:
- **Authentication**: `/api/auth/login`, `/api/auth/register`, `/api/auth/logout`
- **Users**: `/api/users` (CRUD operations)
- **Announcements**: `/api/announcements`
- **Blog/Posts**: `/api/blog`
- **Events**: `/api/events`
- **Projects**: `/api/projects`
- **Gallery/Media**: `/api/gallery`
- **Dashboard-specific endpoints** for different user roles
- **Reports**: `/api/reports` (PDF generation, analytics)
- **Settings**: `/api/settings` (system configuration)

## Database
- The frontend contains `const_dev_db (7).sql` which likely matches the backend schema
- Tables probably include: users, roles, permissions, announcements, blog_posts, events, projects, gallery_items, settings, etc.
- Uses Phinx for migration management

## Security Features
- Input validation and sanitization
- Prepared statements to prevent SQL injection
- CSRF protection
- Rate limiting on auth endpoints
- Secure file upload validation
- Environment-based configuration

## Integration with Frontend
- API base URL configured in frontend via `NEXT_PUBLIC_APP_URL` or similar
- JSON responses standardized
- Authentication via JWT tokens in Authorization header or cookies
- CORS configured to allow frontend domain
- Image serving through public/storage or similar

## Background Processing
- `worker.php` handles queued jobs
- Likely used for: email sending, report generation, image processing, data synchronization
- Triggered by frontend actions or scheduled tasks

## Deployment
- Dockerfile present for containerization
- Can run on traditional LAMP stack
- Requires web server (Apache/Nginx) with PHP-FPM
- Database requires MySQL 5.7+ or MariaDB equivalent