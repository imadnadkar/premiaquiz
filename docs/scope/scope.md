# Scope: Premia Quiz & Survey AI

A WordPress plugin for creating quizzes and surveys with AI-powered question generation and grading, targeting educators and course creators. Built with React admin UI, custom database tables, and OpenAI integration.

**Build approach:** Tracer Bullet (prove the whole pipe works before building any part of it fully).
**Workflow:** Beta (after /develop, /check verify then /test). The project default level of rigor. `/architect` is the recommended first stop for a feature with a real decision, but skippable when you already know the build. Any feature can carry its own tag (e.g. `· GA`) to do more or less.

_These are recommendations to keep your build orderly, not requirements. Skip anything that does not fit: if you already know how to build a feature, use `/develop` and skip `/architect`. You decide when a feature is `done`._

## At a glance

| # | Feature | Phase | Status |
|---|---------|-------|--------|
| 1 | Stack & architecture | Foundation | in-progress |
| 2 | Coding standards & tooling | Foundation | in-progress |
| 3 | Data model | Foundation | in-progress |
| 4 | Design system & UI foundation | Foundation | planned |
| 5 | Core quiz/survey loop | Slice 1 | planned |
| 6 | Question types (12+) | Slice 2 | planned |
| 7 | Results & scoring | Slice 3 | planned |
| 8 | Lead capture forms | Slice 4 | planned |
| 9 | Analytics dashboard | Slice 5 | planned |
| 10 | WordPress block editor | Slice 6 | planned |
| 11 | Email marketing integrations | Slice 7 | planned |
| 12 | WooCommerce integration | Slice 8 | planned |
| 13 | API & webhooks | Slice 9 | planned |
| 14 | AI question generation | Slice 10 | planned |
| 15 | AI grading & feedback | Slice 11 | planned |
| 16 | Performance optimization | Slice 12 | planned |
| 17 | Security hardening | Slice 13 | planned |

## Foundations

### 1. Stack & architecture · in-progress
Decide the stack and scaffold a runnable WordPress plugin so every later slice builds on real structure.
**Done when:** the stack is recorded in a spec and the empty plugin scaffold boots locally and passes build.
- [x] Decide the stack (spec): `/architect stack & architecture`
- [x] Build it: `/develop stack & architecture`
   - [x] Create plugin directory structure and main plugin file
   - [x] Install and configure @wordpress/scripts build pipeline
   - [x] Set up PHP coding standards and PHPCS configuration
   - [x] Create basic plugin activation/deactivation hooks
   - [x] Verify plugin loads in WordPress admin
- [x] Verify it: `/check verify stack & architecture`
- [ ] Test it: `/test stack & architecture`
Spec 0001 · code in `plugins/premiaquiz/`

### 2. Coding standards & tooling · in-progress
Capture conventions, then install lint, format, and pre-commit enforcement from the real scaffolded project.
**Done when:** root `AGENTS.md` reflects the real stack, and lint/format/pre-commit run clean.
- [x] Capture conventions + tooling choices: `/audit`
- [x] Install the tooling: `/develop tooling`
- [ ] Check it runs clean: `/test`

### 3. Data model · in-progress
Core entities every feature builds on: quizzes, questions, answers, results, users, leads.
**Done when:** entities and relationships support later slices (scoring, analytics, lead capture) without a breaking migration.
- [x] Design it (spec): `/architect data model` → [0002](../specs/0002-data-model.md)
- [x] Build it: /develop data model
  - [x] Create migration class for all 8 tables (AC-1)
  - [x] Create Quiz and Question repositories with CRUD operations (AC-2, AC-3)
  - [x] Create Session and Answer repositories (AC-4, AC-5, AC-10, AC-11)
  - [x] Create Result Page, Lead Field, and Lead Submission repositories (AC-6, AC-7)
  - [x] Create cascade delete, snapshot mechanism, and retention cleanup (AC-8, AC-9, AC-12)
- [ ] Verify it: /check verify data model
- [ ] Test it: /test data model
Spec 0002 · code in `plugins/premiaquiz/includes/`

### 4. Design system & UI foundation
Visual language, layout primitives, and base components so the flows feel cohesive and accessible.
**Done when:** `design.md` covers type/color/spacing/components, and base components handle focus and keyboard.
- [ ] Design it (spec): `/architect design system & UI foundation`

## Slice 1: Core quiz/survey loop

### 5. Core quiz/survey loop
Create a quiz, add questions, preview it, and submit responses. This is the walking skeleton that proves the stack connects end to end.
**Done when:** a user can create a quiz, add questions, preview it, submit responses, and see results.
- [ ] Design it (spec): `/architect core quiz/survey loop`

## Slice 2: Question types (12+)

### 6. Question types (12+)
Support all essential question types: multiple choice, true/false, fill-in-blank, short answer, file upload, dropdown, matching, ordering, polar, captcha, date, paragraph.
**Done when:** all 12+ question types work correctly in the quiz builder and frontend.
- [ ] Design it (spec): `/architect question types`

## Slice 3: Results & scoring

### 7. Results & scoring
Score-based results, personality outcomes, percentage scoring, and custom grading systems.
**Done when:** quizzes can have multiple result pages based on score ranges, with personalized messages.
- [ ] Design it (spec): `/architect results & scoring`

## Slice 4: Lead capture forms

### 8. Lead capture forms
Capture email and contact information before or after quiz submission for lead generation.
**Done when:** users can optionally collect leads with customizable form fields and position.
- [ ] Design it (spec): `/architect lead capture forms`

## Slice 5: Analytics dashboard

