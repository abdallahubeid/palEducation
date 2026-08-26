# Database Architecture — palEducation
### Analysis & Specification Document · **Revision 3**

**Role:** Senior Laravel Architecture & Database Engineer · **Method:** 4Ds (Discover · Define · Design · Deliver)
**Date:** 2026-08-26 · **Supersedes:** Revision 2 (2026-08-23)
**Phase:** phases 1–2 implemented; phase 3 partial
**Platform state:** 36 / 48 screens built. **Phase 1 (identity) and phase 2 (permissions) are live**, `branches` from phase 3 is live. 5 domain tables + 5 Spatie tables + 6 framework tables in the database. Zero policies, zero global scopes.

> **Constraint relaxed in Revision 3.** Revisions 1–2 were pre-implementation specifications and deliberately contained no code. Implementation has begun, so this document now also records **what is actually built** — marked inline with ✅ · ⏳ · ❌ — while the descriptive specification style is retained for tables not yet built.

---

# 0-A · Revision 3 — the identity reversal

> 🔴 **Read this before any other section.** Revision 2's headline decision — phone-based identity — **was reversed during implementation.** Sections written under it have been corrected below, but if you are reading an older copy, Cluster A is the part that changed.

## What changed

| Revision 2 specified | Revision 3 · **as built** |
|---|---|
| Identity is a phone number: `phone_country_code` + `phone_number`, composite unique | **Identity is an email address**, unique · plus **Google OAuth** (`google_id`, `google_avatar_url`) |
| `phone_verified_at` | `email_verified_at` |
| `password` required | **`password` nullable** — a Google-registered account has none |
| `phone_verification_codes` table | **Not created.** Verification is Laravel's signed email link; reset is the framework's email flow |
| `password_reset_tokens` removed (keyed by email) | **Retained** — email is the identifier again, so the framework table is correct |
| `students` has no phone (phone *was* the identity) | **`students.phone`** added — profile contact metadata, explicitly **not** a credential |

## Why

Revision 2 §0.4 listed three built screens that phone identity contradicted — login (13), register (14), forgot password (17) — and treated them as work to be scheduled. Email identity **removes all three conflicts instead of paying for them**, and adds a Google sign-in path that eliminates password handling for most students. The three language keys Revision 2 marked obsolete (`auth.email_label`, `auth.email_placeholder`) stay valid.

The cost is the loss of SMS-verified phone ownership at signup. That is accepted: phone is now profile metadata, collected at registration but never used to authenticate.

## Consequences for the invariants (§5)

| # | Revision 2 | Revision 3 |
|---|---|---|
| 3 | «A phone number identifies at most one live account» | ❌ **Void.** Replaced by: **an email address identifies at most one live account** — enforced by `users.email` unique ✅ |
| 14 | «One-time codes are never stored in plaintext» | ⚪ **Moot** — no code table exists. Reinstate if SMS is ever added |
| 1 · 2 | Specified | ✅ **Both enforced in the database as designed** |
| 4 | `userable_type` and the Spatie role agree | ✅ **Now enforceable** — see §0-B |

## Consequences for the open items (§7)

| Item | Revision 3 status |
|---|---|
| §7.1 `admins` table | ✅ **Adopted (A) and built** |
| §7.2 Drop `email` entirely | ✅ **Reversed** — email is the identifier |
| §7.3 `governorate` / `country_code` on `students` | ✅ **Adopted and built** |
| §7.4 `guardian_phone` at signup | ✅ **Settled as profile-only** — the column exists, registration does not collect it |
| §7.7 Soft delete blocks re-registration for 30 days | ⏳ **Still open, now about the email column** — the unique index behaves identically |

---

# 0-B · Revision 3 — permissions are live

Phase 2 landed. `spatie/laravel-permission` **8.3.0** is installed, its config and migration are published, and the three roles are seeded.

| Element | Status |
|---|---|
| `config/permission.php` published (`teams` = false) | ✅ |
| 5 tables created — `roles` · `permissions` · `model_has_roles` · `model_has_permissions` · `role_has_permissions` | ✅ |
| `HasRoles` on `User` — **and on `User` only** (integration decision 1) | ✅ |
| `student` · `teacher` · `admin` seeded (`RoleSeeder`, guard `web`) | ✅ |
| Granular `permissions` rows | ❌ Deferred until the first real Policy exists — seeding permission names nothing checks is worse than none |
| Invariant 4 held at creation time | ✅ `User::syncRoleToShape()` inside both account-creation transactions |
| Scheduled drift check comparing the two discriminators | ❌ Not built |

**How invariant 4 is actually enforced.** `User::roleName()` derives the role name from `userable_type` — one function, used by both account-creation paths and by the backfill migration, so no second rule can drift from the first. `User::syncRoleToShape()` applies it inside the same transaction that creates the account.

**The role-less window Revision 2 predicted did open**, because phase 1 shipped before phase 2. It was closed by a one-off data migration (`2026_08_26_000001_backfill_user_roles`) and cannot reopen: both creation paths now assign the role atomically.

---

# 0 · Revision summary

## 0.1 The five decisions now locked

| # | Decision | Effect on the schema |
|---|---|---|
| 1 | **Polymorphic identity** — `users` is an authentication entry table linked one-to-one polymorphically to `students` and `teachers` | Restructures the identity cluster and **every foreign key that previously pointed at a person** |
| 2 | **Spatie permissions** — roles handled dynamically; the static `role` column is removed | +5 tables; introduces a second discriminator that must be kept aligned with the first |
| 3 | **M-2 resolved** — no gating, 90% unlock threshold, 2 attempts, highest score counts | Four columns move from unspecified to concrete; the access predicate **stays flat** |
| 4 | **M-5 resolved** — completion is server-verified and authoritative | Adds real anti-fraud structure to `lecture_progress`; a counter alone is no longer sufficient |
| 5 | **Academic terms — Option A** — explicit table; subscriptions and lectures bind strictly | `academic_term_id` becomes **required** on lectures, orders and subscriptions |

## 0.2 What changed structurally since Revision 1

