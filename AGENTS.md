<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>`

# Agent Rules — Enterprise Support Desk SaaS

You are working on a Laravel 13 + PHP 8.4+ + Livewire SaaS application.

Project:
Enterprise Support Desk SaaS Platform.

Goal:
Build a production-ready, scalable, maintainable, multi-tenant support platform for companies.

Core stack:

- Laravel 13
- PHP 8.4+
- Livewire
- Blade
- Tailwind CSS
- Laravel Reverb
- Laravel Echo
- MySQL or PostgreSQL
- Spatie Laravel Permission
- Spatie Laravel Activitylog
- Pest

Architecture rules:

- Follow Clean Architecture principles.
- Follow SOLID principles.
- Keep controllers thin.
- Keep Livewire components focused on UI interaction only.
- Put business logic in Services.
- Put database access in Repositories.
- Put authorization in Policies/Gates.
- Put validation in Form Requests or Livewire validation rules when appropriate.
- Use Enums for statuses, priorities, user types, ticket types, notification types, mailbox message types, and repeated constants.
- Use Events/Listeners for notifications, activity logging, mailbox messages, and side effects.
- Use Jobs for heavy or async work.
- Use DTOs only when they improve clarity.
- Do not over-engineer simple features.

Multi-tenancy rules:

- Use single database multi-tenancy with company_id.
- Every tenant-owned table must include company_id unless there is a clear reason not to.
- Never leak data between companies.
- Every query involving tenant data must be scoped by company_id.
- Super Admin can access platform-level data.
- Company users can access only their own company data.
- Customers can access only their own requests.

Code quality rules:

- Write senior-level, production-ready code.
- Code must be readable months later.
- Prefer explicit naming over clever abstractions.
- Avoid hidden logic.
- Avoid surprising side effects.
- Keep files focused on one responsibility.
- Keep methods small and intention-revealing.
- Remove unused imports.
- Do not add unused Request parameters.
- Do not add noisy comments that only repeat method names.
- Comments are allowed only when they explain non-obvious business rules.

Error handling rules:

- Do not wrap controller actions in broad try/catch blocks.
- Do not catch Throwable in controllers.
- Do not catch generic Exception in controllers.
- Let unexpected exceptions bubble up during development.
- Only catch specific exception types when there is a meaningful recovery action.
- If an exception is caught, call report($e).
- Do not hide real errors behind generic flash messages.
- Do not suppress stack traces during development.
- Do not expose file paths, stack traces, or line numbers to normal users in production.
- Error logs may be visible only to authorized Super Admin users.

Security rules:

- Use Policies for all sensitive actions.
- Do not trust frontend checks.
- Validate all user input.
- Sanitize file uploads.
- Restrict file types and file sizes through settings.
- Protect uploaded files based on visibility and ownership.
- Never expose another company’s data.
- Never expose internal comments to customers.
- Use authorization checks before every critical action.
- User profile images and uploaded files must be validated, stored safely, and served through authorized access when private.

SaaS roles:
Platform level:

- super_admin

Company level:

- company_admin
- department_manager
- department_deputy
- support_agent
- customer

Main modules:

1. SaaS Foundation
2. Companies
3. Users
4. User Profiles
5. Roles and Permissions
6. Departments
7. Company Settings
8. Branding
9. Theme Settings
10. Language Settings
11. Tickets
12. Ticket Replies
13. Internal Comments
14. Attachments
15. Complaints
16. Inquiries
17. SLA
18. Notifications
19. Realtime Events
20. Internal Mailbox
21. Activity Logs
22. Error Logs
23. Reports

Navigation shell rules:
The authenticated app layout must include a professional SaaS navbar and sidebar shell.

Navbar must include:

- User profile avatar/icon.
- User profile dropdown.
- Admin settings icon when the authenticated user has permission.
- Theme toggle icon.
- Language switcher icon.
- Notifications icon with realtime updates using Laravel Reverb.
- Internal mailbox icon with realtime updates using Laravel Reverb.

User profile dropdown:

- Show user avatar.
- Show user name.
- Show email.
- Link to profile page.
- Link to account settings.
- Logout action.

Admin settings icon:

- Visible only to authorized users.
- Opens admin/system settings area.
- Respect company-level and platform-level permissions.

Theme toggle:

- Support light/dark mode if implemented.
- Store user theme preference.
- Do not hardcode theme behavior in views.
- Prefer a clean ThemePreference service or settings abstraction if needed.

Language switcher:

- Support switching application language.
- Store user language preference.
- Do not hardcode language logic in random views.
- Keep visible UI text translatable where practical.

Notifications rules:

- Use Laravel Reverb for realtime updates.
- Notifications icon must show unread count.
- Clicking the icon opens a compact side panel/dropdown.
- Compact view shows latest 5 notifications.
- Include “View all” button.
- Full notifications page must exist.
- Notification item can link to related entity:
    - new ticket
    - ticket reply
    - complaint update
    - inquiry reply
    - assignment
    - SLA escalation
    - system alert
- Notifications must be scoped by company_id and recipient user.
- Notifications must support read/unread state.
- Notifications must not leak data between tenants.

Internal mailbox rules:
The platform must include an internal mailbox/inbox system separate from normal notifications.

Mailbox icon:

- Visible in navbar.
- Shows unread count.
- Updates realtime using Laravel Reverb.
- Clicking the icon opens a compact mailbox side panel/dropdown.
- Compact mailbox view shows latest 5 messages.
- Include “View all” button.
- Full mailbox page must exist.

Mailbox features:

- Inbox page.
- Message detail page.
- Show sender.
- Show recipient.
- Show subject.
- Show full message body.
- Show related entity if exists.
- Mark as read.
- Mark as unread if needed.
- Archive if needed.
- Link message to system events:
    - new ticket assigned
    - new ticket reply
    - complaint update
    - inquiry response
    - system announcement
    - admin notice
- Clicking a mailbox message should take the user to the related entity when applicable.
- Example: if the message is about a new ticket, clicking it can open the ticket page.
- Example: if the message is about a new reply, clicking it can open the ticket reply context.
- Mailbox messages must be scoped by company_id and recipient user.
- Mailbox must support realtime new-message delivery through Reverb.
- Do not use mailbox as a replacement for activity logs.
- Do not expose private messages across companies.

Permissions:
Use Spatie Laravel Permission.
Permissions must be granular and module-based.

Examples:

- companies.view
- companies.create
- companies.update
- companies.delete
- users.view
- users.create
- users.update
- users.delete
- profiles.view
- profiles.update
- profiles.avatar.update
- departments.view
- departments.create
- departments.update
- departments.delete
- roles.view
- roles.create
- roles.update
- roles.delete
- tickets.view
- tickets.create
- tickets.reply
- tickets.comment
- tickets.assign
- tickets.transfer
- tickets.close
- tickets.reopen
- complaints.view
- complaints.create
- complaints.reply
- complaints.assign
- complaints.close
- inquiries.view
- inquiries.create
- inquiries.reply
- settings.view
- settings.update
- branding.view
- branding.update
- theme.update
- language.update
- notifications.view
- notifications.mark_read
- notifications.delete
- mailbox.view
- mailbox.read
- mailbox.send
- mailbox.archive
- mailbox.delete
- reports.view
- reports.export
- activity_logs.view
- error_logs.view

Seeder rules:

- Create roles and permissions through seeders.
- Assign reasonable default permissions to each role.
- Seeders must be idempotent.
- Do not duplicate roles or permissions when run multiple times.
- Super Admin gets all platform permissions.
- Company Admin gets all company permissions.
- Department Manager gets department-scoped permissions.
- Department Deputy gets limited department management permissions.
- Support Agent gets assigned-ticket permissions.
- Customer gets own-request permissions.
- Include notification and mailbox permissions in seeders.

Livewire rules:

- Use Livewire for interactive dashboards, tables, filters, forms, modals, settings pages, navbar panels, notification panels, and mailbox panels.
- Do not put business rules directly in Livewire components.
- Livewire components may call Services.
- Keep Livewire state clear and minimal.
- Validate Livewire forms clearly.
- Do not perform heavy queries inside render() without pagination or caching.
- Use pagination for large tables.
- Avoid N+1 queries.
- Navbar realtime widgets should be lightweight and efficient.

UI rules:

- Use Blade + Livewire + Tailwind.
- Build modern SaaS dashboard UI.
- Use responsive layouts.
- Use reusable Blade components.
- Support Arabic RTL where needed.
- Keep UI text consistent.
- Use clear empty states.
- Use confirmation dialogs for destructive actions.
- Use status badges and priority badges.
- Use clean forms with validation errors.
- Navbar and sidebar must feel like a professional SaaS product.

Logging rules:
Use Spatie Activitylog for:

- creating tickets
- replying to tickets
- internal comments
- assigning tickets
- transferring tickets
- changing statuses
- uploading attachments
- updating settings
- changing branding
- changing theme/language preferences
- managing users
- managing roles and permissions
- sending internal mailbox messages if important

Ticket rules:
Tickets must support:

- title
- description
- customer
- company
- department
- assigned agent
- status
- priority
- category
- attachments
- replies
- internal comments
- assignment history
- status history
- rating after close

Status rules:
Use Enums for statuses.

Suggested ticket statuses:

- new
- open
- in_progress
- waiting_customer
- waiting_department
- resolved
- closed
- reopened
- cancelled

Attachment rules:

- Support polymorphic attachments if useful.
- Attachments must include company_id.
- Attachments must include uploaded_by_id.
- Attachments must support visibility:
    - public
    - internal
- Public attachments can be visible to customers.
- Internal attachments are staff-only.

Settings rules:
Company settings should allow customization without code changes:

- company name
- logo
- favicon
- colors
- ticket categories
- ticket priorities
- ticket statuses if safe
- file upload rules
- SLA rules
- working hours
- canned responses
- notification preferences
- mailbox preferences
- enable or disable complaints
- enable or disable inquiries

Do not build all features at once.
Work in phases.
Always analyze before implementing.
Always explain created/updated files.
Always mention risks or assumptions.
