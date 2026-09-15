# Premia Quiz & Survey AI

## Stack

- **Language / Runtime**: PHP 8.0+, JavaScript (ES2020+)
- **Framework**: WordPress Plugin API
- **Admin UI**: React 18 via @wordpress/components, built with @wordpress/scripts
- **Frontend Quiz UI**: Vanilla JavaScript (no framework, lightweight for quiz takers)
- **Database**: Custom tables via dbDelta() with versioned migrations
- **AI Integration**: OpenAI PHP SDK
- **Package manager**: npm

## Build approach

**Tracer Bullet**, vertical end to end slices, thin but complete through every layer

## Commands

```bash
# Install dependencies
npm install

# Build admin assets (React)
npm run build

# Start dev server (hot reload)
npm run start

# Lint PHP
./vendor/bin/phpcs

# Lint JavaScript
npm run lint:js

# Lint CSS
npm run lint:css

# Format JavaScript and CSS
npm run format

# Run PHP tests
./vendor/bin/phpunit

# Run JavaScript tests
npm test

# Run JavaScript tests in watch mode
npm run test:watch

# Run JavaScript tests with coverage
npm run test:coverage

# Update WordPress packages
npm run packages-update
```

## Specs

Stored in `docs/specs/`. Format: `docs/specs/NNNN-title.md`.

## Rules

- Follow WordPress PHP coding standards (PHPCS with WordPress ruleset)
- Use nonces for CSRF protection, capability checks for authorization
- Sanitize input early, escape output late
- Use $wpdb->prepare() for all SQL queries, never string concatenation
- Single Responsibility: each class has one reason to change
- Dependency Inversion: wire dependencies via constructor injection, no service locators
- Classes stay under 200 lines, extract responsibilities when they grow
- Prefer composition over inheritance beyond one level deep
- Name classes after what they do (UserRepository, not AbstractBaseUserImpl)
- Register activation/deactivation hooks at top level, not inside other hooks
- Use Settings API for options: register_setting(), add_settings_section(), add_settings_field()

## Agent skills

**Workflow skills** (in `.agents/skills/`):
- [architect](.agents/skills/architect/): jsmastery-pro/skills, architecture decisions and specs
- [audit](.agents/skills/audit/): jsmastery-pro/skills, context bootstrapper for AGENTS.md
- [check](.agents/skills/check/): jsmastery-pro/skills, verification and code review
- [debug](.agents/skills/debug/): jsmastery-pro/skills, bug finding and fixing
- [develop](.agents/skills/develop/): jsmastery-pro/skills, feature building from specs
- [document](.agents/skills/document/): jsmastery-pro/skills, PR and changelog writing
- [scope](.agents/skills/scope/): jsmastery-pro/skills, feature scoping and planning
- [sync](.agents/skills/sync/): jsmastery-pro/skills, keeping context files current
- [test](.agents/skills/test/): jsmastery-pro/skills, test suite writing

**WordPress skills** (in `.claude/skills/`):
- [wp-plugin-development](.claude/skills/wp-plugin-development/): WordPress/agent-skills, plugin architecture, hooks, security, Settings API
- [wp-block-development](.claude/skills/wp-block-development/): WordPress/agent-skills, Gutenberg blocks: block.json, attributes, rendering
- [wp-rest-api](.claude/skills/wp-rest-api/): WordPress/agent-skills, REST API routes, endpoints, schema, authentication
- [wp-performance](.claude/skills/wp-performance/): WordPress/agent-skills, profiling, caching, database optimization
- [wp-phpstan](.claude/skills/wp-phpstan/): WordPress/agent-skills, PHPStan static analysis for WordPress

## Git

- integration: on
- branch prefix: feat/
- commit: per-milestone

## Context files

<!-- Nested AGENTS.md files are listed here as they are created -->

_Drafted by /audit from the repo, worth a quick human pass. Edit freely: once a line stops matching this draft, later runs treat it as curated and will flag rather than overwrite it._