| Change | Detail |
|---|---|
| `users` rebuilt | ~~Identity is now **phone-based**.~~ ❌ **Reversed in Rev 3 — identity is email + Google OAuth (§0-A).** What survives: no `role`, no `branch_id`, no `name`. It holds credentials, activation state, and the polymorphic pointer — nothing else. |
| `teacher_profiles` **removed** | Absorbed into the new first-class `teachers` table. |
| `students`, `teachers` **added** | Domain tables owning all human-readable and academic data. |
| `admins` **added (recommended)** | See §7.1 — without it, an administrator has no name, because names now live on domain tables. |
| ~~`phone_verification_codes` **added**~~ | ❌ **Reversed in Rev 3** — withdrawn with phone identity (§0-A, §3.5). |
| ~~`password_reset_tokens` **removed**~~ | ❌ **Reversed in Rev 3** — retained, because email is the identifier again. |
| 5 Spatie tables added | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`. ✅ **built** |
| **Every person-referencing FK re-pointed** | New governing rule in §4.4: *domain references point at domain tables; actor and audit references point at `users`.* |
| `lecture_progress` expanded | M-5 being authoritative means coverage intervals, heartbeat bounds, and a completion source — not just a seconds counter. |
| **Table total** | 30 domain → 33 domain → **32 domain in Rev 3**, plus 5 Spatie, plus 6 framework = **43**. |

## 0.3 The decision that fixed a problem Revision 1 could not solve

Revision 1 recorded a defect it had no way to remove: `users.branch_id` had to be **nullable**, because teachers and administrators have no branch. That made the platform's first governing principle — a student belongs to exactly one branch — unenforceable at the database level, downgraded to an application check plus a scheduled integrity scan.

**The polymorphic split eliminates that.** `branch_id` now lives on `students`, where every row is by definition a student, so the column becomes **required**. Invariant 1 is now a genuine database constraint rather than a promise.

This is the strongest single argument for the new structure, and it reverses the reservation Revision 1 raised about moving branch data off `users`. The cost that reservation identified — an extra hop on the hottest lookup in the system — is real but small and fully mitigable (§4.2). The integrity gain is permanent and not otherwise obtainable.

## 0.4 ⚠️ ~~Conflicts with already-built screens~~ — **RESOLVED in Revision 3, by reversal**

> 🔴 **All three conflicts below are gone, and none of the listed screen changes was made.** Identity moved to email (§0-A), so the three screens were right as built and the specification was what changed. The three files listed here are correct in their current form; the two language keys marked obsolete are still in use. **Read this section as history, not as work.**

<details>
<summary>Revision 2's original text — retained so the reversal is legible</summary>

Phone-based identity contradicts three screens that are already built. These are not risks; they are present-tense mismatches, confirmed by reading the files.

| Screen | File | Conflict |
|---|---|---|
| **13 · Login** | `resources/views/pages/auth/login.blade.php` lines 32–35 | The identity field is `name="email"` with `type="email"`. It must become a phone-number field with a country-code selector. |
| **17 · Forgot password** | `resources/views/pages/auth/forgot-password.blade.php` lines 14–17 | Same email field, and the whole flow assumes an emailed reset link. It becomes an SMS one-time-code flow, which also changes the success copy (`auth.forgot_sent_body` currently refers to an inbox). |
| **14 · Register** | `resources/views/pages/auth/register.blade.php` | Three separate mismatches: it collects **`email`** (which now has no column anywhere), it collects **`country` and `governorate`** (which have no home in the agreed `students` specification), and it does **not** collect **`guardian_phone`** (which the specification includes). |

Two language keys also become obsolete or need renaming: `auth.email_label` and `auth.email_placeholder`.

None of this blocks the schema. All of it must be scheduled, and §7 turns each mismatch into an explicit decision rather than leaving it to be discovered during implementation.

</details>

**Revision 3 outcome, per screen:** login (13) keeps its email field ✅ · register (14) keeps `email`, keeps `country`/`governorate` — both now have columns on `students` ✅ — and still does not collect `guardian_phone`, settled as profile-only (§7.4) ✅ · forgot password (17) keeps the emailed-reset assumption and `auth.forgot_sent_body` stays accurate ✅. **The backend for screen 17 is still not built** — the route is a bare `Route::view`. That is the one real item surviving from this section.

## 0.5 A correction carried forward from Revision 1

`docs/01-project-understanding.md` contradicts itself: §7 marks M-1 and M-4 resolved on 2026-08-22, while §9 (line 347) and §10 (line 359) still assert they were not and warn against building a database on them. §7 is correct. With M-2, M-5 and the terms question now also settled, **§9 and §10 of that document are fully stale and should be rewritten** — every decision they list as blocking is now closed.

---

# 1 · DISCOVER

## 1.1 Method

Tables were derived from three sources, in priority order, not from a generic e-learning template:

1. **The two governing principles** — *personalised exclusion* (a student never sees content outside their branch) and *the closed loop* (video completion is an event that opens the quiz automatically). Both must be representable as data. With M-5 now resolved as server-authoritative, the second is no longer satisfiable by a client-side flag and requires real structure.
2. **The 48-screen inventory** — every screen read for the entities it displays, filters by, sorts on, or mutates. A screen that sorts by a column implies that column exists and is indexable.
3. **The non-negotiable rules** — soft delete with a trash surface, files never served from the public directory, permission enforced in query scope *and* policy *and* view, and no card data ever stored.

A table earned its place only if a built or specified screen reads from it or writes to it.

## 1.2 The inventory — 38 domain + 5 permission + 6 framework

> **Revision 3 count.** 33 → 32 (`phone_verification_codes` withdrawn, §3.5) → **38 with the six landing-content tables of Cluster J**, which Revisions 1–2 did not anticipate because the landing page was static.
>
> **Built so far: 16 of 38 domain + 5 of 5 permission + 6 of 6 framework.** Phases 1–4 complete, plus Cluster J and a reduced `settings`. Each table below carries its status.

### Cluster A — Identity & Access (4 domain · **4 built**)

| # | Table | Role | Status |
|---|---|---|---|
| 1 | `users` | Authentication entry point. **Email + Google OAuth**, activation, polymorphic pointer. No domain data. | ✅ built |
| 2 | `students` | Student domain record. Owns `branch_id` — the platform's root filter. | ✅ built |
| 3 | `teachers` | Teacher domain record. Publicly readable persona. | ✅ built |
| 4 | `admins` | Administrator domain record. §7.1 resolved as **yes**. | ✅ built |
| — | ~~`phone_verification_codes`~~ | One-time SMS codes. **Withdrawn** — identity is email-based (§0-A, §3.5). | ❌ withdrawn |

### Cluster B — Academic Structure (4 domain · **4 built**)

| # | Table | Driven by | Status |
|---|---|---|---|
| 5 | `branches` | Screens 16, 22, 23, 36 · **the root filter** | ✅ built + seeded |
| 6 | `academic_terms` | Decision 5 · hard dependency of content and commerce | ✅ built + seeded |
| 7 | `subjects` | Screens 3, 18, 27, 37 | ✅ built + seeded (14 rows) |
| 8 | `subject_teacher` | Junction · M-10 (one teacher today, many-capable by design) | ✅ built |

### Cluster C — Learning Content (2 domain · **2 built**)

| # | Table | Driven by | Status |
|---|---|---|---|
| 9 | `lectures` | Screens 3, 4, 28, 30, 38 | ✅ built |
| 10 | `library_files` | Screens 19, 31, 39 | ✅ built |

### Cluster D — Assessment (6 domain)

| # | Table | Driven by |
|---|---|---|
| 12 | `quizzes` | Screens 5, 29 · 1:1 with lecture, mandatory at publish (M-3) |
| 13 | `questions` | Screen 29 · three question types |
| 14 | `question_options` | Screen 29 |
| 15 | `quiz_attempts` | Screens 6, 20, 32 |
| 16 | `attempt_answers` | Screen 6 · answer review |
| 17 | `attempt_answer_options` | Junction · exists only because multi-select exists |

### Cluster E — Progress (2 domain)

| # | Table | Driven by |
|---|---|---|
| 18 | `lecture_progress` | Screens 2, 4, 20 · **carries the server-verified closed-loop event** |
| 19 | `study_activity` | Screen 20 · streak tracker and weekly study chart |

### Cluster F — Commerce (6 domain)

| # | Table | Driven by |
|---|---|---|
| 20 | `plans` | Screens 7, 44 |
| 21 | `orders` | Screen 8 |
| 22 | `order_items` | Screens 7, 8 |
| 23 | `payments` | Screens 9, 10, 42 · manual proof and admin review (M-4) |
| 24 | `subscriptions` | Screens 11, 12, 41 · the access grant |
| 25 | `subscription_subjects` | Junction · per-subject entitlement |

### Cluster G — Communication (6 domain)

| # | Table | Driven by |
|---|---|---|
| 26 | `news` | Screens 24, 25, 40 |
| 27 | `news_categories` | Screen 24 · the segmented filter and kicker |
| 28 | `topics` | Screen 25 · sidebar topic list |
| 29 | `news_topic` | Junction |
| 30 | `notifications` | Screens 21, 33a · M-8 |
| 31 | `contact_messages` | `/contact` |

### Cluster H — System (2 domain · **1 built**)

| # | Table | Driven by | Status |
|---|---|---|---|
| 31 | `settings` | Screen 44 · settings groups, plus the M-2 platform defaults | 🔶 **built in reduced form** — see §3.32 |
| 32 | `activity_log` | Screens 26, 34, 47 · audit trail and deletion attribution | ⏳ |

### Cluster J — Landing content (7 domain · **7 built**) · **new in Revision 3**

Not in Revisions 1–2. The public landing page was built as static Blade with its content hard-coded in `@php` blocks — `$slides`, `$features`, and the rest. These tables move that content under administrator control without changing a single screen's markup contract.

| # | Table | Driven by | Status |
|---|---|---|---|
| 33 | `media` | Polymorphic asset store — **public marketing assets only** | ✅ built |
| 34 | `sliders` | Home hero carousel (was `$slides`) | ✅ built |
| 35 | `testimonials` | Home student-voices section | ✅ built |
| 36 | `steps` | Home *how to start* section | ✅ built |
| 37 | `features` | Home feature cards (was `$features`) | ✅ built |
| 38 | `faqs` | Home / support FAQ accordion | ✅ built |

> 🔴 **`media` is bounded by design, and the boundary is the whole point.** It serves **public marketing assets only** — slider artwork and testimonial portraits, files that are *meant* to be seen by anonymous visitors and live under `public/`.
>
> **Paid content never enters it.** `lectures` and `library_files` keep their own columns on the **private disk**, delivered through a controller that evaluates the access predicate first. Attaching either here would move paid content onto a public disk — the exact free leak that removes the reason to subscribe.
>
> **The guard is structural, not documentary:** `Lecture` and `LibraryFile` declare no morph relation to `Media`, so there is nothing to attach through. A test asserts the absence.

> **`steps` and `features` carry no media relation, and that absence is deliberate.** Both render from an `x-icon` component name, not an uploaded file. Declaring `morphMany` on them "just in case" is what later lets an image leak into a section whose layout was designed around a number and a line of text. A test asserts they have no `media()`, `image()` or `photo()` method.

### Cluster I — Permissions (5, package-supplied)

`roles` · `permissions` · `model_has_roles` · `model_has_permissions` · `role_has_permissions`. — ✅ **all 5 built**

Shipped by `spatie/laravel-permission` **8.3.0** and created by its published migration. They are not designed here, but their **integration** is (§3.5 and §4.3) because two decisions about them are ours to make: which model they attach to, and how they stay aligned with `userable_type`.

### Framework tables (6, not domain) — ✅ **all 6 built**

`sessions` · `cache` · `cache_locks` · `jobs` · `job_batches` · `failed_jobs`.

> **`password_reset_tokens` is RETAINED** (reversing Revision 2). It is keyed by email address — and in Revision 3 email **is** the identifier again, so the framework table is exactly the right mechanism for password reset. ✅ built.

## 1.3 Deliberately excluded — and why

| Excluded | Reason |
|---|---|
| `payment_methods`, `cards`, gateway tokens | **M-4 forbids it.** Payment is manual and off-platform. No card number, expiry, security code, cardholder name, or gateway token exists anywhere in this schema. A design invariant, not an omission. |
| `carts` | The plan calculator holds state in the DOM and is discarded on navigation. An order is created at checkout. |
| `coupons`, `discounts` | The branch-wide plan **is** the discount mechanism. A second one would collide with the four-subject break-even that makes the calculator's nudge a real mechanic. |
| A hand-rolled `roles` column or table | Superseded by decision 2. |
| `enrolments` | See §4.6 — access is derived, never stored. A membership table would become a second source of truth that keeps asserting access after a subscription lapses. |
| `certificates`, `forums`, `live_sessions`, `assignments` | Not in the 48-screen plan. Standard in a generic LMS; absent from this product. |
| `translations` | M-11 recommends system-UI-only translation. UI strings live in language files. Teacher content stays in its author's language. |
| A dedicated `trash` table | Soft deletes plus `deleted_by` cover it. One caveat for screen 47 in §4.8. |
| `watch_segments` as a table | Rejected in favour of a merged JSON column — see §3.18. One row per playback segment per student per lecture explodes in count and serves no query anyone will run. |

## 1.4 Conventions applied throughout

- **Primary key** — auto-incrementing unsigned big integer named `id`, unless stated otherwise.
- **Timestamps** — `created_at` and `updated_at` everywhere except pure junctions and append-only logs.
- **Soft-delete set** — nine tables carry `deleted_at` *and* `deleted_by`: `users`, `students`, `teachers`, `admins`, `branches`, `subjects`, `lectures`, `library_files`, `news`, `quizzes`. Elsewhere, deletion is hard or forbidden.
- **Column names are always English**, even where stored content is Arabic.
- **Money** is an integer count of the smallest unit (agora). Never floating point. Currency is a separate column defaulting to ILS.
- **Enumerated values** are short strings backed by an application enum, not native database enums — a native enum makes adding a value a table alteration.
- **Every foreign key declares an explicit deletion behaviour.** Content references *restrict*; ownership references *cascade*; actor references (`deleted_by`, `reviewed_by`, `causer_id`) *null out*, so an audit row survives the deletion of the person it names.
- **Phone numbers are stored split** — country calling code and national significant number in separate columns — never as one concatenated string. Uniqueness is composite across the pair.

---

# 2 · DEFINE

## Cluster A — Identity & Access

**`users`** — one row per set of credentials that can authenticate. It holds a phone number, a password hash, an activation flag, a locale preference, sign-in telemetry, and a polymorphic pointer to exactly one domain record. **Scope: authentication and account state only.** It carries no name, no role, no branch, and no biography. Anything a screen would *display* about a person lives on the domain table, not here.

**`students`** — the domain record for a learner: their name, their branch, their school, their guardian's contact. **Scope: academic identity.** This is where the platform's root filter physically lives, and `branch_id` is required here because every row is by definition a student.

**`teachers`** — the domain record for an instructor: name, title, biography, portrait, social links, public visibility. **Scope: presentation and content ownership, never authorisation.** Its rows are read by unauthenticated visitors on the branch page, which is precisely why it is separated from the credentials table.

**`admins`** — the domain record for an administrator. Minimal by design: a name and a portrait. **Scope: giving an administrator an identity to display.** Recommended rather than assumed — §7.1 explains what breaks without it.

**`phone_verification_codes`** — a short-lived, single-use code sent by SMS, for registration verification, password reset, and phone-number change. **Scope: proving control of a phone number.** Rows are keyed by phone number rather than by user, because during registration and recovery the code must exist before, or independently of, an authenticated session.

## Cluster B — Academic Structure

**`branches`** — the four secondary-school streams. The most consequential table in the schema: the root of personalised exclusion. A branch owns subjects; a student belongs to exactly one. Scope: academic segmentation plus the admin-editable public copy on the branch page.

**`academic_terms`** — a named, dated academic period. Now adopted explicitly (decision 5). Subscriptions and lectures bind to it strictly, which makes it a **hard dependency**: no lecture and no order can exist before a term does. Scope: the time window that content and commerce are both denominated in.

**`subjects`** — a taught course inside exactly one branch. Term-independent: the subject is stable, while what varies per term is which lectures exist and who has paid. Scope: the unit of both content grouping and commercial purchase, since M-1 makes a subject individually buyable.

**`subject_teacher`** — the assignment of teachers to subjects. A junction from day one even though M-10 specifies one teacher per subject today, because M-10 explicitly asks the table to accommodate expansion.

## Cluster C — Learning Content

**`lectures`** — one video lesson inside one subject, in one academic term, with its ordering, publication state, and video reference. Scope: the atomic unit of learning and the anchor of the closed loop. Owns at most one quiz.

**`library_files`** — a downloadable resource attached to a subject and optionally to a lecture. Scope: supplementary material. Deliberately separate from lectures: a file has no video, no quiz, no completion event, and different delivery semantics (a signed temporary URL rather than a verified stream).

## Cluster D — Assessment

**`quizzes`** — the single assessment bound to one lecture, now carrying **concrete** behaviour settings resolved by M-2. Scope: a container for questions and for the grading and unlock rules in force for this quiz.

**`questions`** — one question inside one quiz, with its type, text, explanation, point value, and display order.

**`question_options`** — one selectable answer, carrying its correctness flag. Correctness lives on the option rather than the question — which is what lets multi-select work without a second mechanism.

**`quiz_attempts`** — one student's one sitting of one quiz. Scope: an immutable historical record. Never edited after submission; a retake is a new row, capped at two by M-2.

**`attempt_answers`** — the student's response to one question within one attempt, with computed correctness and awarded points. Scope: the row that powers the review screen where the correct answer appears beside the wrong one.

**`attempt_answer_options`** — which specific options were selected. A separate table purely because multi-select exists.

## Cluster E — Progress

**`lecture_progress`** — one row per student per lecture. With M-5 resolved as server-authoritative, this table no longer merely records a number: it records **which parts of the video were actually watched**, when the last verified heartbeat arrived, and by what authority completion was granted. Scope: **this is where the closed-loop principle becomes trustworthy data.**

**`study_activity`** — a daily per-student rollup of minutes studied, lectures completed, and quizzes taken. A deliberate denormalisation serving the streak tracker and weekly chart. Scope: read-optimised aggregate, fully reconstructible from source.

## Cluster F — Commerce

**`plans`** — the definition of a purchasable offer and its current price. Two rows today. A table rather than configuration because screen 44 gives the admin a subscriptions tab.

**`orders`** — a commercial intent: this student, at this moment, wanted this access for this price, in this term. Scope: a historical financial record that is **never rewritten**. Prices are snapshotted so a later change cannot retroactively alter what a student was charged.

**`order_items`** — the individual lines: which subjects, at what unit price. An associative entity, not a pure junction.

**`payments`** — one manual payment declaration against one order: method, amount, transaction reference, receipt image, and the admin's verdict. Scope: **evidence and adjudication.** What the student claims and what the admin decided. No card data of any kind.

**`subscriptions`** — an active grant of access for one term. Created only when a payment is approved. Scope: the authoritative answer to "may this student open this subject right now?"

**`subscription_subjects`** — which subjects a per-subject subscription covers. Empty for branch-wide subscriptions, which derive coverage from the branch.

## Cluster G — Communication

**`news`** — one published article. Scope: editorial content, staff-authored, publicly readable.

**`news_categories`** — the mutually exclusive section an article belongs to: the kicker above the headline and the bucket in the segmented filter.

**`topics`** — free-form tags applied across articles. **Not the same as a category.** An article has exactly one category but many topics. Collapsing them breaks one of the two news screens.

**`news_topic`** — a pure junction, no attributes.

**`notifications`** — an addressed, read-trackable message. Polymorphic by recipient. Scope: the return-to-platform driver identified in M-8.

**`contact_messages`** — an inbound message from the public form, with handling status. Scope: inbox, not CRM.

## Cluster H — System

**`settings`** — grouped key/value configuration backing screen 44's four tabs, and now additionally holding the **platform defaults for M-2** (unlock threshold, attempt cap, scoring policy, gating flag). Scope: values an administrator may change without a deployment.

**`activity_log`** — an append-only record of who did what to which entity. Three screens depend on it, and it is the only durable answer to "who changed this?" once a soft-deleted row is purged at the 30-day mark.

---

# 3 · DESIGN — fields, types, constraints

Legend: **PK** primary key · **FK** foreign key · **U** unique · **N** nullable · **R** required · **IX** indexed · **UC** composite unique · **MI** morph index

## Cluster A — Identity & Access

### 3.1 `users` · ✅ **BUILT** — `0001_01_01_000000_create_users_table`

> 🔴 **Revised in Revision 3.** Identity is an **email address plus optional Google OAuth**, not a phone number. See §0-A.

| Field | Type / Purpose | Constraints | Built |
|---|---|---|---|
| `id` | Big integer, surrogate identity | PK | ✅ |
| `email` | String (190) — the identifier | R, **U** | ✅ |
| `email_verified_at` | Timestamp — when control of the address was proven | N | ✅ |
| `password` | String (255) — hash only. **Nullable**: a Google-registered account has none | N | ✅ |
| `google_id` | String (255) — Google subject identifier | N, **U** | ✅ |
| `google_avatar_url` | String (500) — provider-hosted portrait, fallback only | N | ✅ |
| `userable_type` | String (255) — domain model class | R, **MI** + **UC** with `userable_id` | ✅ |
| `userable_id` | Big integer — domain record identity | R | ✅ |
| `is_active` | Boolean — account enabled; screen 35's block toggle | R, default true, IX | ✅ |
| `locale` | String (5) — `ar` \| `en`, persisted UI language | R, default `ar` | ✅ |
| `last_login_at` | Timestamp | N | ✅ |
| `last_login_ip` | String (45) — IPv6-capable | N | ✅ |
| `remember_token` | String (100) — framework session persistence | N | ✅ |
| `created_at` / `updated_at` | Timestamps | R | ✅ |
| `deleted_at` | Soft delete marker | N, IX | ✅ (index added 2026-08-26) |
| `deleted_by` | Actor who deleted | FK → `users.id` (self), N, `nullOnDelete` | ✅ |

**`password` is nullable and that is a security decision, not a relaxation.** A Google-registered account has no password to store, and storing a synthetic one would create a credential nobody set and nobody can rotate. The consequence belongs in the application: any password-authentication path must treat a null hash as *this account does not use passwords*, never as *any password matches*.

**`userable_type` and `userable_id` are both required.** This is deliberate and it is the reason §7.1 recommends an `admins` table: if administrators had no domain record, this pair would have to be nullable, and every piece of code touching identity would need a null branch. Keeping the pair mandatory means *every* user row resolves to exactly one domain record, without exception.

> **Built stronger than specified: `users_userable_unique`.** A composite **unique** index on (`userable_type`, `userable_id`) — not in Revisions 1–2. It makes the polymorphic link genuinely one-to-one: two credential rows cannot claim the same domain record, so account sharing is impossible at the root rather than policed above it.

**On `locale` living here rather than on the domain tables.** Language is an account preference, not academic data, and `.claude/rules/rtl-bilingual.md` §5 already specifies a locale column on the users table. It also needs to be readable before the domain record is loaded, since the language switcher runs on every request including unauthenticated ones.

**`deleted_at` and `deleted_by` on `users` — built.** Without them, soft-deleting a student would leave a live credential row pointing at a deleted domain record: an account that can still authenticate into a void. Both are soft-deleted together in one transaction.

> **⚠️ Trap, unchanged in substance: soft delete plus a unique index locks the identifier.** It was the phone number in Revision 2; it is now the **email address**. A soft-deleted row retains its address, so that address cannot re-register until the 30-day purge runs. **Recommendation stands: accept the behaviour and give administrators a restore path** — for a student who deleted their account by mistake, restoration is the outcome they actually want, since progress, attempts and subscription return with it. Mutating the stored address on deletion is a tombstone hack that corrupts the audit trail to solve a rarer problem. Still open — §7.7.

### 3.2 `students` · ✅ **BUILT** — `2026_08_24_000001_create_students_table`

| Field | Type / Purpose | Constraints | Built |
|---|---|---|---|
| `id` | Big integer | PK | ✅ |
| `full_name` | String (160) — display name | R | ✅ |
| `branch_id` | Big integer — the student's branch | FK → `branches.id`, **R**, IX, `restrictOnDelete` | ✅ |
| `school_name` | String (160) | N | ✅ |
| `phone` | String (32) — **the student's own phone. Contact metadata, never a credential** (§0-A) | N | ✅ |
| `guardian_phone` | String (32) — guardian contact | N | ✅ |
| `governorate` | String (80) | N | ✅ |
| `country_code` | String (2) — ISO country of residence | N | ✅ |
| `avatar_path` | String (255) — private-disk path | N | ✅ |
| `created_at` / `updated_at` | Timestamps | R | ✅ |
| `deleted_at` | Soft delete marker | N, IX | ✅ (index added 2026-08-26) |
| `deleted_by` | Actor who deleted | FK → `users.id`, N, `nullOnDelete` | ✅ |

> **`phone` is new in Revision 3** and exists only because identity moved to email. Revision 2 had no student phone column — the phone *was* the identity, on `users`. It is collected by step 2 of the registration screen and is read-only contact data.
>
> **⚠️ Trap this column already sprang once.** The column existed, the form sent it, the controller validated and passed it — and it was silently discarded, because `Student::$fillable` did not list it. Eloquent drops unlisted keys with no error and no warning. Fixed 2026-08-26; regression locked by a test asserting every profile field reaches its column, not merely that the request was accepted.

> **`branch_id` is REQUIRED and now genuinely enforced.** `restrictOnDelete` means deleting a branch that still holds students fails loudly rather than orphaning or erasing them.

> **`branch_id` is REQUIRED, and this is the headline improvement of the whole revision.** In Revision 1 it had to be nullable on `users`, because teachers and administrators have no branch — which made the platform's first governing principle unenforceable by the database and dependent on a scheduled integrity scan. Here, every row is by definition a student, so the column is simply mandatory. **A student without a branch is now structurally impossible.**

> **Three recommended additions, each with a concrete driver.** `governorate` and `country_code` are collected by the already-built registration screen (step 2 has select fields named `country` and `governorate`) and have nowhere to be stored in the agreed specification — either they are added here, or those fields are removed from screen 14. `avatar_path` supports the student profile screen. `deleted_by` is required by both trash screens, which display who performed each deletion.

> **Note on `guardian_phone`:** it is in the agreed specification but the built registration screen does **not** collect it. Either step 2 gains a field, or it becomes profile-only and stays null at signup. §7.4.

### 3.3 `teachers` · ✅ **BUILT** — `2026_08_24_000002_create_teachers_table`

> Built exactly as specified. `deleted_at` gained its index on 2026-08-26.

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `full_name` | String (160) | R |
| `title` | String (120) — professional title shown under the name | N |
| `bio` | Text — biography on the public branch page | N |
| `avatar_path` | String (255) — portrait | N |
| `social_links` | JSON — platform-keyed URL map | N |
| `is_publicly_listed` | Boolean — appears on the public branch page — **recommended** | R, default false, IX |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` | Soft delete marker | N, IX |
| `deleted_by` | Actor who deleted — **recommended** | FK → `users.id`, N |

