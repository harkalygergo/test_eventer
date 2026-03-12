# TEST - Eventer
###### v2026.03.12.4

Eventer is a simple Symfony-based web application where users can create, edit and delete events. Just for test, not production.

---

## Requirements

- Composer
- PHP 8.4
- SQL

---

## Documentation

### How to install?

```shell
# clone or download repository
git clone [...]
# go to webapp backend directory
cd backend
# install dependencies
composer install --optimize-autoloader
# copy and modify dotenv to configure environment
cp .env.dist .env; nano .env
# run migrations
php bin/console doctrine:migrations:migrate
# create symlink for frontend in public directory
cd public; ln -s ../../frontend;
```

### How to test?

```shell
# you can create demo users
php bin/console app:create-demo-users
# you can run on localhost:
symfony serve # or: php -S localhost:8000 -t public
```

---

## Task

### Summary

Create a system with a separate backend and frontend, which communicate through the HTTP layer. The backend exposes a RESTful API, which is accessed by the frontend to perform various tasks.

### Architecture

 - For frontend, feature-based structure is preferred.
 - For backend, service layer structure is preferred.

### Functionality

1. The purpose of the system is to manage events for users.
    - Events are described by their:
      - Title - mandatory
      - Occurrence (date & time) - mandatory
      - Description - optional
    - Users can:
      - Create events,
      - List their events,
      - Update existing events’ description,
      - Delete events
   - The system can manage multiple users and their associated events.
1. The core functionalities of the system must be protected from unauthorized access.
   - Login page without possibility for registration
   - Users need to have the option to ask for a password reset
   - Bonus: MFA is present.
1. Authentication to the backend API must adhere to a modern security standard (Oauth 2.0, JWTs or Basic when using TLS)
1. Data must be stored in a secure environment with appropriate access controls and encryption.
   - Bonus: Protection from various cyberattacks, OWASP Top 10 risks are addressed.
1. The system has a help desk which understands customers' free word questions, answers them and provides transfer to a human if requested.
   - Users with a specific privilege level (helpdesk agent) can access a separate interface, on which they can see previous chats, and answer new incoming ones.
   - Bonus: the system is also available on a voice basis (phone or web voice)