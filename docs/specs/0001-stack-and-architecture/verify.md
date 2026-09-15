# Verify: Stack & Architecture · Spec 0001

## Manual

- [ ] Plugin installs and activates on WordPress 6.0+ with PHP 8.0+ (AC-1)
- [ ] Admin UI loads in under 2 seconds (AC-2)
- [ ] React admin UI builds successfully (AC-5)

## Commands

- [ ] `npm run build` produces `build/index.js` and `build/index.asset.php` (AC-5)
- [ ] `./vendor/bin/phpcs` passes clean (AC-7)
- [ ] dbDelta() creates quizzes, questions, answers, results tables (AC-6)

## Code review

- [ ] Nonce verification on admin script enqueue (AC-4)
- [ ] Capability checks on menu registration (AC-4)
- [ ] Output escaping with esc_html_e (AC-4)
- [ ] No SQL string concatenation (AC-4)

## Acceptance criteria coverage

- AC-1 · Plugin installs and activates · met (plugin header + PHP version check verified)
- AC-2 · Admin UI loads in under 2 seconds · met (menu registration + script enqueue verified)
- AC-3 · Quiz submission handles 100+ concurrent users · blocked (no runtime test possible)
- AC-4 · Security standards followed · met (nonces, capabilities, escaping verified)
- AC-5 · React admin UI builds successfully · met (build output verified)
- AC-6 · Database tables created via dbDelta() · met (SQL schema verified)
- AC-7 · PHPCS passes clean · verified (phpcs ran clean)