**`social_links` as JSON is the right call here:** a small, bounded map, always read and written whole with its teacher, never queried across teachers. A child table would add a join to serve a query nobody runs.

> **🔴 Security note on `social_links`.** It is user-supplied content rendered on a **public** page. Two rules apply at write time, not render time: validate the shape against a fixed allowlist of platform keys, and validate each URL against an allowlist of schemes. Storing an arbitrary string here and rendering it into a link attribute is a script-injection and open-redirect vector on the most publicly exposed surface of the platform.

**`is_publicly_listed` defaults to false.** Screen 33 warns the teacher that their profile appears publicly; a default of false makes that warning describe an opt-in rather than something that already happened.

### 3.4 `admins` · ✅ **BUILT** — `2026_08_24_000003_create_admins_table`

> **§7.1 resolved: option (A) adopted.** The table exists, so the polymorphic pair on `users` stays mandatory with no exceptions. `deleted_at` gained its index on 2026-08-26.

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `full_name` | String (160) | R |
| `avatar_path` | String (255) | N |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` / `deleted_by` | Soft delete marker and actor | N |

Deliberately minimal. Its entire purpose is to give an administrator a name and to keep the polymorphic pointer on `users` mandatory. See §7.1 for the decision and the alternative.

### 3.5 `phone_verification_codes` · ❌ **WITHDRAWN in Revision 3**

> **Not built, and not pending.** This table existed only to serve phone identity. With identity moved to email (§0-A), its two jobs are covered by the framework: registration verification is Laravel's signed email link, and password reset is the framework's email flow against the retained `password_reset_tokens` table.
>
> **The specification below is kept, not deleted.** If SMS verification is ever added — for phone-number confirmation on the profile screen, or as a second factor — this is the design to build, and invariant 14 (codes stored hashed, never plaintext) comes back with it. Reading it as pending work is the error to avoid.

<details>
<summary>Withdrawn specification — retained for a future SMS feature</summary>

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `phone_country_code` | String (5) | R, **IX** with `phone_number` |
| `phone_number` | String (20) | R |
| `code_hash` | String (255) — **hash of the code, never the code itself** | R |
| `purpose` | Short string — `registration` \| `password_reset` \| `phone_change` | R, IX |
| `expires_at` | Timestamp — short window, minutes not hours | R, IX |
| `consumed_at` | Timestamp — single-use marker | N |
| `attempt_count` | Small integer — wrong-code counter for lockout | R, default 0 |
| `ip_address` | String (45) | N |
| `created_at` | Timestamp — append-only | R |

Keyed by phone number rather than by user, because during registration no user exists yet, and during password reset the requester is by definition unauthenticated.

> **🔴 The code is stored hashed.** A one-time code is a credential for the duration of its life. Database read access must not be enough to take over an account. Three further rules belong in the application, not the schema, but are recorded here because omitting them makes the table actively dangerous: rate-limit issuance per number and per IP; lock the code after a small number of wrong attempts using `attempt_count`; and expire in minutes. Consumed and expired rows should be pruned on a schedule — this table grows quickly and holds nothing of long-term value.

</details>

## Cluster I — Permissions (Spatie integration) · ✅ **BUILT**

**Version 8.3.0.** Config and migration published; migration renumbered to `2026_08_24_000004_create_permission_tables` so a fresh install creates these tables immediately after the identity tables — phases 1 and 2 land together, as this document requires.

The package supplies these tables and their migration. What is designed here is the **integration decision**, not the table shapes.

| Table | Shape | Notes |
|---|---|---|
| `roles` | `id`, `name`, `guard_name`, timestamps · unique on (`name`, `guard_name`) | Three rows: `student`, `teacher`, `admin` |
| `permissions` | `id`, `name`, `guard_name`, timestamps · unique on (`name`, `guard_name`) | Granular capabilities |
| `model_has_roles` | `role_id`, `model_type`, `model_id` · composite PK · morph index | **Attached to `User` only** |
| `model_has_permissions` | `permission_id`, `model_type`, `model_id` · composite PK · morph index | Direct grants, bypassing roles |
| `role_has_permissions` | `permission_id`, `role_id` · composite PK | The role-to-permission map |

**Integration decision 1 — roles attach to `User`, never to `Student` or `Teacher`.** The agreed decision states this, and it is correct: the guard authenticates a `User`, so permission checks must resolve against the same model the guard returns. Attaching roles to domain models would split the check across two model types and require the resolution path to know which one it is holding before it can ask a permission question.

**Integration decision 2 — two discriminators now exist, and they must not drift.** This is the most important consequence of combining decisions 1 and 2, and it has no analogue in Revision 1:

| Discriminator | Authoritative for | Example question it answers |
|---|---|---|
| `users.userable_type` | **Identity and shape** | Which domain table holds this person's name and branch? |
| Spatie role | **Capability** | May this person approve a payment? |

They answer different questions and must never be used interchangeably. The binding rule: **a user whose `userable_type` is Student holds the `student` role and no other**, and the two are assigned together in a single transaction at account creation. Assigning one without the other produces an account that is structurally a student but has no permissions, or has permissions its domain record cannot support — and neither failure raises an error. A scheduled consistency check comparing the two is cheap and worth having.

**Integration decision 3 — three operational consequences of the package's polymorphic pivot.** `model_has_roles.model_id` carries no foreign key, because it is polymorphic. So: hard-deleting a user orphans its role rows and they must be cleaned up explicitly at purge; the package caches permissions, so that cache must be flushed whenever roles or permissions change; and role assignment cannot be relied upon to cascade on delete the way an ordinary foreign key would.

### How this is implemented · ✅ Revision 3

| Concern | Implementation |
|---|---|
| Roles attach to `User` only | `HasRoles` trait on `App\Models\User`. **Not** on `Student` / `Teacher` / `Admin` |
| The two discriminators cannot drift | `User::roleName()` derives the role name from `userable_type`. **One function, no second rule** — used by both account-creation paths and by the backfill migration |
| Role assigned atomically with the account | `User::syncRoleToShape()`, called **inside** the creation transaction in `RegisteredUserController::store()` and `GoogleController::completeRegistration()` |
| Roles exist before any account | `RoleSeeder` runs first in `DatabaseSeeder`; the backfill migration also `findOrCreate`s them, so a freshly migrated database always has the three |
| Package cache | `PermissionRegistrar::forgetCachedPermissions()` after seeding roles |
| Historic role-less accounts | Closed once by `2026_08_26_000001_backfill_user_roles`, which walks `withTrashed()` so soft-deleted accounts return from the trash with their role intact |

**Still owed:** granular `permissions` rows (deferred until the first Policy exists — permission names that nothing checks are worse than none), the scheduled drift check comparing `userable_type` against the assigned role, and explicit cleanup of `model_has_roles` rows in the 30-day purge job.

## Cluster B — Academic Structure

### 3.6 `branches` · ✅ **BUILT** — `2026_08_24_000000_create_branches_table`

> Built ahead of its phase, deliberately: `students.branch_id` is required, so no student can exist before a branch does. Seeded with the four branches by `BranchSeeder`.
>
> **Two omissions corrected on 2026-08-26** (`2026_08_26_000000_add_soft_delete_indexes_and_branch_actor_fk`): `deleted_at` had no index, and `deleted_by` — alone among the four soft-deleting tables — carried **no foreign key at all**, leaving invariant 21 unenforced on this table. Both fixed; the FK is `nullOnDelete`.

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `name` | String (120) — display name | R |
| `slug` | String (140) — URL segment | R, U, IX |
| `description` | Text — admin-editable blurb (screen 36) | N |
| `icon` | String (60) — icon component name, not a file path | N |
| `accent_token` | String (40) — design-token **name**, never a hex value | N |
| `display_order` | Small integer | R, default 0 |
| `is_active` | Boolean — accepts new students | R, default true |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` / `deleted_by` | Soft delete marker and actor | N |

`accent_token` stores a token *name*. A hex value in the database would sit outside the design system, violate the no-hex rule, and be invisible to any future palette change. There is a matching trap already recorded in `doctrine.md`: a token name read from the database must be mapped through a fixed lookup to a literal class name, never concatenated into one, because the build-time scanner does not execute PHP.

### 3.7 `academic_terms` · ✅ **BUILT** — `2026_08_26_000002_create_academic_terms_table`

| Field | Type / Purpose | Constraints | Built |
|---|---|---|---|
| `id` | Big integer | PK | ✅ |
| `name` | String (80) — e.g. first semester | R, **UC** with `academic_year` | ✅ |
| `academic_year` | String (9) — e.g. 2026/2027 | R, IX | ✅ |
| `starts_on` | Date | R | ✅ |
| `ends_on` | Date | R | ✅ |
| `status` | Short string — `upcoming` \| `active` \| `archived` — **added in Rev 3** | R, default `upcoming`, IX | ✅ |
| `is_current` | Boolean — the term new subscriptions default to | R, default false, IX | ✅ |
| `created_at` / `updated_at` | Timestamps | R | ✅ |

**Adopted (decision 5).** Exactly one row should carry `is_current` as true; enforce that in the application, since a partial unique index is not portable across databases.

> **`status` is new in Revision 3 and is not a duplicate of `is_current`.** They answer different questions and §7.6 needs both:
>
> | Column | Question | Cardinality |
> |---|---|---|
> | `status` | Is this term open for new orders? | Any number may be `active` |
> | `is_current` | Which term do new subscriptions default to? | Exactly one row |
>
> Under §7.5 option B (cumulative access, the recorded recommendation), term 1 stays `active` — students still open content they paid for — while term 2 carries `is_current` and sells. One boolean cannot express that. **Application invariant: `is_current = true` implies `status = active`**, enforced by `AcademicTerm::makeCurrent()`, which is the only writer of the flag: it clears the flag elsewhere and forces `status` to `active`, both in one transaction. Writing the column directly anywhere else reintroduces the window where two rows carry it or none does.

> **No `branch_id`, deliberately** — and this contradicts a natural reading of "terms belong to a branch". A term is a **calendar** unit, not an academic one: *first semester 2026/2027* is the same period for the scientific, literary, commercial and industrial branches. Adding `branch_id` would mean four identical rows per term and a new class of error — binding a lecture to the "wrong branch's" copy of the same semester. The branch reaches content through `subjects.branch_id`, which is the platform's single root filter; a second path to the same filter is how the two drift apart.

> **No soft delete.** A term is referenced by lectures, orders and subscriptions under `restrict`, so it cannot be deleted while anything remembers it. Closing a term is `status = archived`, not deletion — terms are historical records, not disposable configuration.

> **Operational dependency created by this decision.** Because lectures and orders now bind strictly to a term, **a term must exist before any lecture can be created or any order placed.** Two things follow: the very first seeder must create a term, and screen 44 needs an *open / close term* surface that the current specification does not mention. §7.6.

### 3.8 `subjects` · ✅ **BUILT** — `2026_08_26_000003_create_subjects_table`

