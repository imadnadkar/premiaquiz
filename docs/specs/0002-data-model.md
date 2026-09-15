# 0002. Data model

**Date**: 2026-09-15
**Status**: In Progress

## Summary

This spec defines the database schema for the Premia Quiz & Survey AI plugin. Eight custom tables store quizzes, questions, options, sessions, answers, result pages, lead field definitions, and lead submissions. The model supports both assessment quizzes (score based) and personality quizzes (pattern based), with flexible lead capture fields, configurable data retention, and analytics ready structures.

## Context

Every feature in the plugin (scoring, analytics, lead capture, AI grading) depends on this data model. Getting it wrong means breaking migrations later, which is painful in WordPress where dbDelta() has limitations. The model must support two quiz types, anonymous and authenticated users, flexible lead capture, and enough structure for analytics without over engineering.

Key forces at play:
- Two quiz types with different scoring models (assessment vs personality)
- Must support both WordPress logged in users and anonymous visitors
- Lead capture needs flexible admin defined fields, not a fixed schema
- Data retention must be configurable per quiz without breaking the model
- Analytics need both aggregate stats and per question breakdowns
- Quiz editing during active sessions must not disrupt in progress attempts
- The model must be stable enough that later slices can build on it without breaking migrations

## Requirements

**User stories**:
- As an administrator, I want to create quizzes with different types (assessment, personality) so that I can use them for different purposes
- As an administrator, I want to define which contact fields to capture so that I can collect the information I need for lead generation
- As a quiz taker, I want to take a quiz and see my results immediately so that I get instant feedback
- As an administrator, I want to view analytics about quiz performance so that I can understand how my quizzes are performing

**Acceptance criteria** (the contract, each criterion is IDed and independently checkable):
- **AC-1**: All 8 tables are created via dbDelta() on plugin activation with correct column types and indexes
- **AC-2**: A quiz can be created with type assessment or personality, status draft/published/archived, and all required fields
- **AC-3**: A quiz can have unlimited questions, each with up to 10 options, with correct ordering via position field
- **AC-4**: A quiz session is created when a user starts a quiz, linked to WP user ID for logged in users or session key for anonymous
- **AC-5**: Individual answers are stored per question per session, with answer value, correctness, and points awarded
- **AC-6**: Result pages are mapped to score ranges (assessment) or outcome patterns (personality), and assigned on session completion
- **AC-7**: Lead field definitions are configurable per quiz, and lead submissions store the captured values
- **AC-8**: Deleting a quiz cascades to all related entities (questions, options, sessions, answers, result pages, lead fields, lead submissions)
- **AC-9**: In progress sessions keep their quiz snapshot when the quiz is edited (questions and options are copied to the session)
- **AC-10**: Sessions are marked as abandoned when the browser closes (via JavaScript beacon)
- **AC-11**: Concurrent sessions are independent, no conflicts between simultaneous quiz takers
- **AC-12**: Data retention is configurable per quiz, with automatic cleanup of old sessions when retention days are set

## Options considered

### Option 1: Single flat table

Store all quiz data in one or two wide tables with many nullable columns.

**Pros**:
- Simple to query, no joins needed
- Easy to understand

**Cons**:
- Poor normalization, data duplication
- Hard to extend for new question types or features
- Analytics queries become complex with nullable columns

### Option 2: Normalized relational schema (Recommended)

Separate tables for each entity with proper foreign keys and indexes.

**Pros**:
- Clean normalization, no data duplication
- Easy to extend with new fields or entities
- Analytics queries are straightforward with proper indexes
- Follows WordPress dbDelta() patterns

**Cons**:
- More tables to manage (8 total)
- Requires joins for complex queries
- Migration management is more complex

### Option 3: Hybrid with JSON columns

Use relational tables but store flexible data in JSON columns (settings, outcome patterns, lead field values).