### 9. Analytics dashboard
Track submissions, scores, completion rates, and response breakdowns in admin dashboard.
**Done when:** admin can view quiz statistics, individual responses, and aggregate data.
- [ ] Design it (spec): `/architect analytics dashboard`

## Slice 6: WordPress block editor

### 10. WordPress block editor
Embed quizzes in posts/pages via Gutenberg block with live preview.
**Done when:** users can add quiz block to any post/page and see preview in editor.
- [ ] Design it (spec): `/architect WordPress block editor`

## Slice 7: Email marketing integrations

### 11. Email marketing integrations
Connect to Mailchimp, ConvertKit, ActiveCampaign, and other email platforms.
**Done when:** quiz leads can be automatically sent to configured email marketing services.
- [ ] Design it (spec): `/architect email marketing integrations`

## Slice 8: WooCommerce integration

### 12. WooCommerce integration
Connect quizzes to WooCommerce products, offer quizzes as product add-ons, or require quiz completion for purchase.
**Done when:** quizzes can be linked to WooCommerce products with conditional access.
- [ ] Design it (spec): `/architect WooCommerce integration`

## Slice 9: API & webhooks

### 13. API & webhooks
REST API for programmatic access, webhooks for real-time notifications, and Zapier integration.
**Done when:** external systems can create, retrieve, and respond to quizzes via API, with webhook notifications.
- [ ] Design it (spec): `/architect API & webhooks`

## Slice 10: AI question generation

### 14. AI question generation
Use OpenAI GPT-4 to generate questions from topics, automatically create quizzes from content.
**Done when:** users can input a topic and get AI-generated questions that can be edited and used.
- [ ] Design it (spec): `/architect AI question generation`

## Slice 11: AI grading & feedback

### 15. AI grading & feedback
Use AI to grade open-ended answers, provide personalized feedback, and suggest improvements.
**Done when:** AI can grade text answers and provide meaningful feedback to users.
- [ ] Design it (spec): `/architect AI grading & feedback`

## Slice 12: Performance optimization

### 16. Performance optimization
Optimize database queries, implement caching, handle 100+ concurrent quiz takers.
**Done when:** plugin handles high traffic without performance degradation.
- [ ] Design it (spec): `/architect performance optimization`

## Slice 13: Security hardening

### 17. Security hardening
Nonce verification, capability checks, input sanitization, rate limiting, and security audit.
**Done when:** plugin follows WordPress security standards and passes security review.
- [ ] Design it (spec): `/architect security hardening`

## Deferred
Out of scope for the current build pass, kept so the plan stays honest.
- **Multilingual support**: translation and localization · needs a decision
- **Certificate generation**: quiz completion certificates · needs a decision
- **Quiz themes**: premium visual themes for quizzes · needs a decision
- **Import/export**: CSV import/export of quizzes · needs a decision
- **Leaderboards**: public leaderboards for quizzes · needs a decision
- **Conditional logic**: show/hide questions based on answers · needs a decision
- **Time limits**: quiz timers and time-based scoring · needs a decision
- **User accounts**: user registration and quiz history · needs a decision

## Legend

**The decision box.** Every feature carries exactly one, the sub-task whose label ends with `(spec)`. Its wording varies (`Design it (spec)` normally, `Decide the stack (spec)` on Stack & architecture), so skills locate it by that `(spec)` suffix, never by an exact label. Every other box is an execution box and `/architect` never ticks one.

**Feature lifecycle**: the scope updates as a feature moves; each row is what it shows and who sets it:

| State | Set by | The feature shows |
|---|---|---|
| `planned` · needs a decision | `/scope` | one box: `Design it (spec): /architect <feature>` |
| `in-progress` (designed) | **`/architect` at spec capture** | `Design it` ticked; spec linked; `Build it: /develop <feature>` + **2 to 5 milestones**; the tier's closing boxes (`Verify it` Alpha+, `Test it` Beta+); any surfaced follow-up enrolled |
| `in-progress` (building) | `/develop` | milestone sub-boxes tick one by one; code pointer filled |
| `in-progress` (verified) | `/check verify` | `Build it` + milestones ticked; `Verify it` ticked |
| `done` | **you, when you decide it is** (any skill sets it when you say so); `/sync` reconciles | boxes you ran ticked, skipped ones marked skipped; the tier's last stage (`Beta` → after `/test`) is the suggested point to call it done; `/sync` captures conventions |

- **Next step** = the first unticked box (always a command or a tracked milestone).
- **needs a decision** = run `/architect` first; otherwise straight to `/develop` (or `/audit` for standards & tooling). The tag drops once the spec is captured.
- **Atomic build tasks live in the spec's `## Build plan`, not here**: the scope carries only the milestone rollup.
- **Status** `planned` → `in-progress` → `done`, plus `existing` (pre-workflow) and `dropped` (de-scoped, kept for history).
- **Approach tag** beside a heading (e.g. `· Facade`) overrides the project default for that feature; no tag = inherits it.
- **Workflow tier tag** beside a heading (e.g. `· GA`, `· Prototype`) sets that one feature's rigor above or below the project default; no tag inherits the default. It decides the feature's check boxes and each skill's next suggestion.
- **Workflow** (header line) is the project default, what runs after `/develop`: **Prototype** = nothing (trust develop's own build time self check); **Alpha** = `/check verify`; **Beta** = `/check verify` then `/test`; **GA** = adds a fresh model `/check review` then `/document`. A feature built on an unratified decision (an `Assumed` spec) stays flagged, but that never blocks `done`.
- **Pointer line** (`spec <n> · code in <path>`): the spec link added by `/architect`, the code path by `/develop`.