> **Built to spec.** Added beyond it: a composite index on (`branch_id`, `is_published`, `display_order`) — the query behind every student-facing subject list — and the explicit `deleted_at` index. `price_override_agora` is an unsigned integer of agora; `null` means *use the plan rate*.
>
> **No `academic_term_id`, deliberately** — and this contradicts a natural reading of "subjects belong to a branch/term". A subject is **permanent**; the term binds to its *content* (`lectures.academic_term_id`), not to the subject itself. Putting the term here would force a fresh copy of every subject each term, and every reference to it — subscriptions, prices, teacher assignments, a student's history — would break at each rollover. Seeded with 14 subjects across the four branches by `SubjectSeeder`.

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `branch_id` | Big integer — owning branch | FK → `branches.id`, R, IX |
| `name` | String (140) | R |
| `slug` | String (160) | R, IX, **UC** with `branch_id` |
| `description` | Text — subject hero copy | N |
| `icon` | String (60) — icon component name | N |
| `accent_token` | String (40) — design-token name | N |
| `display_order` | Small integer | R, default 0 |
| `price_override_agora` | Integer — per-term price when this subject deviates from the plan rate | N |
| `is_published` | Boolean | R, default false, IX |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` / `deleted_by` | Soft delete marker and actor | N |

`slug` is unique **per branch**, not globally: two branches may both teach Mathematics, and they are different products at different prices.

### 3.9 `subject_teacher` — junction · ✅ **BUILT** — `2026_08_26_000004_create_subject_teacher_table`

> **Built to spec**, with a composite index on (`teacher_id`, `is_primary`) — *my subjects*, the first query on every teacher screen. Both `subject_id` and `teacher_id` cascade on delete: this row is a **relationship, not content**, and an assignment missing either side is meaningless. It is the only deliberate exception to the academic spine's restrict-everywhere rule, because deleting it destroys no content. Verified: deleting a teacher removes the assignment and leaves the subject intact.
>
> **Note the deletion asymmetry** — a teacher who owns lectures cannot be deleted at all (`lectures.teacher_id` restricts), so the cascade here only ever fires for a teacher with no content.

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `subject_id` | Big integer | FK → `subjects.id`, R, **UC** with `teacher_id` |
| `teacher_id` | Big integer — **now points at `teachers`, not `users`** | FK → `teachers.id`, R, IX |
| `is_primary` | Boolean — the public face of the subject | R, default true |
| `assigned_at` | Timestamp | R |
| `assigned_by` | Big integer — the admin who assigned — **an actor, so it points at `users`** | FK → `users.id`, N |

**This row demonstrates the new foreign-key rule in miniature.** `teacher_id` is a domain reference and points at `teachers`. `assigned_by` is an actor reference and points at `users`. The two columns look similar and mean entirely different things: one identifies *whose subject this is*, the other records *who made it so*.

The change from Revision 1 is not cosmetic. When this column pointed at `users`, nothing prevented a row assigning a **student** to teach a subject; only a policy stood in the way. Pointing it at `teachers` makes that row impossible to insert.

## Cluster C — Learning Content

### 3.10 `lectures` · ✅ **BUILT** — `2026_08_26_000005_create_lectures_table`

> **Built to spec.** All three foreign keys are required and all three restrict: a subject, term or teacher with lectures beneath it cannot be deleted. Verified by test.
>
> **Enum columns are short strings backed by PHP enums**, per §1.4 — `App\Enums\LectureStatus`, `VideoSource`, `VideoStatus`. Native database enums were avoided so adding a value is a code change, not a table alteration.
>
> **Indexes added beyond the field table:** (`subject_id`, `academic_term_id`, `status`, `display_order`) — the platform's most-repeated query, already sorted and term-scoped; (`teacher_id`, `status`) for the teacher scope; `video_status` for the processing queue; plus `published_at` and `deleted_at`.
>
> **`Lecture::isReadyToPublish()` is deliberately incomplete.** It checks `video_status`, `video_reference` and a non-zero `video_duration_seconds` — invariant 9. It does **not** yet check M-3 (a quiz with at least one question), because `quizzes` does not exist. The gap is marked in the method's docblock; do not treat it as a finished publish gate until phase 5 lands.

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `subject_id` | Big integer — owning subject | FK → `subjects.id`, R, IX |
| `academic_term_id` | Big integer — **now required** (decision 5) | FK → `academic_terms.id`, **R**, IX |
| `teacher_id` | Big integer — authoring teacher | FK → `teachers.id`, R, IX |
| `title` | String (200) | R |
| `slug` | String (220) | R, **UC** with `subject_id` |
| `description` | Text | N |
| `display_order` | Integer — position within the subject | R, default 0, IX |
| `video_source` | Short string — `local_private` \| `bunny` \| `vimeo` \| `external` | R |
| `video_reference` | String (500) — private-disk path **or** provider asset identifier | N |
| `video_duration_seconds` | Integer — **authoritative duration, server-obtained** | N |
| `video_status` | Short string — `pending` \| `processing` \| `ready` \| `failed` | R, default `pending` |
| `thumbnail_path` | String (255) — private-disk path | N |
| `status` | Short string — `draft` \| `published` \| `hidden` | R, IX |
| `published_at` | Timestamp | N, IX |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` / `deleted_by` | Soft delete marker and actor | N |

**`teacher_id` replaces Revision 1's `created_by`,** and the rename is meaningful: it is ownership, not authorship metadata. It is what the teacher scope rule — *a teacher sees only their own subjects* — filters on, and pointing it at `teachers` makes a student-authored lecture structurally impossible.

**`video_duration_seconds` is now load-bearing, not informational.** M-5 makes completion server-verified against a 90% threshold, and a percentage of an unknown total cannot be computed. **Recommendation: a lecture cannot be published until this value is present and server-obtained** — from the provider's API for a hosted asset, or from server-side inspection for a local file. A duration supplied by the client is exactly the number an attacker would want to control, since understating it makes 90% arrive early.

`video_reference` stays nullable because a draft legitimately exists before upload. `status` and `published_at` remain separate so a hidden lecture retains its original publication date.

### 3.11 `library_files` · ✅ **BUILT** — `2026_08_26_000006_create_library_files_table`

> **Built to spec, plus one column: `uploaded_by` → `users`, nullable, `nullOnDelete`.** The two person-columns are not redundant — they are §4.4's rule applied twice:
>
> | Column | Kind | Points at | Answers |
> |---|---|---|---|
> | `teacher_id` | domain reference, required | `teachers` | Whose file is this? (the teacher scope filters on it) |
> | `uploaded_by` | actor reference, nullable | `users` | Who pressed upload? (may be an admin acting for the teacher) |
>
> Without `uploaded_by`, an admin uploading on a teacher's behalf either has nowhere to be recorded or forces `teacher_id` to point at a non-teacher — which would break invariant 6.
>
> **`storage_path` is in the model's `$hidden`.** Leaking the private-disk path in any JSON, response or Livewire payload hands an attacker half the route to paid content. `download_count` is moved only by `incrementQuietly()` — a download is not an edit and must not touch `updated_at`.
>
> **`lecture_id` is the only `nullOnDelete` here, and that is the point:** deleting a lecture must not destroy its attachments. The file loses its lecture and falls back to subject level, which is why `subject_id` is required and `lecture_id` is not — the file always has a home. Verified by test.

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `subject_id` | Big integer | FK → `subjects.id`, R, IX |
| `lecture_id` | Big integer — optional attachment | FK → `lectures.id`, N, IX |
| `teacher_id` | Big integer — uploader | FK → `teachers.id`, R, IX |
| `title` | String (200) | R |
| `original_filename` | String (255) — as the teacher named it | R |
| `storage_path` | String (500) — **private disk only** | R |
| `mime_type` | String (120) | R |
| `size_bytes` | Big integer | R |
| `download_count` | Integer | R, default 0 |
| `is_published` | Boolean | R, default false, IX |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` / `deleted_by` | Soft delete marker and actor | N |

`storage_path` points at the private disk without exception. A directly reachable URL to paid content is a free leak that removes the reason to subscribe. Delivery is by short-lived signed URL through a controller that evaluates the access predicate first.

## Cluster D — Assessment

### 3.12 `quizzes` — M-2 now concrete

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `lecture_id` | Big integer | FK → `lectures.id`, R, **U** |
| `title` | String (200) | N |
| `pass_score_percent` | Small integer — threshold, 0–100 | R, default 60 |
| `unlock_threshold_percent` | Small integer — **90** (M-2) | **R**, default 90 |
| `max_attempts` | Small integer — **2** (M-2) | **R**, default 2 |
| `scoring_policy` | Short string — **`highest`** (M-2) | **R**, default `highest` |
| `gates_next_lecture` | Boolean — **false** (M-2) | **R**, default false |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` / `deleted_by` | Soft delete marker and actor | N |

**All four M-2 columns are now required with concrete defaults.** In Revision 1 they were nullable placeholders awaiting a decision.

> **Design recommendation: snapshot the platform defaults onto each quiz at creation.** The four values live in `settings` as platform-wide defaults and are **copied onto the quiz row when it is created**, rather than being read live at grading time. This costs four settings rows and gains one important property: changing the platform policy affects **new** quizzes only, never quizzes students are already sitting.
>
> The alternative — reading live from settings — produces two concrete failures. A student who has used both attempts finds themselves over the limit if the cap drops to one. A student sitting at 85% watched has their quiz re-lock if the threshold rises to 95%. Both are silent, both look like bugs to the student, and both are avoided by snapshotting.

**Uniqueness on `lecture_id` is what enforces 1:1 in the database.** It guarantees *at most one* quiz per lecture. It cannot guarantee *at least one*, and should not try — a draft lecture legitimately has none. The M-3 rule is therefore two-layered: the database prevents a second quiz, and the application blocks the draft-to-published transition until a quiz with at least one question exists.

**With `max_attempts` fixed at 2 and `scoring_policy` at `highest`, no denormalised best-attempt column is needed.** Selecting the higher of two rows is trivial. Revisit only if the attempt cap ever rises substantially.

### 3.13 `questions`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `quiz_id` | Big integer | FK → `quizzes.id`, R, IX |
| `type` | Short string — `single_choice` \| `multiple_choice` \| `true_false` | R |
| `text` | Text — may contain mixed-direction content | R |
| `explanation` | Text — shown beside the correct answer on the result screen | N |
| `points` | Small integer | R, default 1 |
| `display_order` | Small integer | R, default 0, IX |
| `created_at` / `updated_at` | Timestamps | R |

`true_false` is a distinct type rather than a two-option single-choice, because the builder presents it as its own affordance and the renderer must know without counting options. Its options are still stored as ordinary rows, so grading keeps one code path.

`explanation` is not decoration. The rule that *red clarifies, it does not scold* depends on the correct answer appearing beside the wrong one immediately — this column carries the teaching.

### 3.14 `question_options`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `question_id` | Big integer | FK → `questions.id`, R, IX |
| `text` | String (500) | R |
| `is_correct` | Boolean | R, default false |
| `display_order` | Small integer | R, default 0 |
| `created_at` / `updated_at` | Timestamps | R |

Correctness lives on the option, not the question. That single choice is what lets multi-select work without a parallel mechanism: a multiple-choice question is simply one with more than one correct option.

### 3.15 `quiz_attempts`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `quiz_id` | Big integer | FK → `quizzes.id`, R, IX |
| `student_id` | Big integer — **now points at `students`** | FK → `students.id`, R, IX |
| `attempt_number` | Small integer — 1 or 2 under M-2 | R, **UC** with `quiz_id` + `student_id` |
| `started_at` | Timestamp | R |
| `submitted_at` | Timestamp — null while in progress | N |
| `score_points` | Integer | N |
| `max_points` | Integer — **snapshotted at sitting** | N |
| `score_percent` | Small integer — derived, stored for cheap sorting | N |
| `is_passed` | Boolean | N |
| `created_at` / `updated_at` | Timestamps | R |

`max_points` is snapshotted rather than read live. If a teacher edits the quiz afterwards, a historical attempt must still report the score the student achieved out of the total that existed at the time. Without this column, editing a quiz silently rewrites history on the student performance screen.

The composite uniqueness makes a duplicate submission structurally impossible — which matters on the unreliable mobile connections most of this audience uses.

**The M-2 cap of two attempts is enforced in the application, not the database.** `attempt_number` cannot be constrained against `quizzes.max_attempts` from a column definition. The check belongs at attempt creation, and the uniqueness constraint above is what stops a race from producing two rows numbered 3.

### 3.16 `attempt_answers`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `attempt_id` | Big integer | FK → `quiz_attempts.id`, R, **UC** with `question_id` |
| `question_id` | Big integer | FK → `questions.id`, R, IX |
| `is_correct` | Boolean — computed at submission | R |
| `points_awarded` | Small integer | R, default 0 |
| `answered_at` | Timestamp | N |

One row per question per attempt. Unanswered questions still get a row with zero points, so the review screen can distinguish *skipped* from *absent*.

### 3.17 `attempt_answer_options` — pure junction

| Field | Type / Purpose | Constraints |
|---|---|---|
| `attempt_answer_id` | Big integer | FK → `attempt_answers.id`, R, **composite PK** |
| `question_option_id` | Big integer | FK → `question_options.id`, R, IX |

No payload, no surrogate key. One row for a single-choice answer, several for a multi-select. The only table in the schema whose existence is owed entirely to one question type.

## Cluster E — Progress

### 3.18 `lecture_progress` — M-5 now server-authoritative

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `student_id` | Big integer — **now points at `students`** | FK → `students.id`, R, **UC** with `lecture_id` |
| `lecture_id` | Big integer | FK → `lectures.id`, R, IX |
| `last_position_seconds` | Integer — resume point | R, default 0 |
| `watched_intervals` | JSON — **merged, non-overlapping [start, end] coverage** | N |
| `watched_seconds` | Integer — derived from the union of intervals | R, default 0 |
| `watched_percent` | Small integer — derived against the lecture duration | R, default 0, IX |
| `last_heartbeat_at` | Timestamp — **the anti-fraud anchor** | N |
| `first_started_at` | Timestamp | N |
| `completed_at` | Timestamp — **the closed-loop event** | N, IX |
| `completion_source` | Short string — `server_verified` \| `admin_override` | N |
| `quiz_unlocked_at` | Timestamp | N |
| `created_at` / `updated_at` | Timestamps | R |

