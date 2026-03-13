# TEST - Eventer
###### v2026.03.13.1

Eventer is a simple Symfony-based web application where users can create, edit and delete events. Just for test, not production.

---

## Requirements

- Composer
- PHP 8.4
- SQL

---

## Documentation

### API endpoints

- User info
   - `GET /api/me` (MeController): returns current user’s email and roles; used by the frontend right after login.
- Events (all require auth and are per-user)
   - `GET /api/events` – list current user’s events, ordered by occurrence time.
   - `POST /api/events` – create event (`title`, `occursAt` in ISO or `YYYY-MM-DDTHH:MM`, optional `description`).
   - `PATCH /api/events/{id}` – update description only of an existing event, owned by current user.
   - `DELETE /api/events/{id}` – delete an event owned by current user.
- Helpdesk (user side)
   - `POST /api/helpdesk/messages`
      - Creates or reuses an open Conversation for the current user.
      - Stores the user message and generates a simple bot reply:
      - If message contains “reset” and “password”: explains how to reset password and how to ask for agent.
      - If it mentions “event”: explains where to manage events.
      - Otherwise: generic assistant answer, with an option to ask for an agent.
      - If the message contains "agent" (case-insensitive), conversation status is set to `waiting_agent` and bot reply explains transfer to human agent.
      - Returns the full conversation (id, user, status, createdAt, messages).
- Helpdesk (agent side – requires ROLE_HELPDESK)
   - `GET /api/helpdesk/conversations` – list all conversations, newest first, with user, status and messages.
   - `POST /api/helpdesk/conversations/{id}/reply` – add an agent reply message and reopen status to open, returning updated conversation.

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

### How to update?

```shell
cd backend;
php bin/console cache:clear;
composer dump-autoload -o;
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