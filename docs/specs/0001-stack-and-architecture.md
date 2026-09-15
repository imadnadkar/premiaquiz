# 0001. Stack and architecture

**Date**: 2026-09-15
**Status**: Accepted

## Summary

This spec defines the technology stack and architecture for the Premia Quiz & Survey AI WordPress plugin. The plugin will use PHP 8.0+ with a layered monolith architecture, React for the admin UI via @wordpress/scripts, vanilla JavaScript for the frontend quiz interface, custom database tables with dbDelta() for quiz data, and OpenAI integration via PHP SDK. The stack is designed for standard WordPress hosting environments with high performance and security standards.

## Context

Building a WordPress quiz and survey plugin requires careful consideration of the WordPress ecosystem constraints. The plugin must work on shared hosting, follow WordPress coding standards, integrate with the block editor, and maintain security while providing modern React-based admin UI. The target audience is educators and course creators who need reliable, fast quizzes with AI-powered features.

Key forces at play:
- WordPress ecosystem: must follow WordPress plugin conventions, use WordPress APIs, and work within WordPress hosting constraints
- Performance requirements: handle 100+ concurrent quiz takers, optimize database queries
- Security requirements: WordPress security standards (nonces, capability checks, sanitization, escaping)
- Modern UI: React-based admin interface similar to Gutenberg for administrators
- Frontend performance: quiz takers need fast loading, minimal JavaScript for the quiz interface
- AI integration: OpenAI GPT-4 for question generation and grading
- Time constraint: 2-4 week MVP timeline

Two distinct UI surfaces:
1. **Admin UI** (quiz builder): React via @wordpress/scripts for WordPress administrators creating quizzes
2. **Frontend Quiz UI** (quiz display): Vanilla JavaScript for website visitors taking quizzes, optimized for performance

## Requirements

**User stories**:
- As a WordPress administrator, I want to install a quiz plugin that works on standard WordPress hosting so that I don't need special server configuration
- As a developer, I want a clean architecture that follows WordPress conventions so that I can maintain and extend the plugin
- As a user, I want fast quiz loading and submission so that the experience is smooth

**Acceptance criteria** (the contract, each criterion is IDed and independently checkable):
- **AC-1**: Plugin installs and activates on WordPress 6.0+ with PHP 8.0+
- **AC-2**: Admin UI loads in under 2 seconds on standard WordPress hosting
- **AC-3**: Quiz submission handles 100+ concurrent users without performance degradation
- **AC-4**: All database operations use prepared statements and follow WordPress security standards
- **AC-5**: React admin UI builds successfully with @wordpress/scripts and loads in WordPress admin
- **AC-6**: Custom database tables are created/updated via dbDelta() with versioned migrations
- **AC-7**: Plugin follows WordPress coding standards and passes PHPCS checks

## Options considered

### Option 1: Traditional WordPress plugin (PHP only)

Pure PHP plugin with jQuery-based admin interface, using WordPress post types for data storage.

**Pros**:
- Simplest to build, no build tools required
- Maximum compatibility with WordPress hosting
- No JavaScript build step

**Cons**:
- Limited UI capabilities, harder to create modern interfaces
- Post types less performant for quiz data with relationships
- jQuery code harder to maintain at scale

### Option 2: React admin with custom tables and vanilla JS frontend (Recommended)

PHP backend with React admin UI built via @wordpress/scripts, custom database tables for quiz data, and vanilla JavaScript for frontend quiz display.

**Pros**:
- Modern, maintainable React admin interface
- Lightweight frontend quiz UI with no dependencies
- Custom tables optimized for quiz data relationships and performance
- Follows WordPress block editor patterns
- Good balance of performance and compatibility

**Cons**:
- Requires JavaScript build step for admin UI
- Two different JavaScript approaches (React admin, vanilla frontend)
- Must externalize WordPress packages properly

### Option 3: Full React application with WordPress as backend

Complete React SPA for admin, using WordPress REST API exclusively.

**Pros**:
- Most modern approach, best UI/UX potential
- Clear separation of concerns

**Cons**:
- Overkill for a WordPress plugin
- Requires complex authentication handling
- Harder to distribute via WordPress.org
- Loses WordPress admin integration benefits

## Decision

**Chosen option**: Option 2: React admin with custom tables and vanilla JS frontend

Build a PHP plugin with React admin UI using @wordpress/scripts, custom database tables with dbDelta(), OpenAI integration via PHP SDK, and vanilla JavaScript for the frontend quiz interface. This provides the best balance of modern UI for administrators, performance for quiz takers, and WordPress ecosystem compatibility.