> **This table is where M-5 stops being a hosting question and becomes a data structure.**
>
> Revision 1 stored a single `watched_seconds` counter. That was adequate while completion was a display behaviour; it is **not** adequate now that completion is authoritative, because a counter cannot distinguish watching from seeking. A student who drags the scrubber to the end produces the same maximum position as one who watched the whole lecture.
>
> **`watched_intervals` records which parts of the video were actually covered**, as a merged set of non-overlapping ranges maintained server-side. The 90% threshold is measured against the union of those intervals, not against the furthest position reached. Seeking forward moves the playhead; it does not create coverage.
>
> **`last_heartbeat_at` bounds how much any single report may claim.** A heartbeat may extend coverage by at most the wall-clock time elapsed since the previous one, plus a small tolerance. This is what stops a fabricated report from claiming forty minutes of coverage two seconds after the last one. Without it, interval tracking is merely a more elaborate thing to lie about.
>
> **`completion_source` distinguishes a verified completion from an administrative unlock.** Both are legitimate; conflating them destroys the audit trail precisely where it matters most, since an override is the one path that bypasses the platform's central mechanic.

**Why JSON rather than a `watch_segments` table.** Intervals are read and written as a whole set on every heartbeat, merged in one operation, and never queried individually or across students. A child table would produce one row per playback segment per student per lecture — the highest-cardinality table in the schema by a wide margin — to serve no query anyone will ever run. The merge keeps the JSON array small: contiguous viewing collapses to a single interval.

**`quiz_unlocked_at` is recorded rather than inferred** so that an administrative unlock or a future policy change leaves a trace instead of silently contradicting the completion rule.

### 3.19 `study_activity`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `student_id` | Big integer | FK → `students.id`, R, **UC** with `activity_date` |
| `activity_date` | Date — one row per student per day | R, IX |
| `minutes_studied` | Small integer | R, default 0 |
| `lectures_completed` | Small integer | R, default 0 |
| `quizzes_taken` | Small integer | R, default 0 |
| `created_at` / `updated_at` | Timestamps | R |

A read-optimised rollup, fully reconstructible from `lecture_progress` and `quiz_attempts`. It exists because screen 20 renders a consecutive-day streak and a weekly chart on every load, and deriving both from raw rows means scanning a student's whole history each time.

**Because it is derived, it must have a rebuild path** — a maintenance routine that recomputes a date range from source. A denormalisation without a rebuild path is a future data-integrity incident. With M-5 now server-verified, this table has a second reason to be reconstructible: if a fraud rule is ever tightened retroactively, the aggregates must be recomputable from the corrected intervals.

## Cluster F — Commerce

### 3.20 `plans`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `code` | Short string — `per_subject` \| `branch_full` | R, U |
| `name` | String (120) | R |
| `type` | Short string — drives entitlement logic | R |
| `price_agora` | Integer — 3500 and 12000 today | R |
| `currency` | String (3) | R, default ILS |
| `billing_period` | Short string — `term` | R |
| `is_active` | Boolean | R, default true |
| `display_order` | Small integer | R, default 0 |
| `created_at` / `updated_at` | Timestamps | R |

Money as integer agora: 35 ₪ is 3500. The break-even at four subjects (4 × 3500 = 14000 > 12000) is an arithmetic property of these two rows, and it is what makes the calculator's *switch to the branch pass* prompt a genuine mechanic rather than decoration. **Anyone editing these two numbers through screen 44 is editing that mechanic** — worth a warning in the admin interface.

### 3.21 `orders`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `reference` | String (20) — human-quotable, shown on screen 10 | R, U, IX |
| `student_id` | Big integer — **now points at `students`** | FK → `students.id`, R, IX |
| `academic_term_id` | Big integer — **required** | FK → `academic_terms.id`, R, IX |
| `plan_id` | Big integer | FK → `plans.id`, R |
| `plan_type` | Short string — snapshot | R |
| `branch_id` | Big integer — for a branch-wide order | FK → `branches.id`, N |
| `subtotal_agora` | Integer | R |
| `total_agora` | Integer | R |
| `currency` | String (3) | R, default ILS |
| `status` | `awaiting_payment` \| `under_review` \| `approved` \| `rejected` \| `cancelled` \| `expired` | R, IX |
| `placed_at` | Timestamp | R |
| `created_at` / `updated_at` | Timestamps | R |

`plan_type`, `subtotal_agora` and `total_agora` are **snapshots**. An order is a financial record; a later price change must never retroactively alter what a student was told they owed.

`status` includes `expired` deliberately: manual payment means an order can be created and never paid, and those must be distinguishable from ones a student actively cancelled.

### 3.22 `order_items`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `order_id` | Big integer | FK → `orders.id`, R, **UC** with `subject_id` |
| `subject_id` | Big integer | FK → `subjects.id`, R, IX |
| `unit_price_agora` | Integer — price snapshot | R |
| `subject_name_snapshot` | String (140) — name at purchase | R |

An **associative entity**, not a pure junction. `subject_name_snapshot` keeps a receipt readable after a subject is renamed or soft-deleted. Populated only for per-subject orders.

### 3.23 `payments`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `order_id` | Big integer | FK → `orders.id`, R, IX |
| `method` | `bank_transfer` \| `e_wallet` \| `agent` \| `cash` | R, IX |
| `amount_agora` | Integer — what the student declares having paid | R |
| `currency` | String (3) | R, default ILS |
| `sender_name` | String (160) — name on the transfer | N |
| `transaction_reference` | String (120) — student-supplied | N |
| `paid_at` | Timestamp — when the student says it happened | N |
| `proof_path` | String (500) — receipt image, **private disk** | N |
| `proof_mime_type` | String (120) | N |
| `status` | `submitted` \| `approved` \| `rejected` | R, IX |
| `reviewed_by` | Big integer — **an actor, so it points at `users`** | FK → `users.id`, N |
| `reviewed_at` | Timestamp | N |
| `rejection_reason` | Text — shown back to the student | N |
| `created_at` / `updated_at` | Timestamps | R |

> **🔴 Invariant.** No column in this table — or anywhere in this schema — holds a card number, expiry date, security code, cardholder name, or gateway token. M-4 places payment entirely outside the platform. This table records a *claim* and an *adjudication*. Any future request to store a card is a change to M-4, not a schema addition.

`proof_path` is on the private disk for an obvious reason: a receipt is a financial document carrying a real person's name and bank details, and a guessable public URL would expose it.

`amount_agora` is separate from the order total because in manual payment they genuinely differ — students underpay, overpay, and round. Screen 42 needs both to decide.

`sender_name` exists because in practice a parent or relative sends the transfer. Without it, an administrator cannot match a bank line to a student and rejects a valid payment.

### 3.24 `subscriptions`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `student_id` | Big integer — **now points at `students`** | FK → `students.id`, R, IX |
| `order_id` | Big integer — the order that produced the grant | FK → `orders.id`, N, U |
| `academic_term_id` | Big integer — **required** | FK → `academic_terms.id`, R, IX |
| `type` | `per_subject` \| `branch_full` | R, IX |
| `branch_id` | Big integer — required for branch-wide | FK → `branches.id`, N, IX |
| `starts_at` | Timestamp | R, IX |
| `ends_at` | Timestamp | R, IX |
| `status` | `active` \| `expired` \| `cancelled` \| `suspended` | R, IX |
| `activated_by` | Big integer — **an actor, so it points at `users`** | FK → `users.id`, N |
| `cancelled_at` | Timestamp | N |
| `cancellation_reason` | Text | N |
| `created_at` / `updated_at` | Timestamps | R |

**A subscription exists only after a payment is approved.** Keeping it separate from `orders` is the structural guarantee that a pending or rejected order grants nothing: there is no status value on an order that could be misread as access, because access is not an order concept at all.

`order_id` is nullable and unique — nullable so an administrator can grant a complimentary or compensatory subscription with no commercial event behind it, unique so one order can never be redeemed twice.

`status` is stored rather than derived purely from dates, because suspension and cancellation are states dates cannot express. It must be reconciled against `ends_at` by a scheduled job; treating a stored status as authoritative without that job is how expired subscriptions silently keep working.

### 3.25 `subscription_subjects` — pure junction

| Field | Type / Purpose | Constraints |
|---|---|---|
| `subscription_id` | Big integer | FK → `subscriptions.id`, R, **composite PK** |
| `subject_id` | Big integer | FK → `subjects.id`, R, IX |

Populated only for per-subject subscriptions. A branch-wide subscription leaves it empty and derives coverage from `branch_id`. Not an optimisation — it is what makes the pricing page's promise that *new subjects are included in the branch pass* automatic rather than dependent on someone remembering to backfill rows.

## Cluster G — Communication

### 3.26 `news`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `category_id` | Big integer — the kicker and filter bucket | FK → `news_categories.id`, N, IX |
| `author_id` | Big integer — **an actor; may be an admin or a teacher, so it points at `users`** | FK → `users.id`, N, IX |
| `title` | String (220) | R |
| `slug` | String (240) | R, U, IX |
| `excerpt` | Text | N |
| `body` | Long text | R |
| `key_points` | JSON — the highlights box on screen 25 | N |
| `cover_image_path` | String (500) | N |
| `cover_alt_text` | String (255) | N |
| `reading_minutes` | Small integer | N |
| `views_count` | Integer — drives the most-read widget | R, default 0, IX |
| `status` | `draft` \| `published` \| `archived` | R, IX |
| `published_at` | Timestamp | N, IX |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` / `deleted_by` | Soft delete marker and actor | N |

`author_id` is one of the clearest illustrations of the new rule: an article may be written by a teacher **or** an administrator, so it cannot point at either domain table. It points at `users`.

`key_points` as JSON: an ordered list of short strings, always read and written whole with its article, never queried across articles.

`views_count` is a denormalised counter incremented on read, and it is acceptable for it to be approximate — it orders a sidebar widget, it does not bill anyone.

### 3.27 `news_categories`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `name` | String (80) — kicker text | R |
| `slug` | String (100) | R, U |
| `display_order` | Small integer | R, default 0 |
| `created_at` / `updated_at` | Timestamps | R |

### 3.28 `topics`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `name` | String (80) | R |
| `slug` | String (100) | R, U |
| `created_at` / `updated_at` | Timestamps | R |

**Categories and topics are not the same thing, and merging them breaks a screen.** A category is exclusive — one kicker, one bucket in the segmented filter on screen 24. A topic is inclusive — an article carries several, listed in the sidebar of screen 25. One is a 1:N parent, the other an N:M tag.

### 3.29 `news_topic` — pure junction

| Field | Type / Purpose | Constraints |
|---|---|---|
| `news_id` | Big integer | FK → `news.id`, R, **composite PK** |
| `topic_id` | Big integer | FK → `topics.id`, R, IX |

### 3.30 `notifications`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | UUID | PK |
| `type` | String (255) — notification class | R |
| `notifiable_type` | String (255) — **`User`** | R, **MI** with `notifiable_id` |
| `notifiable_id` | Big integer | R |
| `data` | JSON — title, body, target link, icon | R |
| `read_at` | Timestamp | N, IX |
| `created_at` / `updated_at` | Timestamps | R |

**Notifications address the `User`, not the domain record.** A notification is delivered to an *account* — it appears in the top bar of whoever is signed in, and it is read state on that session. Addressing the domain record would fragment the unread-count query across two model types for no gain.

The composite morph index is what keeps that unread-count query cheap, and it runs on **every authenticated page load** — so it is not optional.

### 3.31 `contact_messages`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `name` | String (160) | R |
| `email` | String (190) — **contact data from a public form, not an identity** | R |
| `phone` | String (32) | N |
| `subject` | String (200) | N |
| `message` | Text | R |
| `status` | `new` \| `read` \| `replied` \| `spam` | R, default `new`, IX |
| `handled_by` | Big integer — an actor | FK → `users.id`, N |
| `handled_at` | Timestamp | N |
| `ip_address` | String (45) | N |
| `created_at` / `updated_at` | Timestamps | R |

This table retains an `email` column, and that is not an inconsistency with phone-based authentication. A message from an anonymous visitor needs a reply address; it is contact data, never a credential, and it authenticates nothing.

## Cluster H — System

### 3.32 `settings` · 🔶 **BUILT IN REDUCED FORM** — `2026_08_26_000013_create_settings_table`

> **What exists today:** `id` · `key` (120, **unique**) · `value` (text, nullable) · timestamps. Nothing else. Grouping is carried inside the key by dotted naming — `site.name`, `contact.phone`, `social.facebook` — so lookup stays a single-column match.
>
> **What is missing, and why it matters.** The specification below adds `group`, `value_type`, `is_public` and `updated_by`. The consequential one is **`is_public`**: without it the database cannot distinguish *safe for the public footer* from *private*, so a careless query returns everything.
>
> 🔴 **Operating rule until `is_public` exists: store only values an anonymous visitor may see.** The M-2 assessment defaults — unlock threshold, attempt cap, scoring policy, gating flag — **must not** be stored in this table yet. `value_type` is the second gap: every value comes back a string, so callers cast by hand.
>
> **Reconciliation is deferred to phase 10:** either extend this table to the shape below, or rewrite this section to match what shipped. It must not be left as two conflicting descriptions of one table.

<details>
<summary>Full phase-10 specification — the target shape</summary>

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `group` | `general` \| `subscriptions` \| `notifications` \| `languages` \| **`assessment`** | R, **UC** with `key` |
| `key` | String (120) | R |
| `value` | Text — serialised | N |
| `value_type` | `string` \| `integer` \| `boolean` \| `json` | R |
| `is_public` | Boolean — safe to expose to unauthenticated pages | R, default false |
| `updated_by` | Big integer — an actor | FK → `users.id`, N |
| `created_at` / `updated_at` | Timestamps | R |

**A fifth group, `assessment`, is added by M-2.** It holds the four platform defaults — unlock threshold, attempt cap, scoring policy, gating flag — which are snapshotted onto each new quiz at creation (§3.12). Screen 44 currently specifies four tabs; this either becomes a fifth or folds into the general tab. §7.6.

`is_public` matters: the platform name and contact details are read by the public footer while assessment thresholds are not, and one flag keeps a careless query from leaking the second set.

</details>

---

## Cluster J — Landing content · ✅ **ALL BUILT** 2026-08-26

Six tables that move the public landing page's hard-coded `@php` arrays under administrator control. Every one carries `display_order`, `is_active`, soft deletes and an explicit `deleted_at` index; each is queried the same way — `active()->ordered()`.

### 3.34 `media` — polymorphic asset store · `2026_08_26_000007`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `path` | String (500) — **relative** path inside the public disk, never an absolute URL | R |
| `type` | Short string — `image` \| `video` \| `document`, backed by `App\Enums\MediaType` | R, default `image` |
| `alt_text` | String (200) — accessible description | N |
| `display_order` | Small integer — which asset wins when a parent has several | R, default 0 |
| `mediable_type` / `mediable_id` | Polymorphic owner | R, **MI** on (`mediable_type`, `mediable_id`) |
| `created_at` / `updated_at` | Timestamps | R |
| `deleted_at` | Soft delete marker | N, IX |

**`path` is relative, not a URL.** Adding a CDN or changing the domain must not require rewriting every row; `Media::url()` composes the absolute address at read time.

**Attached to `Slider` and `Testimonial` only.** No other model declares a morph relation to it — see the Cluster J note in §1.2 for why that boundary is the security control.

> ⚠️ **`mediable_id` carries no foreign key** — polymorphic columns cannot. Two consequences, both identical to the Spatie pivot's (§3.5): hard-deleting a parent **orphans its media rows**, so the 30-day purge job must clean them explicitly; and no cascade can be relied on. Soft-deleting a parent leaves its media intact, which is what makes restore-from-trash return the slider *with its artwork*.

> **No index on (`mediable_type`, `mediable_id`, `display_order`).** Its leading prefix is the `morphs()` index itself, so keeping both would cost every write twice to save a filesort over the handful of rows one parent owns.

### 3.35 `sliders` · `2026_08_26_000008`

`badge_text` (120, N) · `title` (200, R) · `sub_title` (300, N) · `btn1_text`/`btn1_link` · `btn2_text`/`btn2_link` (all N) · `display_order` · `is_active` · timestamps · soft deletes.

Two buttons, because a hero carries a primary call (*start now*) and a secondary one (*browse branches*). **The one-primary-button-per-screen rule still holds** — `btn1` renders primary and `btn2` secondary; they are not two primaries. `Slider::hasPrimaryAction()` requires *both* text and link, since text without a link renders a dead button.

Artwork comes from `media`, not a column — which is what lets one slide carry a desktop and a mobile asset without a migration.

### 3.36 `testimonials` · `2026_08_26_000009`

`name` (160, R) · `title` (160, N) · `rate` (**unsigned tiny integer**, R, default 5) · `description` (text, R) · `display_order` · `is_active` · timestamps · soft deletes.

**`rate` is an integer, not a float.** The rating is a choice among five buttons, not a continuous measurement, and float comparison is inexact — `rate = 4.5` may not match itself. The 1–5 range is an application rule: the column accepts up to 255, so `Testimonial::stars()` clamps on read rather than trusting a display loop to print 255 stars.

**No foreign key to `students`, deliberately.** A testimonial is a published marketing **snapshot**. Linking it to a live account means a student deleting their account tears a section out of the home page — or worse, reveals that a named person subscribes.

### 3.37 `steps` · `2026_08_26_000010` — **text-only**

`title` (200, R) · `sub_title` (300, N) · `icon` (60, N — an `x-icon` component name, never a file path) · `display_order` · `is_active` · timestamps · soft deletes. **No media relation.**

`display_order` here is **semantic, not cosmetic**: *register → choose your branch → pay → start* stops making sense out of order, unlike a shufflable list of features.

### 3.38 `features` · `2026_08_26_000011` — **text-only**

`title` (200, R) · `description` (text, N) · `btn_text`/`btn_link` (N) · `icon` (60, N) · `accent_token` (40, N) · `display_order` · `is_active` · timestamps · soft deletes. **No media relation.**

One optional button, not two: a feature card is a secondary call inside a page that already has a primary one.

> 🔴 **`accent_token` stores a token *name*.** It must be mapped through a fixed PHP array to a **literally written** Tailwind class. String-building — `"bg-{$tone}/14"` — generates no CSS, because the build-time scanner does not execute PHP. This trap has already fired twice in this project.

### 3.39 `faqs` · `2026_08_26_000012`

`question` (300, R) · `answer` (**text**, R) · `display_order` · `is_active` · timestamps · soft deletes.

`answer` is text, not a short string: a useful answer is a paragraph, and truncating the column forces an abbreviation that defeats the answer's purpose.

### 3.40 `branches` — modified · `2026_08_26_000014`

Adds `btn_text` (80, N) and `btn_link` (500, N). **An `ALTER`, not a `CREATE`** — the table has been live since 2026-08-24 with four seeded branches and students pointing at it under `restrict`. Both columns are nullable because the existing rows have no button, and `null` means *use the card's default*, not *an empty button*. `Branch::$fillable` was extended in the same batch — migration and model always move together.

### 3.33 `activity_log`

| Field | Type / Purpose | Constraints |
|---|---|---|
| `id` | Big integer | PK |
| `causer_id` | Big integer — who acted, **always a `users` reference** | FK → `users.id`, N, IX |
| `action` | `created` \| `updated` \| `deleted` \| `restored` \| `approved` \| `rejected` \| `logged_in` | R, IX |
| `subject_type` | String (255) — affected model | R, **MI** with `subject_id` |
| `subject_id` | Big integer | R |
| `description` | String (255) | N |
| `properties` | JSON — before/after values where relevant | N |
| `ip_address` | String (45) | N |
| `created_at` | Timestamp — append-only, no `updated_at` | R, IX |

Append-only and polymorphic. Three screens depend on it, and it is the only durable answer to *who changed this?* once a soft-deleted row is purged at the 30-day mark. It should be pruned on its own, longer schedule — and that retention window is a decision for you, not a default for me. §7.8.

---

# 4 · DELIVER — relationships

## 4.1 Relationship inventory

| Type | Count | Where |
|---|---|---|
| **Polymorphic One-to-One** | 1 | `users` ↔ {`students`, `teachers`, `admins`} — **the identity spine** |
| One-to-One | 1 | `lectures` ↔ `quizzes` |
| One-to-Many | 28 | §4.4 |
| Many-to-Many | 5 | §4.5 |
| Polymorphic Many-to-Many | 2 | Spatie role and permission assignment |
| Polymorphic One-to-Many | 2 | `notifications`, `activity_log` |
| Self-referencing | 1 | `users.deleted_by` → `users` |

## 4.2 The identity spine — polymorphic one-to-one

```
                    ┌──────────────┐
                    │    users     │  credentials · activation · locale
                    │              │  userable_type + userable_id
                    └──────┬───────┘
                           │  polymorphic 1:1  (required, exactly one)
          ┌────────────────┼────────────────┐
          ▼                ▼                ▼
    ┌───────────┐   ┌────────────┐   ┌───────────┐
    │ students  │   │  teachers  │   │  admins   │
    │ branch_id │   │ bio, links │   │ full_name │
    └─────┬─────┘   └──────┬─────┘   └───────────┘
          │                │
          ▼                ▼
      branches      subject_teacher → subjects
