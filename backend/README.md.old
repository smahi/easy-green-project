# Backend Administration Commands

This application uses the Filament Shield plugin to manage roles and permissions, specifically including a `superuser` role with unrestricted access. To make managing administrative users easier, we provide two custom Artisan commands.

## 1. Create a Superuser

This command allows you to create a new user account and automatically assign it the `superuser` role.

**Interactive Mode:**
Simply run the command and it will prompt you for the necessary information.
```bash
php artisan user:create-superuser
```

**Non-Interactive Mode:**
You can pass the details as options if you want to bypass the prompts (e.g., for automation):
```bash
php artisan user:create-superuser --name="Admin" --email="admin@example.com" --password="password123"
```

## 2. Update Account Data

This command allows you to update an existing user's information (name, email, and optionally password).

**Interactive Mode:**
You can start the command interactively, and it will ask you which user you want to edit:
```bash
php artisan user:update
```

**Semi-Interactive Mode:**
You can provide the email of the user you want to update directly as an argument. The command will then prompt you for the remaining fields:
```bash
php artisan user:update admin@example.com
```