**Implementation skills**: none detected (no community skills installed yet)

## Rationale

The chosen approach aligns with the project requirements: educators need a modern, fast quiz builder (React UI), high performance is required (custom tables), and the plugin must work on standard WordPress hosting (WordPress conventions). Option 2 provides the right balance: React for maintainable admin UI, vanilla JavaScript for fast frontend quiz loading, custom tables for performance, while staying within WordPress ecosystem constraints.

The frontend uses vanilla JavaScript instead of React to keep the quiz interface lightweight for website visitors. React would add 40KB+ of JavaScript bundle for frontend users who just need to take a quiz. Vanilla JavaScript provides the best performance for quiz takers while React provides the best developer experience for the complex admin quiz builder.

Option 1 (pure PHP) would limit UI capabilities and performance. Option 3 (full React SPA) would be overkill and harder to distribute. Option 2 matches what successful WordPress plugins like QSM use while allowing modern improvements with the admin React UI.

## Proposed stack

| Layer | Choice | Reason |
|---|---|---|
| Language | PHP 8.0+ | WordPress minimum requirement, good performance, broad hosting support |
| Framework | WordPress Plugin API | Standard WordPress plugin architecture, maximum compatibility |
| Primary DB | Custom tables via dbDelta() | Optimized for quiz data relationships and query performance |
| Build tool | @wordpress/scripts (webpack) | Official WordPress build tool, auto-externalizes WP packages |
| Admin UI | React 18 via @wordpress/components | Modern UI, matches Gutenberg, maintainable |
| Frontend Quiz UI | Vanilla JavaScript | Lightweight, no dependencies, fast loading for quiz takers |
| AI Integration | OpenAI PHP SDK | Clean API integration, no extra dependencies |
| File Storage | WordPress uploads directory | Standard WordPress pattern, works everywhere |
| Background Jobs | WP-Cron | Simple, no extra infrastructure required |
| Caching | WordPress transients API | Simple, works on all WordPress hosting |
| Testing | PHPUnit + Jest (via wp-scripts) | Standard WordPress plugin testing approach |
| Observability | WordPress debug log + custom logs | Simple, effective, no external dependencies |
| REST API | Custom namespace (/wp-json/premiaquiz/v1/) | Clean API design, proper versioning |
| Hosting | WordPress hosting (shared/VPS/managed) | Target environment for the plugin |
| Schema Versioning | Version in wp_options + incremental migrations | Standard WordPress pattern for database migrations |

## Build plan

This is an architecture decision spec. The build plan is derived by `/develop` at build time for the scaffold feature that executes this stack decision.

## Consequences

**Positive**:
- Modern React admin UI that users expect
- Custom tables provide better performance than post types for quiz data
- Follows WordPress conventions for maximum compatibility
- Clear architecture that's maintainable at scale

**Negative / tradeoffs**:
- Requires JavaScript build step (but @wordpress/scripts handles this well)
- More complex than a pure PHP plugin
- Must properly externalize React and WordPress packages

**Neutral**:
- Plugin will have a build process (npm install, npm run build)
- Custom tables require migration management
- React admin requires understanding of WordPress package dependencies

## Follow-up

- [ ] Install @wordpress/scripts and configure build pipeline
- [ ] Create AGENTS.md with coding standards and conventions
- [ ] Design the complete database schema (spec 0002)
- [ ] Design the admin UI component structure
- [ ] Design the frontend vanilla JavaScript quiz architecture (module pattern, event handling, state management)

## References

**Project sources** (verifiable, in this repo):
- Scope document: `docs/scope/scope.md` (project requirements and feature list)

**Practices & standards**:
- WordPress Plugin Handbook: follows standard WordPress plugin architecture
- dbDelta() for database migrations: official WordPress method for creating/updating tables
- WordPress security standards: nonce verification, capability checks, sanitization, escaping
- @wordpress/scripts: official WordPress build tooling for React-based admin UIs

**Links** (web verified only):
- WordPress Plugin Handbook: https://developer.wordpress.org/plugins/
- dbDelta reference: https://developer.wordpress.org/reference/functions/dbdelta
- @wordpress/scripts documentation: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts
- Creating Tables with Plugins: https://developer.wordpress.org/plugins/creating-tables-with-plugins
- Introduction to securely developing plugins: https://learn.wordpress.org/tutorial/introduction-to-securely-developing-plugins