```

**Cardinality:** one user has exactly one domain record; one domain record belongs to exactly one user. Both directions are mandatory, which is the strongest form this relationship can take and is only achievable because `admins` exists (§7.1).

**Uniqueness enforcement.** The polymorphic pair carries a composite index for lookup, but a *unique* index across `userable_type` and `userable_id` is what prevents two user rows claiming the same domain record — two sets of credentials logging into the same student. **Recommendation: make that index unique.** It costs nothing and closes an account-sharing hole that would otherwise be invisible until someone exploited it.

**The cost, stated plainly, and its mitigation.** `students.branch_id` is the hottest column on the platform: the global scope reads it on effectively every content query. Behind a polymorphic relation it is one hop away rather than on the authenticated model directly. Revision 1 cited this as a reason to keep it on `users`; the decision to move it is nonetheless correct, because it buys a mandatory-column constraint that was otherwise unobtainable (§0.3).

**The mitigation is resolve-once-per-request:** load the domain record with the authenticated user and memoise the branch for the lifetime of the request. Every subsequent query in that request reads a value already in memory. The cost is one join or one extra lookup per request, not per query — and it is bounded, predictable, and paid once.

**What the spine does *not* do.** It does not answer permission questions. `userable_type` says what shape a person is; the Spatie role says what they may do. §4.3.

## 4.3 Roles and permissions — the second discriminator

```
   users ──(polymorphic N:M)──> roles ──(N:M)──> permissions
     │                                              ▲
     └──────────(polymorphic N:M, direct grants)─────┘