**Pros**:
- Balances structure with flexibility
- Easy to add new config without schema changes
- WordPress supports longtext for JSON storage

**Cons**:
- JSON queries are slower than indexed columns
- Harder to validate data at database level
- Some analytics require parsing JSON

## Decision

**Chosen option**: Option 2: Normalized relational schema

Build 8 custom tables with proper foreign keys, indexes, and column types. Use JSON columns only for genuinely flexible configuration (quiz settings, question settings, outcome patterns, lead field settings) where the structure varies per record and is not queried directly.

**Implementation skills**: `wp-plugin-development` (WordPress/agent-skills, `.claude/skills/wp-plugin-development/`) · `wp-performance` (WordPress/agent-skills, `.claude/skills/wp-performance/`)

## Rationale

Option 2 is the right choice because this plugin has clear relational structure: quizzes have questions, questions have options, sessions have answers. A normalized schema makes analytics queries straightforward, supports proper indexing for performance, and follows WordPress dbDelta() patterns.

Option 1 (flat table) would create a maintenance nightmare as features grow, with dozens of nullable columns and complex queries. Option 3 (hybrid with JSON) is tempting for flexibility, but the flexible parts (settings, outcome patterns, lead field values) are not queried directly, they are read and parsed at the application layer.

The JSON columns are used sparingly for genuinely variable configuration: quiz settings (time limit, pass mark), question settings (correct answer, points), outcome patterns (which answer patterns map to which result), and lead field settings (validation rules, placeholders). These are written once and read as a whole, never queried by individual keys.

## Feature design

**Data model sketch**:

| Entity | Primary Key | Foreign Keys | Key Fields |
|---|---|---|---|
| Quiz | id (bigint, auto) | created_by → WP users | title, description, status (draft/published/archived), quiz_type (assessment/personality), retention_days (nullable), settings (JSON), created_at, updated_at |
| Question | id (bigint, auto) | quiz_id → Quiz | question_type (enum: multiple_choice, true_false, fill_blank, short_answer, file_upload, dropdown, matching, ordering, polar, captcha, date, paragraph), text, position, is_required, settings (JSON), created_at, updated_at |
| Question Option | id (bigint, auto) | question_id → Question | text, position, is_correct, weight (decimal), image_url (nullable), created_at |
| Quiz Session | id (bigint, auto) | quiz_id → Quiz, user_id → WP users (nullable), result_page_id → Result Page (nullable) | session_key (unique per quiz), status (in_progress/completed/abandoned), score, percentage, started_at, completed_at, ip_address, user_agent, quiz_snapshot (JSON, copy of quiz structure at start) |
| Quiz Answer | id (bigint, auto) | session_id → Quiz Session, question_id → Question | answer_value (text), is_correct, points_awarded (decimal), answered_at |
| Result Page | id (bigint, auto) | quiz_id → Quiz | title, content, image_url (nullable), min_score, max_score, min_percentage, max_percentage, outcome_pattern (JSON), position, created_at, updated_at |
| Lead Field Definition | id (bigint, auto) | quiz_id → Quiz | field_name, field_label, field_type (text/email/phone/number/textarea), is_required, position, settings (JSON), created_at |
| Lead Submission | id (bigint, auto) | quiz_id → Quiz, session_id → Quiz Session, field_id → Lead Field Definition | field_value (text), created_at |

**Unique constraints**:
- Quiz Session: (user_id, quiz_id) for logged in users, (session_key, quiz_id) for anonymous
- Quiz Answer: (session_id, question_id)
- Lead Field Definition: (quiz_id, field_name)

**State transitions**:

Quiz: draft → published → archived

Quiz Session: in_progress → completed | in_progress → abandoned

**API surface**:

No REST API endpoints at this stage (deferred to slice 9). The interface is PHP classes that create and query the tables.

**Value sourcing**:

| Action | Value produced / displayed | Source |
|---|---|---|
| Quiz creation | Quiz ID | Auto increment on insert |
| Session creation | Session ID | Auto increment on insert |
| Score calculation | Score | Sum of points_awarded across all answers |
| Percentage calculation | Percentage | Score / total possible points * 100 |
| Result page assignment | Result page ID | Match score/percentage to result page ranges |
| Lead field values | Submitted values | Input from quiz taker, validated against field type |

**Key invariants**:
- One answer per question per session (enforced by unique constraint)
- Score is calculated on session completion, not during the quiz
- Quiz snapshot is copied to session on start, so quiz edits do not affect in progress sessions
- Cascade delete ensures no orphaned records when a quiz is deleted

**Security model**:
- Quiz management (create, edit, delete): manage_options capability (admin only)
- Quiz viewing (admin): manage_options capability (admin only)
- Quiz taking: public (no authentication required)
- Lead submissions: stored with session, linked to quiz

**Configuration required**:

None new. The plugin uses WordPress options API for configuration, no new environment variables needed.

**Critical test scenarios** (each maps to an acceptance criterion in ## Requirements):
- Happy path: Create a quiz with 5 questions, take it as an anonymous user, see results, verify all 8 tables have correct data, verifies **AC-1**, **AC-2**, **AC-3**, **AC-4**, **AC-5**, **AC-6**
- Failure case: Start a quiz, edit the quiz while in progress, complete the quiz, verify the session used the original quiz structure, verifies **AC-9**
- Auth/permission: Try to create a quiz without manage_options capability, verify access denied, verifies **AC-2**
- Edge case: Two users take the same quiz simultaneously, verify no conflicts, verifies **AC-11**
- Edge case: Set quiz retention to 30 days, run cleanup, verify old sessions are deleted, verifies **AC-12**

## Build plan

Ordered for Tracer Bullet approach: stand up a thin end to end thread through every layer first, then thicken.

1. Create the migration class that defines all 8 tables with dbDelta(), satisfies **AC-1**
2. Create the Quiz repository class with CRUD operations, satisfies **AC-2**
3. Create the Question repository with CRUD and ordering, satisfies **AC-3**
4. Create the Question Option repository with CRUD and ordering, satisfies **AC-3**
5. Create the Quiz Session repository with creation and status updates, satisfies **AC-4**, **AC-10**, **AC-11**
6. Create the Quiz Answer repository with creation and batch operations, satisfies **AC-5**
7. Create the Result Page repository with CRUD and range matching, satisfies **AC-6**
8. Create the Lead Field Definition repository with CRUD, satisfies **AC-7**
9. Create the Lead Submission repository with creation, satisfies **AC-7**
10. Create the cascade delete handler for quiz deletion, satisfies **AC-8**
11. Create the session snapshot mechanism for quiz editing during active sessions, satisfies **AC-9**
12. Create the retention cleanup handler for old sessions, satisfies **AC-12**
13. Create the analytics query helpers for basic stats and detailed breakdowns, satisfies **AC-2** through **AC-7**

## Consequences

**Positive**:
- Clean, normalized schema that is easy to extend
- Proper indexing for analytics queries
- Supports both quiz types without schema changes
- Flexible lead capture without fixed columns
- Quiz editing does not disrupt active sessions

**Negative / tradeoffs**:
- 8 tables to manage (but each has a clear purpose)
- Requires joins for complex queries (but analytics queries are straightforward)
- JSON columns for flexible config are harder to query directly (but these are read as a whole, not queried by keys)

**Neutral**:
- Plugin will have a migration class that runs on activation
- dbDelta() has limitations (no DROP COLUMN, limited index changes)
- Quiz snapshot adds some storage overhead per session
- Analytics queries will need proper indexing for performance

## Follow-up

- [ ] Design the admin UI component structure for the quiz builder
- [ ] Design the frontend vanilla JavaScript quiz architecture
- [ ] Consider adding audit logs for quiz mutations (create, edit, delete)
