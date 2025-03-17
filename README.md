# Food Craft Club

An exclusive craft food marketplace platform built with PHP 8.1+ and modern web technologies.

## Project Overview

Food Craft Club is a members-only platform that connects craft food sellers with exclusive clients. The platform features:

- Role-based access control (Admin, Seller, Client)
- Secure member authentication and authorization
- Product catalog management
- Order processing system
- Integrated chat system
- Membership fee management
- Real-time notifications

## Technical Stack

- **Backend**: PHP 8.1+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3 (Bootstrap), JavaScript
- **Dependencies**: Composer
- **Additional Features**: JWT Authentication, Real-time Chat

## Project Structure

```
foodcraftclub/
├── config/               # Configuration files
├── database/            # Database migrations and seeds
├── public/              # Public assets and entry point
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── index.php       # Entry point
├── src/                 # Source code
│   ├── Core/           # Framework core components
│   ├── Controllers/    # Application controllers
│   ├── Models/         # Database models
│   └── Views/          # View templates
├── .env.example        # Environment configuration template
├── composer.json       # Composer dependencies
└── README.md           # Project documentation
```

## Setup Instructions

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env` and configure your environment variables:
   ```bash
   copy .env.example .env
   ```
4. Set up your database and update the `.env` file with your database credentials
5. Run database migrations:
   ```bash
   php database/migrate.php
   ```
6. Start the development server:
   ```bash
   php -S localhost:8080 -t public
   ```

## User Roles

### Administrator
- Manage user accounts and roles
- Configure membership fees
- Access all platform features
- Monitor orders and communications

### Seller
- Manage product catalog
- Process orders
- Configure delivery areas
- Set payment methods
- Chat with clients

### Client
- Browse product catalog
- Place orders
- Pay membership fees
- Chat with sellers

## Security Features

- Role-based access control
- Password hashing with bcrypt
- CSRF protection
- Secure session management
- Input sanitization
- SQL injection prevention

## Contributing

This is a private project. Contributions are managed internally.

## License

Proprietary. All rights reserved.