```

Two polymorphic many-to-many relationships, both supplied by the package: `model_has_roles` maps users to roles, `model_has_permissions` maps users to permissions directly, and `role_has_permissions` maps roles to permissions. All three use composite primary keys and carry no additional attributes — pure junctions in the package's own design.

**The rule that must not be broken:** `userable_type` and the Spatie role are assigned **together, in one transaction, at account creation**, and neither is ever changed without the other. They are two views of one fact, and the database cannot enforce their agreement — `model_has_roles.model_id` is polymorphic and therefore carries no foreign key.

**What drift looks like in practice,** so it is recognisable when it happens:

| Drift | Symptom |
|---|---|
| Student `userable_type`, no role | The account authenticates, resolves to a student record, and is denied everywhere. Looks like a broken permission system. |
| Teacher `userable_type`, student role | Sees the student interface while owning lectures. The teacher scope filter returns nothing and the teacher believes their content was deleted. |
| Role changed without `userable_type` | The most dangerous: an account with administrator permissions whose domain record is a student, holding a `branch_id` that some queries still scope by. |

A scheduled consistency check comparing the two is cheap. **Recommendation: run it, and treat any mismatch as a security event rather than a data-quality warning.**

## 4.4 The governing rule for person-referencing foreign keys

This is the single most consequential change in Revision 2, and it applies without exception:

> **Domain references point at domain tables. Actor and audit references point at `users`.**

| Reference kind | Question it answers | Points at | Examples |
|---|---|---|---|
| **Domain** | Whose thing is this? Who is this record *about*? | `students` / `teachers` | `lectures.teacher_id`, `subscriptions.student_id`, `quiz_attempts.student_id`, `subject_teacher.teacher_id`, `lecture_progress.student_id`, `orders.student_id`, `library_files.teacher_id` |
| **Actor** | Who performed this action? | `users` | `*.deleted_by`, `payments.reviewed_by`, `subscriptions.activated_by`, `settings.updated_by`, `activity_log.causer_id`, `subject_teacher.assigned_by`, `contact_messages.handled_by`, `news.author_id` |

**Three reasons this rule is right, in order of importance:**

**1. It converts policy discipline into structural impossibility.** In Revision 1, `lectures.created_by` pointed at `users`, so nothing at the database level stopped a **student** from being recorded as a lecture's author. Only a policy stood in the way, and a policy is a thing a developer can forget. `lectures.teacher_id → teachers.id` makes that row impossible to insert. The same applies to every domain reference in the table above. **The permission model gained an enforcement layer it did not previously have.**

**2. Actor references must survive people the domain cannot represent.** An administrator approving a payment is an actor. If `payments.reviewed_by` pointed at a domain table, it could not represent an administrator at all without also assuming `admins` exists — and even then, a reference that must accept *any* of three domain types is exactly a reference to `users`.

**3. It makes the two reference kinds visually distinguishable.** In `subject_teacher`, `teacher_id` and `assigned_by` sit side by side and mean entirely different things: one is *whose subject this is*, the other is *who made it so*. The rule means a reader can tell which is which from the target table alone, without tracing the code.

**One consequence to accept.** A teacher's *actions* are logged against their user id while their *content* is owned by their teacher id. Joining "everything this person did" therefore crosses the spine once. That is correct, not awkward: their actions belong to their account, their content belongs to their professional identity, and those genuinely are different things — the content survives if the account is disabled.

## 4.5 One-to-Many relationships and deletion behaviour

### Identity

| Parent (1) | Child (N) | On parent deletion |
|---|---|---|
| `branches` | `students` | **Restrict** — never orphan a student's academic identity |
| `users` | `users` (as `deleted_by`) | **Null** |

### Academic spine

| Parent (1) | Child (N) | On parent deletion |
|---|---|---|
| `branches` | `subjects` | **Restrict** |
| `academic_terms` | `lectures` | **Restrict** |
| `academic_terms` | `orders` | **Restrict** |
| `academic_terms` | `subscriptions` | **Restrict** |
| `subjects` | `lectures` | **Restrict** |
| `subjects` | `library_files` | **Restrict** |
| `lectures` | `library_files` | **Null** — the file survives, reattached to the subject |
| `teachers` | `lectures` | **Restrict** |
| `teachers` | `library_files` | **Restrict** |
| `teachers` | `subject_teacher` | **Cascade** — an assignment without a teacher is meaningless |

The academic spine restricts rather than cascades throughout. A cascade here would mean soft-deleting a branch silently destroys every subject, lecture, quiz, attempt and payment beneath it — an unrecoverable loss from one click on an admin table. Restriction forces the deletion to fail loudly and the administrator to deal with the children first, which is correct for content people have paid to access.

**`academic_terms` restricting three children is new in this revision** and is the direct cost of decision 5: a term cannot be deleted while any lecture, order or subscription references it. That is the desired behaviour — terms are historical records, not disposable configuration.

### Assessment chain

| Parent (1) | Child (N) | On parent deletion |
|---|---|---|
| `quizzes` | `questions` | **Cascade** |
| `questions` | `question_options` | **Cascade** |
| `quizzes` | `quiz_attempts` | **Restrict** |
| `students` | `quiz_attempts` | **Cascade** on hard delete only |
| `quiz_attempts` | `attempt_answers` | **Cascade** |
| `questions` | `attempt_answers` | **Restrict** |

The asymmetry is deliberate and is the most consequential cascade decision in the schema. Questions and options cascade because they are *composition* — meaningless outside their parent. Attempts **restrict** because they are a student's academic history: deleting a quiz must never erase the record that a student sat it and scored 80%. That record is what the progress screen, the teacher's performance screen, and any future dispute depend on.

### Commerce chain

| Parent (1) | Child (N) | On parent deletion |
|---|---|---|
| `students` | `orders` | **Restrict** — financial records outlive account deletion |
| `students` | `subscriptions` | **Restrict** |
| `plans` | `orders` | **Restrict** |
| `branches` | `subscriptions` | **Restrict** |
| `orders` | `order_items` | **Cascade** |
| `orders` | `payments` | **Restrict** |
| `subjects` | `order_items` | **Restrict** |
| `users` | `payments` (as reviewer) | **Null** |
| `users` | `subscriptions` (as activator) | **Null** |

Financial records restrict without exception. A student deleting their account must not vaporise the record of money received — and since `students` is soft-deleted, the restriction only bites on a true purge, which is exactly when it should.

Actor references null rather than restrict: an administrator leaving the organisation must not block account cleanup, and the payment's approved status and timestamp survive regardless. Permanent attribution lives in `activity_log`.

### Progress, content and communication

| Parent (1) | Child (N) | On parent deletion |
|---|---|---|
| `students` | `lecture_progress` | **Cascade** |
| `lectures` | `lecture_progress` | **Cascade** |
| `students` | `study_activity` | **Cascade** |
| `news_categories` | `news` | **Null** — an article survives losing its category |
| `users` | `news` (as author) | **Null** |
| `users` | `contact_messages` (as handler) | **Null** |
| `users` | `settings` (as editor) | **Null** |
| `users` | `activity_log` (as causer) | **Null** |
| `users` | `subject_teacher` (as assigner) | **Null** |

Progress cascades because it is worthless without both its student and its lecture. **Every actor reference nulls**, on one principle: *an audit record must survive the deletion of the person it names.* An entry reading "deleted by [removed user]" is still evidence; an entry that was itself deleted is not.

## 4.6 Many-to-Many relationships and their junction tables

Five domain N:M relationships, plus the three package-supplied ones covered in §4.3. They divide into two kinds, and the distinction is real: a **pure junction** carries nothing but its keys and uses a composite primary key; an **associative entity** carries its own attributes and earns a surrogate key.

### N:M 1 — Subjects ↔ Teachers · `subject_teacher` · **associative entity**

One teacher per subject today (M-10); the junction exists from day one because M-10 explicitly asks the table to accommodate expansion.

| Attribute | Why it is there |
|---|---|
| `is_primary` | Which teacher is the public face once several exist. Without it, expansion creates an ambiguity with no answer. |
| `assigned_at` | When the assignment took effect — needed for *which teacher owned this subject last term?* |
| `assigned_by` | Which administrator assigned it. An actor reference, pointing at `users`. |

### N:M 2 — Subscriptions ↔ Subjects · `subscription_subjects` · **pure junction**

The per-subject entitlement from M-1. Composite primary key, no payload — the price already lives on `order_items`, and duplicating it here would create two sources of truth for one number. Populated only for per-subject subscriptions.

### N:M 3 — Orders ↔ Subjects · `order_items` · **associative entity**

| Attribute | Why it is there |
|---|---|
| `unit_price_agora` | The price actually charged. A later price change must not rewrite an issued receipt. |
| `subject_name_snapshot` | The name at purchase time, so a receipt stays readable after a rename or soft delete. |

### N:M 4 — News ↔ Topics · `news_topic` · **pure junction**

Composite primary key, no payload. Topic order is not stored — the sidebar list has no meaningful order, and inventing one would be a column nobody reads.

### N:M 5 — Attempt answers ↔ Question options · `attempt_answer_options` · **pure junction**

Composite primary key, no payload. One row for single-choice, several for multi-select.

### The near-miss worth naming: Students ↔ Subjects

There is an obvious temptation to add an `enrolments` junction. **It would be wrong.** Access on this platform is not stored membership — it is *derived* at query time from an active subscription plus a branch match plus a term match. A stored enrolment table would become a second, silently divergent source of truth: expire a subscription and the enrolment rows would keep asserting access. The predicate in §4.7 is the single source, and there is deliberately no table shadowing it.

## 4.7 The access predicate — updated for M-2 and academic terms

Every content read by a student resolves through one logical test, written here as the specification that the query scope and the policy must both implement.

**A student may access a subject when all of the following hold:**

1. The subject's branch matches the student's branch, resolved through the identity spine. *(Personalised exclusion. The outermost filter, applied first — not a refinement.)*
2. The subject is published.
3. The student holds a subscription whose status is active and where the present moment falls between its start and end.
4. **And** that subscription either is branch-wide with a branch matching the subject's branch, **or** is per-subject and lists this subject among its covered subjects.

**A student may access a lecture** when they may access its subject, **and** the lecture is published, **and** the lecture's academic term is one the student holds a subscription for in that subject.

**A student may open a quiz** when they may access its lecture, **and** their progress row for that lecture carries a `completed_at` value — which, under M-5, means the server verified at least 90% genuine interval coverage.

### Four consequences, each a rule rather than a remark

**Clause 1 belongs in a global query scope**, not only in a policy. A policy can be omitted from one query out of forty; a global scope cannot. Doctrine requires the filter in all three layers, and this predicate is why: the scope makes the common case safe, the policy makes direct access safe, and the view makes the experience coherent.

**Failing clause 3 or 4 is a 402, not a 403.** The student is a legitimate member of that branch who has not paid for that subject. The design rule that *402 is a sales page, not a rejection page* is a direct consequence of where the predicate fails, and the distinction must be preserved in the authorisation layer or the interface cannot honour it.

**Failing clause 1 is a 403, and it must not leak existence.** A student probing another branch's subject should get an identical response whether that subject exists or not.

**M-2 keeps the predicate flat, and this is the good outcome.** Revision 1 flagged `gates_next_lecture` as the one M-2 sub-decision that would change the *shape* of the predicate: had gating been adopted, lecture access would have gained a clause depending on the previous lecture's attempt outcome, turning a flat permission check into an ordered traversal of the subject — a materially more expensive query and a materially more complex policy. **`gates_next_lecture = false` avoids that entirely.** The predicate stays a set of independent conditions, each answerable without walking the lecture sequence.

> **⚠️ The term clause introduces a new product question, and it is not a technical one.** Because lectures bind strictly to a term, a student holding a term-2 subscription does not, by the predicate as written, retain access to term-1 lectures they previously paid for. Whether that is intended is a decision. §7.5 lays out the options and gives a recommendation.

## 4.8 Soft deletion, trash, and the two-layer problem

Ten tables carry `deleted_at` and `deleted_by`: `users`, `students`, `teachers`, `admins`, `branches`, `subjects`, `lectures`, `library_files`, `news`, `quizzes`.

Governing rules from the project's own constraints: teachers see and restore **only their own content**; teachers hold **no permanent delete** (M-9), which is admin-only; automatic purge after **30 days**; every deletion passes a confirmation dialogue; `deleted_by` always recorded.

**New in this revision — deleting a person now touches two tables.** A student is a `users` row *and* a `students` row. Three rules follow:

1. **Both are soft-deleted in one transaction.** A live credential row pointing at a deleted domain record is an account that authenticates into nothing.
2. **Restoration restores both.** The trash screen operates on the person, not on either table individually.
3. **`is_active` and `deleted_at` are different states and must not be conflated.** `is_active = false` is a *block* — screen 35's toggle, reversible, the account still exists and its content is untouched. `deleted_at` is a *deletion* — recoverable for 30 days, then permanent. Blocking a teacher must not remove their lectures from students who paid for them; deleting one must go through the trash. Merging these two into one flag would make the block button destructive.

**The screen 47 caveat.** The admin trash is a single sortable, paginated, type-filtered list drawn from ten tables. Two ways to serve it:

| Approach | Trade-off |
|---|---|
| **(A) Query-time union across the soft-deleted tables** | No extra writes, no second source of truth, nothing to keep in sync. Sorting and paginating across a union is awkward, and cost grows with table count. |
| **(B) A `trashed_items` index table written by observers** | Trivially sortable and paginable. But a second source of truth that can drift, needing a rebuild routine. |

**Recommendation: start with (A).** The set is small by construction — bounded by a 30-day purge — and (A) introduces no synchronisation risk. Switch to (B) only if the screen measurably slows, and treat that as a performance fix with a rebuild command, not as the initial design. Building (B) first would be optimising a screen that does not yet exist against a load that does not exist.

> One point that did shift: Revision 1 counted five soft-deleted tables here; this revision has ten, because the identity split turned one `users` row into two. The union is wider. It remains the right starting choice, but the margin narrowed, and if `admins` is adopted the union should group the three domain tables into a single *person* row for display rather than showing a user and a student as two separate trash entries for the same deletion.

## 4.9 A note on the amount of polymorphism now present

Four polymorphic relationships now exist: the identity spine, Spatie's two assignment pivots, `notifications`, and `activity_log`. That is a lot, and it deserves an explicit caution rather than silence.

**None of them can carry a database-level foreign key.** Referential integrity for all four is the application's responsibility, and a dangling reference is possible after any hard delete.

**The consequences differ sharply, and they should be treated differently:**

| Relationship | Dangling reference means | Severity |
|---|---|---|
| `activity_log.subject` | A log entry describing a purged record | **Desirable** — the log must outlive what it describes |
| `notifications.notifiable` | A notification for a deleted account | Cosmetic — prune on purge; the renderer should tolerate a missing target rather than fail |
| `model_has_roles.model` | Orphaned role assignments after a user purge | **Must be cleaned explicitly** at purge — otherwise a recycled id inherits stale permissions |
| `users.userable` | A credential row pointing at nothing | **Critical** — this is an authenticatable account with no identity |

The last one is why §4.2 recommends both a unique index on the pair and transactional deletion of the two rows together. It is the one polymorphic relation on this platform where a dangling reference is a security problem rather than a cosmetic one.

## 4.10 Index strategy

| Index | Serves | Built |
|---|---|---|
| `users` **unique** on (`email`) | Login — the identity lookup (🔄 was the phone pair) | ✅ |
| `users` **unique** on (`google_id`) | The Google callback lookup | ✅ |
| `users` **unique** on (`userable_type`, `userable_id`) | The spine; prevents two accounts claiming one person | ✅ |
| `users` on (`is_active`) | Excluding blocked accounts at authentication | ✅ |
| `students` on (`branch_id`) | The root filter and the branch roster | ✅ |
| `branches` **unique** on (`slug`) · on (`is_active`) | Route-model binding by slug; the public branch list | ✅ |
| `teachers` on (`is_publicly_listed`) | The public branch page's teacher strip | ✅ |
| ~~`phone_verification_codes` on (…)~~ | Withdrawn with the table (§3.5) | ❌ |
| `model_has_roles` on (`model_id`, `model_type`) | Every permission check (package-supplied) | ✅ |
| `subjects` on (`branch_id`, `is_published`) | The subject list on every student screen |
| `lectures` on (`subject_id`, `academic_term_id`, `status`, `display_order`) | The lecture list, already sorted and term-scoped |
| `lectures` on (`teacher_id`) | The teacher scope — *my subjects only* |
| `subscriptions` on (`student_id`, `status`, `academic_term_id`, `ends_at`) | **The access predicate — the hot path, deserving the most care** |
| `subscription_subjects` on (`subject_id`) | Reverse lookup: who may access this subject |
| `lecture_progress` **unique** on (`student_id`, `lecture_id`) | Resume, completion, dashboard progress bars |
| `lecture_progress` on (`completed_at`) | Completion counts and the closed-loop check |
| `quiz_attempts` on (`student_id`, `quiz_id`) | Attempt counting against the cap of 2 |
| `study_activity` **unique** on (`student_id`, `activity_date`) | Streak calculation and weekly chart |
| `orders` on (`status`, `created_at`) | The admin review queue, in arrival order |
| `payments` on (`status`, `created_at`) | Screen 42's pending queue |
| `news` on (`status`, `published_at`) · on (`views_count`) | The news index; the most-read widget |
| `notifications` on (`notifiable_type`, `notifiable_id`, `read_at`) | The unread badge — runs on every authenticated page load |
| All ten soft-deleted tables on (`deleted_at`) | Both trash screens and the purge job | ✅ **on all 5 that exist** |

Composite column order is deliberate throughout: the most selective and most frequently filtered column leads, so a partial match on the leading columns remains usable.

> **⚠️ Trap, hit once already: `$table->softDeletes()` does not create an index.** All five identity/branch tables shipped on 2026-08-24 without one, so every Eloquent read — each of which appends `deleted_at IS NULL` implicitly — was an unindexed scan, and both trash screens would have read the same column unindexed. Corrected on 2026-08-26 by `2026_08_26_000000_add_soft_delete_indexes_and_branch_actor_fk`. **Every future table in the soft-delete set must declare the index explicitly alongside `softDeletes()`** — the helper's name suggests completeness it does not deliver.

---

# 5 · Invariants

Properties that must hold regardless of what code is written above the database. Each names its enforcement layer honestly, because several cannot be enforced by the database alone — and pretending otherwise is how an invariant quietly stops holding.

| # | Invariant | Enforced by | Change |
|---|---|---|---|
| 1 | A student belongs to exactly one branch | **Database** — `students.branch_id` is required | ⬆ **Upgraded** from an application check |
| 2 | Every account resolves to exactly one domain record | **Database** — polymorphic pair required + unique | ⬆ **New**, requires `admins` (§7.1) |
| 3 | **An email address** identifies at most one live account | **Database** — `users.email` unique | 🔄 **Rewritten in Rev 3** (was: a phone number) · ✅ enforced |
| 4 | `userable_type` and the Spatie role agree | **Application** — one transaction (`User::syncRoleToShape()`) + scheduled check | ✅ **transaction half built**; the scheduled check is not |
| 5 | A student never reads content outside their branch | Global scope **and** policy **and** view — all three | — |
| 6 | Content is owned only by a teacher | **Database** — `lectures.teacher_id` → `teachers` | ⬆ **Upgraded** from policy-only |
| 7 | A lecture has at most one quiz | **Database** — unique on `quizzes.lecture_id` | — |
| 8 | A published lecture has a quiz with ≥1 question | Application, at the publish transition (M-3) | — |
| 9 | A published lecture has a server-obtained duration | Application, at the publish transition | ⬆ **New**, required by M-5 |
| 10 | A quiz opens only after verified 90% coverage | Application, reading `lecture_progress` | ⬆ **Strengthened** by M-5 |
| 11 | Progress cannot advance faster than wall-clock time | Application, bounded by `last_heartbeat_at` | ⬆ **New**, required by M-5 |
| 12 | No card data exists anywhere | **Schema design** (M-4). No column can hold it. | — |
| 13 | Content files are never reachable without an authorisation check | Private disk + controller + signed URLs | — |
| 14 | One-time codes are never stored in plaintext | ⚪ **Moot in Rev 3** — no code table exists | Reinstate with any future SMS feature |
| 15 | A subscription exists only after a payment is approved | Table separation between `orders` and `subscriptions` | — |
| 16 | Historical prices and scores are never rewritten | Snapshot columns on `orders`, `order_items`, `quiz_attempts`, and now `quizzes` | ⬆ **Extended** to quiz settings |
| 17 | Every deletion is recoverable for 30 days and attributable | `deleted_at` + `deleted_by` + `activity_log` + purge job | — |
| 18 | Blocking is not deleting | Separate `is_active` and `deleted_at` | ⬆ **New**, made explicit |
| 19 | Teachers cannot permanently delete | Policy (M-9) | — |
| 20 | Money is never a floating-point value | Integer agora throughout | — |
| 21 | An audit record survives the deletion of the person it names | Actor foreign keys null rather than cascade | — |

**Six invariants moved from application enforcement to database enforcement in this revision** (1, 2, 3, 6, plus 12 and 14 by design). That is the concrete measure of what the restructure bought.

> **Revision 3 — which invariants actually hold today.**
>
> | Holds now | Invariant |
> |---|---|
> | ✅ | **1** `students.branch_id` required + FK `restrictOnDelete` |
> | ✅ | **2** polymorphic pair required **and** uniquely indexed |
> | ✅ | **3** `users.email` unique (rewritten from phone) |
> | ✅ | **4** at creation time — via `syncRoleToShape()` in both paths; the scheduled drift check is still owed |
> | ✅ | **12** by design — no column can hold card data |
> | ✅ | **18** `is_active` and `deleted_at` are separate columns |
> | ✅ | **21** actor FKs `nullOnDelete` on every soft-deleting table — `branches` corrected 2026-08-26 |
> | ✅ | **6** content is owned only by a teacher — `lectures.teacher_id` and `library_files.teacher_id` point at `teachers`, so a student-owned lecture is structurally impossible (2026-08-26) |
> | ✅ | **20** money is integer agora — `subjects.price_override_agora` is the first such column |
> | ⚠️ | **9** a published lecture has a server-obtained duration — the column and the check exist (`Lecture::isReadyToPublish()`), but **nothing calls it yet**: no publish transition has been written |
> | ❌ | **5** — **no global scope and no policy exists yet.** Phases 3–4 have now added the very content this invariant protects, so the gap is no longer theoretical. **Largest open risk in the platform** |
> | ⏳ | **7 · 8 · 10 · 11 · 15 · 16 · 19** — depend on tables not yet built |
> | ⏳ | **13** — `storage_path` is private-disk by design and hidden from serialisation, but **no file-serving controller exists**, so nothing yet evaluates access before delivery |
> | ⏳ | **17** — `deleted_at` / `deleted_by` in place on all seven soft-deleting tables; `activity_log` and the 30-day purge job are not |

---

# 6 · Resolved decisions

## 6.1 M-2 — quiz behaviour · **RESOLVED**

| Parameter | Value | Where it lives |
|---|---|---|
| Unlock threshold | **90%** genuine watched coverage | `quizzes.unlock_threshold_percent`, snapshotted from settings |
| Maximum attempts | **2** | `quizzes.max_attempts` |
| Scoring policy | **highest** | `quizzes.scoring_policy` |
| Gates next lecture | **false** | `quizzes.gates_next_lecture` |

**Schema effects.** Four columns move from nullable placeholders to required with defaults. A fifth settings group, `assessment`, holds the platform-wide defaults, which are copied onto each quiz at creation so that a later policy change never disturbs a student mid-term (§3.12).

**The important part is what did *not* happen.** Revision 1 identified `gates_next_lecture` as the sub-decision that would change the *shape* of the access predicate rather than merely its values. Gating would have made lecture access depend on the previous lecture's attempt outcome, converting a flat set of conditions into an ordered traversal of the subject. **Choosing false keeps the predicate flat, keeps the query cheap, and keeps the policy simple** — and it aligns with the doctrine entry that gating creates frustration and raises drop-off in an audience already under exam pressure.

**One remaining application-level rule:** the cap of 2 cannot be a database constraint, since `attempt_number` cannot be checked against a column in another table. It is enforced at attempt creation, backed by the composite uniqueness that prevents a race producing two rows numbered 3.

## 6.2 M-5 — video completion · **RESOLVED as server-verified and authoritative**

**Schema effects, all on `lecture_progress`:** `watched_intervals`, `last_heartbeat_at`, and `completion_source` are added; `watched_seconds` becomes a derived value rather than the primary record.

**Why a counter was no longer sufficient.** While completion was a display behaviour, a seconds counter was fine. Made authoritative, it becomes the thing that unlocks paid content, and a counter cannot distinguish watching from seeking — dragging the scrubber to the end produces the same maximum position as watching the whole lecture. Interval coverage measures *which* 90%, and the heartbeat bound measures whether that coverage accumulated at a physically possible rate.

**Two consequences beyond the columns:**

- **`lectures.video_duration_seconds` becomes load-bearing** and must be server-obtained — from the provider's API for a hosted asset, or by server-side inspection for a local file. A percentage of an unknown total cannot be computed, and a client-supplied duration is precisely the number an attacker would want to control, since understating it makes 90% arrive early. This becomes a publish-time requirement (invariant 9).
- **The hosting choice is now a security choice, not just a cost one.** The schema tolerates either through `video_source` and `video_reference`, so nothing is blocked. But a provider that offers signed, server-side playback events makes the verification above straightforward, whereas a self-hosted element makes every heartbeat a claim from an untrusted client that the server can only bound, never confirm. **Recommendation: when the hosting provider is finally selected, weight server-verifiable playback events heavily** — under this decision, that capability is the difference between verification and mitigation.

## 6.3 Academic terms — **RESOLVED as Option A (explicit table)**

**Schema effects.** `academic_terms` is adopted. `academic_term_id` becomes **required** on `lectures`, `orders` and `subscriptions`, and the term restricts deletion of all three.

**Two consequences that need action rather than acknowledgement:**

**An operational dependency now exists.** No lecture can be created and no order placed before a term exists. The first seeder must create one, and screen 44 needs an *open / close term* surface the current specification does not mention (§7.6).

**A product question is created, not answered.** Binding lectures strictly to terms means the access predicate, as written, does not grant a term-2 subscriber access to term-1 lectures. That is a real behavioural decision that the technical choice surfaced but does not settle (§7.5).

---

# 7 · Open items — **4 of 8 resolved in Revision 3**

Revision 2 raised eight. Four are now closed by implementation, four remain.

| Item | Status |
|---|---|
| 7.1 `admins` table | ✅ **Resolved** — built, option (A) |
| 7.2 Drop `email` | ✅ **Resolved by reversal** — email is the identifier |
| 7.3 `governorate` / `country_code` | ✅ **Resolved** — both on `students` |
| 7.4 `guardian_phone` at signup | ✅ **Resolved** — profile-only |
| 7.5 Term revokes previous-term access? | ⏳ **Open · blocks `academic_terms`, therefore phase 4** |
| 7.6 Screen 44 gains two surfaces | ⏳ Open — scope note |
| 7.7 Soft delete blocks re-registration | ⏳ Open — now about **email**, not phone |
| 7.8 `activity_log` retention | ⏳ Open — no default set |

Nothing below has been silently defaulted.

## 7.1 ~~Does an `admins` table exist?~~ · ✅ **RESOLVED — yes, option (A) built**

Names now live on domain tables. An administrator with no domain record therefore has **no name** — yet screens 34, 35, 41, 42 and 47 all display who performed an action.

| Option | Consequence |
|---|---|
| **(A) An `admins` table** ✅ | `userable_type` and `userable_id` stay **required**. Every user row resolves to exactly one domain record, with no exceptions and no null branches anywhere in the code. Cost: one table with three meaningful columns. |
| **(B) Nullable pointer + a name on `users`** | Weakens the polymorphic contract permanently. Every piece of identity-handling code needs a null branch, and `users` regains a display column the split was meant to remove. |

**Recommend (A).** The whole value of a mandatory polymorphic pair is that it has no exceptions; option B introduces one on day one and it never goes away. This document is written assuming (A).

## 7.2 ~~Is `email` dropped entirely?~~ · ✅ **RESOLVED — no, it became the identifier**

Revision 2 recommended dropping `email` on the grounds that *"authentication is phone-based"*. **That premise was reversed** (§0-A): email is the primary identifier, unique on `users`, alongside Google OAuth. Screen 14 keeps its field. Nothing to do.

## 7.3 ~~Where do `governorate` and `country` live?~~ · ✅ **RESOLVED — on `students`, as recommended**

Both built: `students.governorate` (80) and `students.country_code` (2), collected by step 2 of registration.

> The caveat Revision 2 attached — that `country_code` partly duplicates `users.phone_country_code` — **no longer applies**: there is no phone country code on `users`. `country_code` is unambiguously country of residence.

## 7.4 ~~Is `guardian_phone` collected at signup?~~ · ✅ **RESOLVED — profile-only, as recommended**

The column exists on `students`, nullable. Registration does **not** collect it; it is filled from the profile screen. Note that registration *does* collect `students.phone` — the student's own number, added in Revision 3 as contact metadata.

## 7.5 Does a new term revoke access to the previous term's lectures? · **product decision · recommend cumulative**

Created by decision 5. The predicate as written grants access only to lectures in a term the student holds a subscription for.

| Option | Behaviour |
|---|---|
| **(A) Strict** | A term-2 subscriber cannot open term-1 lectures, even ones they paid for and completed. Clean, and maximises re-purchase pressure. |
| **(B) Cumulative over paid terms** ✅ | A student sees lectures from every term in which they held a subscription for that subject. |

**Recommend (B).** Option A revokes access to content a student already paid for and, worse, breaks their own progress history mid-year — the progress screen would show completed lectures they can no longer open. That reads as a bug to the student regardless of the commercial logic. (B) is expressible by joining subscriptions on term without restructuring anything, and it costs nothing in the common case since most students subscribe continuously.

## 7.6 Screen 44 gains two surfaces · **scope note**

Two decisions add administrative surfaces the current screen 44 specification does not include: an **open / close academic term** control (decision 5), and the **assessment settings group** holding the four M-2 defaults. Both are small, but both are new scope on a screen already specified as four tabs.

## 7.7 Soft delete blocks **email** re-registration for 30 days · ⏳ **still open** · confirm the behaviour

A soft-deleted `users` row keeps its **email address** under a unique index, so that address cannot re-register until purge. (Revision 2 said *phone number*; the mechanism is identical, only the column changed — §0-A.) **Recommend accepting this and providing an administrative restore path** — for a student who deleted their account by mistake, restoration is what they actually want, since progress, attempts and subscription return with it. The alternative, mutating the stored address on deletion, corrupts the audit trail to solve a rarer problem.

## 7.8 Activity log retention · **decision, no default**

`activity_log` grows without bound and is the only durable attribution once soft-deleted rows are purged at 30 days. Its own retention must be **longer** than 30 days or deletion attribution is lost exactly when it is needed. A choice is required; I am not defaulting one.

## Still open from before, unchanged by this revision

**M-6** — can a student change branch after choosing? Still open. If a self-service request flow is adopted, `students` gains a request column; the recorded recommendation is admin-mediated change with no student-facing column at all. **M-9** (teacher permanent delete) and **M-12** (global search) also remain as recorded.

---

# 8 · Recommended migration sequence

Ten phases, each independently testable. Order is dictated by foreign-key dependency, with one deliberate departure noted at the end.

| Phase | Tables | Unblocks | Status |
|---|---|---|---|
| **1 · Identity** | `users`, `students`, `teachers`, `admins` (~~`phone_verification_codes`~~ withdrawn) | Authentication; the spine every later phase references | ✅ **done** 2026-08-24 |
| **2 · Permissions** | Spatie's five tables, seeded with three roles | Policies; the second discriminator | ✅ **done** 2026-08-26 |
| **3 · Academic structure** | `branches`, `academic_terms`, `subjects`, `subject_teacher` | The root filter; the term dependency | ✅ **done** 2026-08-26 |
| **4 · Content** | `lectures`, `library_files` | Screens 3, 4, 19, 28, 30, 31 | ✅ **done** 2026-08-26 |
| **5 · Assessment** | `quizzes`, `questions`, `question_options` | Screen 29 | ⏳ |
| **6 · Attempts** | `quiz_attempts`, `attempt_answers`, `attempt_answer_options` | Screens 6, 32 | ⏳ |
| **7 · Progress** | `lecture_progress`, `study_activity` | Screens 2, 20; **closes the loop** | ⏳ |
| **8 · Commerce** | `plans`, `orders`, `order_items`, `payments`, `subscriptions`, `subscription_subjects` | Screens 7–12, 41, 42 | ⏳ |
| **9 · Communication** | `news_categories`, `topics`, `news`, `news_topic`, `notifications`, `contact_messages` | Screens 21, 24, 25, 40 | ⏳ |
| **10 · System** | `settings`, `activity_log` | Screens 26, 34, 44, 47 | 🔶 `settings` built reduced (§3.32); `activity_log` ⏳ |
| **J · Landing content** | `media`, `sliders`, `testimonials`, `steps`, `features`, `faqs` | The public home page, previously static | ✅ **done** 2026-08-26 · out of phase order |

**Phases 1–2 must land together.** Invariant 4 — that `userable_type` and the Spatie role agree — cannot be established if accounts are created before roles exist. Creating users in phase 1 and adding roles in phase 2 guarantees a population of role-less accounts that must then be backfilled, which is exactly the drift §4.3 warns about.

> **This is exactly what happened, and it is worth recording rather than tidying away.** Phase 1 shipped on 2026-08-24 without phase 2; four accounts existed with no roles for two days. Phase 2 landed on 2026-08-26 and the window was closed by a data migration (`2026_08_26_000001_backfill_user_roles`). The permission migration was **renumbered to `2026_08_24_000004`** so that a fresh install never reproduces the gap, and both account-creation paths now assign the role inside the creation transaction, so it cannot reopen. Cost of ignoring the rule: one extra migration and a manual backfill — small here, unbounded had it been noticed later.

> **Phase 3 was partly out of order.** `branches` shipped with phase 1 because `students.branch_id` is required — no student row can exist before a branch does. The remaining three tables landed on 2026-08-26 together with phase 4. **The seeder order this forces is now permanent:** roles → branches → **terms** → subjects, because `lectures.academic_term_id` is required and no lecture can exist before a term.

> **§7.5 was NOT settled before `academic_terms` was built, and that is deliberate.** The question — does a new term revoke access to the previous term's lectures? — shapes the **access predicate**, which is a query joining `subscriptions` to `academic_terms`. It does not shape any column built in phase 3 or 4. Both options remain expressible against the schema as it stands. **It must still be settled before phase 8**, where `subscriptions` makes the choice concrete.

## 8.1 Next actions · as of 2026-08-26

| # | Action | Why now |
|---|---|---|
| 1 | **Policies + `BranchScope`** | Invariant 5 — the platform's first governing principle — is **unenforced**, and phases 3–4 just added the content it is supposed to protect. `lectures` and `library_files` now exist with no scope and no policy above them: the gap stopped being theoretical |
| 2 | Granular Spatie permissions | Deferred until the first Policy exists; item 1 makes it due |
| 3 | Phase 5 — `quizzes`, `questions`, `question_options` | Completes `Lecture::isReadyToPublish()`, which cannot enforce M-3 until `quizzes` exists |
| 4 | Settle §7.5, then phase 8 | No longer blocks content; still blocks `subscriptions` |
| 5 | Scheduled `userable_type` ↔ role drift check | The other half of invariant 4 |
| 6 | Settle §7.7 (email re-registration after soft delete) and §7.8 (`activity_log` retention) | Both are decisions, not work |

**Phase 3 must precede phase 4 more strictly than in Revision 1.** `lectures.academic_term_id` is now required, so a term must exist before any lecture can be inserted — including in seeders.

**The deliberate departure.** Screen 42, the admin payment review, is the other half of the manual-payment loop already built in Wave 3. Until it exists, a student can pay and upload a receipt that nothing can approve, and no subscription can ever be created. That makes **phase 8 plus screen 42** the first point at which the platform has a working commercial loop end to end — an argument for reaching phase 8 earlier than dependency order alone would suggest, even at the cost of stubbing parts of phases 5–7.

---

**End of Revision 2.** No code, no SQL, no migrations written. §7 is the gate: items 7.1, 7.2, 7.3 and 7.5 change table structure and should be settled before phase 1 begins.
