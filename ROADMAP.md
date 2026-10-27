# Roadmap & feature backlog

The single place for planned features, ideas and open questions. Add new ideas here
rather than in chat or commit messages. When something ships, tick it and note the date.
Record decisions, with the reason, in the log at the bottom.

**Guiding principle:** Shawn writes on a laptop, bit by bit over several days, and is not
technical. Every feature should either need nothing from him, or make writing easier for him.
Never add a step to his workflow without a clear payoff. The site's personality is his voice:
AI may fix mistakes and fill in missing details, but it never flattens his writing or
publishes on his behalf.

Status: `[ ]` planned · `[~]` in progress · `[x]` done · `(?)` idea, needs discussion

---

## 0. Template vs. content: who fixes what

Problems on the site fall into two buckets. Keep them apart.

- **Template (the theme):** ground rules that hold for *every* post, whatever Shawn types.
  Typography, the reading column, image sizing limits, galleries, spacing, hiding empty elements,
  navigation, search, SEO tags. If a rule can be stated once and applied everywhere, it belongs here.
- **Content (the posts):** what is actually in each post: wording, which photos, how he formatted
  a heading, a restaurant's address. Fix it in the content, not with template special cases.

**Rule of thumb:** the template must not grow edge-case CSS for individual posts. When content doesn't
follow the template's ground rules (a "Day 2" typed as a plain paragraph instead of a heading,
12 photos in a row instead of a gallery), the fix is to change the content to fit the rules. In
future the AI assistant does that (§2.11), with Shawn's or Jacob's approval. Until then, content
issues are parked in §6.

Cheap, generic template rules are fine when they apply everywhere (e.g. "an empty heading is
hidden", "portrait photos never get taller than the screen").

---

## 1. Redesign: "Road Journal" (in progress, staging only)

Direction **A · Road Journal** was chosen on 2026-10-24 by Jacob and Shawn.
Design canvas: https://claude.ai/artifact/Uu314G9Q5YizEABVDquG9c (private; also has the
rejected directions B and C for reference).

Look: warm paper ground, Young Serif headlines over Source Sans 3 body, tomato `#A8321C`
and mustard `#C8921A` accents on charcoal `#1F1B17`, passport-stamp labels.

Approach: restyle the existing classic theme and add a `theme.json` for design tokens and
editor styles. Not a full block theme: that would expose the Site Editor to a non-technical author.

**Goal before production:** make it feel polished and aligned with blog best practices on staging
first. Nothing goes to production until the polish pass below is done.

### How each homepage section gets its content

Every section must work with **no extra effort from Shawn**. When data is missing, a
section hides itself rather than showing something broken.

| Section | Content source | Needs from Shawn | Later improvement |
|---|---|---|---|
| Header + search | WordPress search | Nothing | Search filters (below) |
| Hero: latest trip | Newest post in the Road Trips category; its featured photo and first sentence | Nothing | Stamps ("10 days · 2 countries") from trip fields the assistant fills in |
| Road Trips & Travels | Latest posts in Road Trips | Nothing | |
| Destination stamps (NOLA, SFO…) | A *Destination* taxonomy; each term has a short stamp code, set once per place | Nothing (Jacob or the assistant tags) | Assistant suggests the destination on publish |
| The Bite Rating | Existing 7 reviews (`review_content`, `review_rating`) | Nothing | Reviews extracted from trip posts (§2.4) |
| From Shawn's Kitchen | Existing recipes (`recipes_content`) | Nothing | |
| My 2 Cents | My 2 Cents category | Nothing | |
| Meet Shawn | Fixed text and photo, editable in one admin settings screen | Nothing | |
| Next stops | An *Upcoming trips* list (month + places) in one admin settings screen. Entries hide themselves once their month has passed | Nothing (Jacob keeps it current) | Assistant proposes updates when a post mentions future plans (§2.6) |

### Built (branch `road-journal`, on staging since 2026-10-24)
- [~] Content restructure: categories Road Trips, My 2 Cents, The Early Years, Updates replace "Blog";
      destinations with stamp codes; "Grandville Island" tag fixed; duplicate Uncategorized removed; menu
      rebuilt. Script: `scripts/migrations/road-journal-content.php`. **Done on staging; production waits
      for Shawn's OK on the category names.**
- [x] `theme.json` tokens, self-hosted fonts, editor styles (the editor matches the site).
- [x] Header with search, footer, phone menu; homepage sections; single, archive, search, 404, About,
      Recipes and Reviews templates. Bootstrap, the Font Awesome 4.7 link and the old per-page CSS removed.

### Polish pass: template bugs (from the fresh-eyes review, 2026-10-24)
- [x] Destination archive URLs 404 (rewrite rules were never flushed after adding the taxonomy). Also
      flush in the migration so production doesn't hit it.
- [x] Recipe "Time to cook" badge sits at the screen edge instead of in the text column.
- [x] Archive headings are wider than the list beneath them (misaligned on desktop).
- [x] Card excerpts end in "…." (double punctuation).
- [x] `/page/2/` serves a duplicate of the homepage.
- [x] Empty categories ("Blog", "Uncategorized") are public; hide or noindex empty archives.
- [x] Santa Monica stamp "LAX" reads as an airport. Use a place-style code.
- [x] Images from the old WHC address `wp230263.wpdns.ca` (15 images in 3 posts on production; it's a parked
      domain on the same account). Point them at the main domain with a search-replace migration.
- [x] Ground rules for images: portrait photos never taller than the screen; browsers download images
      sized for the reading column, not full size; tap to enlarge (lightbox).
- [x] Ground rules for content blocks: hide empty headings and empty list items; legacy "row" groups wrap
      instead of squeezing the text (Atlantic Kitchen, Buttermilk Biscuits).
- [x] Search "no results" suggestions should be links; 404 page should suggest real content.
- [x] About page "Today" photo: the template cropped from the middle (fixed: crops from the top now). His face is
      still shaded by his hat brim in that photo: a better source photo is a content item (§6).
- [x] Mobile pass at true phone widths (360, 390 and 414px), not just a narrowed desktop window.

### Polish pass: features (approved by Jacob)
- [x] **Search filters:** filter results by type (trips, reviews, recipes, opinions) and more; see research notes.
- [x] **Breadcrumbs** on posts, recipes, reviews and archives (with BreadcrumbList schema).
- [x] **Social icons** instead of the words "Facebook" / "Instagram" (accessible labels).
- [~] **Bite Rating index:** "How I rate" note on the Reviews page, verdict word next to every score. Opening line is
      Shawn's own (About › Today); band names come from his posts ("off the charts", "top 5", "solid", "I know there are
      better options", "don't waste your time"). **Shawn to approve or rewrite the wording** (`tb_rating_bands()` in functions.php).
- [x] **Related posts** at the end of posts, recipes and reviews (same destination, category or type).
- [x] Gallery lightbox for photo-heavy trip posts (see image ground rules).

Research notes behind these choices: `docs/research/blog-best-practices-2026-10.md`.

### Polish pass, round 2 (2026-10-24)
- [x] **Balanced photo grids** (template, not content): Shawn lays photos out with Grid groups, which filled
      3 columns and left a lone 4th photo on its own row. Grids (and default galleries) now use balanced rows:
      4 = 2×2, 5 = 3+2, 7 = 3+2×2; 2 per row on phones; captions overlay the photo; back-to-back grids
      join with the same 10px gap. Decision: template, because it holds for every post and costs Shawn nothing.
- [x] Row groups in posts stack instead of squeezing text (fixed Atlantic Kitchen fully); photos centred.
- [x] Table of contents for posts with 4+ h2 headings; all headings get anchors. Verified with a test post;
      appears on real posts once "Day N" lines become headings (§2.11).
- [x] Review info box (Bite Rating, verdict, "How I rate", plus city/cuisine/price/visited/address/website when
      filled) with a "Restaurant details" box in the review editor; Review → Restaurant JSON-LD (`bestRating: 10`).
- [x] Recipes: Jump to recipe + Print recipe buttons, print stylesheet; Recipe JSON-LD built from the pattern's
      Ingredients/Instructions blocks (nothing new for Shawn).
- [x] Meta description + Open Graph/Twitter tags on every page (Facebook previews); homepage and recipe descriptions.
- [x] Fuller footer: about line, Browse and More links, RSS, ©. (No privacy page exists yet: see below.)
- [x] 200% text size: overflow check on 14 key pages at 320/360px; fixed scoreboard names, drumsticks, note tilt.
- [x] **Image formats:** tried Modern Image Formats on staging and removed it. On this host AVIF came out *larger*
      than JPEG (GD encoder), WebP+JPEG mode didn't run from WP-CLI, and its first run re-pointed the original
      file record to .avif (restored). Decision (Jacob, 2026-10-25): use LiteSpeed Cache image optimisation via
      QUIC.cloud, **connected on production as a backlog item** (§5), not now. The `sizes` fix already makes
      browsers fetch column-sized images.
- [ ] Check on a real iPhone (Safari) before production.

### Comments and privacy (2026-10-25, on staging)
- [x] **Comments on** for posts, reviews and recipes (pages stay closed). Publish straight away, no approval
      queue; Antispam Bee filters spam on the server (honeypot, too-fast submissions, known spam IPs, BBCode;
      nothing sent to outside services); spam kept 30 days then deleted. Comments containing a link are held
      (§2.7 triage later). No website field, no Gravatar look-ups (readers get an initial; Shawn gets his photo
      and a "Chef" badge). Replies thread 3 deep. Shawn is emailed about new comments on his posts.
      Script: `scripts/migrations/comments-and-privacy.php`.
- [x] Tested on staging as an anonymous visitor: a bot-style post (honeypot filled) went to spam, a genuine
      comment published instantly, one with a link was held with a "waiting for a quick check" note. Note: a
      spam hit marks that IP, so a genuine comment from the same IP right after is also caught (by design).
- [~] **Privacy page** (`/privacy/`, set as WordPress's privacy page, linked from the footer and the comment form).
      Plain language, matches what the site actually does: no ads, analytics or tracking; what comments store;
      the opt-in cookie; links out; hosted in Canada. **Needs Jacob's and Shawn's OK** (not legal advice), and a
      contact method: it currently says to message the Facebook page. Add an email if Shawn wants one.
- [x] **RSS feed** now includes reviews and recipes, not just posts. The RSS icon was removed from the social row:
      `/feed/` is raw XML for feed-reader apps (Feedly etc.), which confused people next to Facebook/Instagram.
      The footer's More list keeps a plain "RSS feed" link, and readers find the feed automatically from the site
      address. (A styled feed page via XSLT was ruled out: Chrome is removing XSLT support.)

### Production rollout: done 2026-10-25
Deployed `7bf781e` (PR #3), then ran road-journal-content, polish-data-fixes, Antispam Bee install and
comments-and-privacy on production (DB backups in `~/deploy/production/db-backups/before-*-20261025-*.sql`).
Checked: 15 key URLs, sitemap (includes destinations), indexable, no staging leftovers, JSON-LD, comment form +
honeypot, one live test comment (published instantly, then deleted; Shawn's emails paused for the test), no theme
errors in the log. Shawn's check-in happens after launch instead of before (Jacob's call).

Follow-ups:
- [ ] Real iPhone check, and Facebook Sharing Debugger on a post.
- [x] Site admin address **GengiesTastyBites@outlook.com** (Jacob's alias, forwards to him): WordPress admin
      email, copied on every new-comment email, footer "Email us", Privacy page contact (`[site_email]`).
      Live on staging and production (2026-10-25, `scripts/migrations/admin-contact.php`).
- [x] Shawn and the admin address receive the new-comment email (Jacob tested 2026-10-25).
- [~] Branded comment emails (`inc/emails.php`): site colours and logo, who said what, plain "what you can do"
      buttons (Reply on the site / Delete it / It's spam; held ones: Approve it), each naming the exact confirm button
      WordPress shows next. No IP, host or email (still in wp-admin → Comments). Replies go to the admin address.
      Live on production 2026-10-25. (first chance to see email; staging blocks it).
- [x] `/category/blog/` (and its pages and feed) 301 to Road Trips (the old Blog category was removed). Live 2026-10-25.
- [ ] Shawn check-in (list below). **Deferred by Jacob (2026-10-25) until the AI features are in**, so it's one
      sitting covering both.

### Production rollout (as planned)
1. **Check-in with Shawn** (Jacob, one sitting; parked until then):
   - category names (rename in the migration script first if he wants different ones);
   - Bite Rating index wording ("How I rate" note and verdict bands);
   - the Privacy page wording and how readers should contact him;
   - that he's happy to get an email for every new comment;
   - the public "I don't use any AI hacks" note (§4).
2. Merge `road-journal` → `main` and approve the production deploy. Until step 3 runs, the homepage
   falls back to the old "Blog" category and the menu stays as it was: nothing breaks.
3. Straight after, in order (each takes a DB backup first; they need the new theme's Destinations taxonomy,
   which is why they run after the deploy):
   - `bash scripts/migrate.sh production scripts/migrations/road-journal-content.php`
   - `bash scripts/migrate.sh production scripts/migrations/polish-data-fixes.php`
   - Install Antispam Bee: `source scripts/lib.sh && remote 'wp --path=public_html plugin install antispam-bee --activate'`
   - `bash scripts/migrate.sh production scripts/migrations/comments-and-privacy.php`
   - Optional: fill "Restaurant details" on the 7 reviews (city, cuisine, price) so the info boxes fill out.
4. Confirm the XML sitemap is served on production (`/wp-sitemap.xml`; on staging it's off because search
   engines are discouraged there).
5. Spot-check the homepage, a trip post, search and the phone menu.

---

## 2. The quiet assistant (AI in the background)

> **Research (2026-10-25):** `docs/research/authoring-ai-2026-10/REPORT.md` covers prior art, what helped other
> (older) authors, the tiered after-publish design, architecture, build order and per-item specs. Read it before
> building anything in §2 or §4.
>
> **Build progress:** step 1 (plugin foundation: `plugins/tastybites-assistant`, job queue, AI log table, off
> switches, after-publish trigger, Tools → Assistant, staging cron) on staging 2026-10-25, branch
> `ai-features` (integration branch; all AI work stays on staging and ships to production together).
> Step 2 (2.1 featured photo) and step 3 (2.11 layout tidy-ups, propose mode) on staging 2026-10-25. Shawn's emails start **off** until Jacob's check-in with him.
> Step 4 (AI connection) on staging 2026-10-26: `tba_ai()` calls Anthropic directly with structured output,
> approved models only (Haiku/Sonnet/Opus 5.5), every call logged with cost, $1/day limit (Tools → Assistant)
> under the $20/month Console limit, "AI on staging" switch (off by default and after every refresh), key in
> `~/.tastybites-secrets-staging.php`; Privacy page "AI helpers" section on staging. Test: `wp tba ai-test`.
>
> **Release checklist (production, all at once, Jacob's go):** create the `tastybites-production` Console
> workspace ($20/month limit) and key → Jacob pastes it into `~/.tastybites-secrets-production.php` (chmod 600);
> run `scripts/migrations/ai-helpers-privacy.php` on production **before** anything else; merge `ai-features`
> to `main` (deploys the plugin); `wp plugin activate tastybites-assistant` + `wp tba setup`; add the cron line;
> `wp tba ai-test`; then switch helpers on one at a time.

Runs on the server when Shawn **publishes or updates a published post**, never on draft saves,
because he writes over several days. It fills in what is missing and never changes his
wording without approval. Built on WordPress 7.1's core AI client (Settings → Connectors) with
Claude as the provider. Estimate the cost before building; it should be small at his volume.

- [x] **2.1 Featured photo fallback** (no AI; on staging 2026-10-25): on publish, if there is no featured
      image, use the first image in the post (≥300px wide: 11 of Shawn's own picks are 480px or less). All 38
      published posts already have one, so no backfill was needed. Later: let AI pick the best photo instead.
- [ ] **2.2 Alt text:** describe each image that has none (Claude vision). Includes a one-off pass over
      the ~1,060 existing images, of which only 4 have alt text today.
- [ ] **2.3 Summary + social preview:** write the excerpt and the Open Graph/meta description when they
      are empty. Also add OG tags to the theme (not AI; see §3).
- [ ] **2.4 Review extraction:** pull restaurants, places and his scores out of trip posts (e.g.
      "Stan's was a solid 8.8/10") into review records linked back to the post. These feed
      The Bite Rating and the map. Must never invent a score: only what he wrote.
- [ ] **2.5 Category / destination suggestion:** suggest category and destination on publish. Applied
      automatically only when confident, otherwise left for Jacob.
- [ ] **2.6 Next stops suggestions:** when a post mentions future trips ("2027 Highlights…"),
      propose updates to the Upcoming trips list for Jacob to approve.
- [ ] **2.7 Comment triage:** decide on comments held for containing links (§3); approve genuine ones.
- [ ] **2.8 Spelling fixer (Jacob, 2026-10-25: critical, not a nice-to-have):** runs automatically
      within a minute or two of publish and fixes misspelled words. The AI only returns a list of word-level
      fixes (word, fix, confidence, sentence); PHP applies each one only if that word is in the post, only that
      word changes and the fix is a plausible spelling correction. A words-unchanged check (every other word
      identical) guards the result, and each pass is saved as a revision for undo. It leaves alone his slang,
      swearing, Canadian spellings and names (restaurants, dishes, places); confirmed names go on a site word
      list. If it isn't sure, the word stays as written and an **urgent email goes to Jacob and Shawn right
      away**; the first answer settles it. Grammar and style stay off. Before going live: report-only run over
      the existing posts so Jacob can judge accuracy (the same list can fix the archive).
      **5a built (staging 2026-10-26, report only):** `inc/spelling.php`; `wp tba spelling [--model] [--store]`;
      Tidy-ups → Spelling report. Model eval over all 38 posts: Opus 5.5 found the most real mistakes (~$0.02 a
      post), Sonnet the fewest, Haiku more false alarms; two Opus runs agreed on ~85% (the rest mostly
      questions). Final Opus run: 89 would fix, 73 would ask. PHP rules: only words in that line; small changes
      only (≤2 letters, ≤3 for long words, or joining/splitting a word: "cheese cake" → "cheesecake"); never
      Canadian/US variants or swearing (whole words); possible names (the AI flags them) and unsure words are
      asked. Next: 5b apply on publish, 5c question emails + 2.13 email settings.
      **Confidence round (Jacob's review, 2026-10-26):** Jacob confirmed every "would fix" on all 26 posts that
      had any. Then: the AI quotes a few words around each mistake so the exact spot is known (fixes the report
      highlighting the first "a"/"is" in a paragraph, and lets a wrong word used twice in a paragraph be fixed
      at that one spot); well-known places (Milwaukee, Fond du Lac) are fixable, names of people/businesses are
      asked unless the fix matches a spelling used elsewhere in the post or on the site (review titles, tags,
      destinations, site word list: "Lawtons" → "Lawsons"); every ask gets a **second opinion** (Opus, reasons
      first, sees the lines around it) that turns it into fix / keep / still unsure; a change that adds or
      removes a "not" is always asked. Result: 130 would fix, 10 would ask, 7 dropped as correct as written.
- [ ] **2.9 Content flags:** find and flag gaps instead of guessing. Examples from the 2026-10 review:
      reviews with no city, address, cuisine, price or visit date; restaurants mentioned in trip posts with
      no score; recipes without servings or with inconsistent times; typos; images without alt text.
- (?) **2.10 Flags dashboard + email loop:** an admin screen listing open content flags, plus a periodic
      email to Shawn (or Jacob) with the flags. **Shawn replies to the email in plain words** ("it's in
      St. Albert, Alberta, about $20 a head") and the AI applies the answer to the post. Open questions: how
      replies are received (a mailbox the site reads), verifying the sender, and showing what changed.
      **Cadence (Jacob, 2026-10-25):** per publish, not weekly, and no weekly cap. One email per publish pass
      with only that post's questions, most important first; posts published within the same hour share
      one email; no questions means no email; unanswered questions stay on Jacob's list and are never re-sent.
      Urgent spelling questions (2.8) go out immediately. Start with one-question link pages (REPORT §1.7).
- [ ] **2.13 Email settings (Jacob, 2026-10-25):** a settings screen for Jacob that controls, per email
      type (urgent spelling, per-publish questions, AI Mode, error alerts, etc.) and per person (Jacob,
      Shawn), who gets what. If Shawn is getting too many, Jacob can turn his off and handle them himself.
      Questions still reach Jacob's list whatever the settings. Build alongside the first email (2.8 / 2.10).
- [ ] **2.11 Structure fixer:** bring content in line with the template's ground rules (§0) instead of the
      template handling edge cases:
  - short standalone lines like "Day 2" or "Lunch" become proper subheadings;
  - runs of consecutive single images become a gallery;
  - empty headings and bullets removed, stray leading hyphens in lists removed;
  - legacy row/column layouts that squeeze text are unwrapped.
  Runs on publish, shows a before/after for approval (or applies automatically once trusted). Never
  changes his words.
  **Built (propose mode, staging 2026-10-25):** the top-level **Tidy-ups** menu (under Posts, Editors and up so Shawn can use it) shows each proposal as the real page,
  now vs. after, side by side (with a phone-width toggle), plus Apply / Reject; Apply saves a revision (undo =
  Restore). Rules, from a survey of all 38 posts: Day lines → H2; a bold route line right after a Day heading →
  H3; "Worth a second stop" / "Don't waste your time" → H2; a short bold line followed by text → H3; a bold line where up to 3 letters stuck to a bold word missed the bold ("V**iew from our room**") counts as bold and is fixed (bold lines
  followed by photos are usually captions, so they stay, for the AI step later); photos next to each other (2+) → one gallery, however Shawn boxed them (layout-only groups and galleries holding only photos, at any depth, are unwrapped; empty boxes removed; dragged photo widths dropped inside the gallery; a lone photo or an existing single gallery stays);
  typed list dashes and empty paragraphs/headings/list items removed. No Columns layouts exist, so no unwrap
  rule. Guards: same words in the same order and the same photos; every rewritten block closes its tags;
  untouched blocks are copied byte for byte; blocks WordPress can't re-save exactly are left alone; Apply is
  refused if the post changed since or is open in the editor. 25 proposals waiting on staging for Jacob.
  `wp tba layout [--store] [--verbose]` for a dry run.
- (?) **2.12 Image tidy-up on upload:** convert large PNG photos to JPEG/WebP and cap their dimensions so
      posts stay light (Italian Center is ~12 MB of PNGs today). Doesn't need AI.
- [ ] **2.14 Phone photo upload page (Jacob, 2026-10-26):** a simple, phone-friendly page on the site (one
      link Shawn bookmarks on his iPhone home screen) where he logs in with his normal site login and uploads
      photos straight from his camera roll into the Media Library, optionally tagged to a trip or draft post.
      **Why:** Shawn takes every photo on his iPhone, emails them to himself and uploads them from the computer,
      because WP admin is awkward on a phone. That strips the photos' data: checked 2026-10-26, his recent
      uploads (Road Trip USA Sept 2026) have **no camera, date or GPS left**, and many are only 480×640 (email's
      shrunk size). Direct upload keeps full size and the photo data.
      **Keep the data, but private:** read time taken, GPS and camera on upload and store them as private
      attachment fields (for the AI: order and day of photos, place names, which restaurant; §4 Photos), then
      strip GPS from the public files so readers can't see where Shawn's home is. Convert HEIC to JPEG
      (iPhone's default format; the host may not convert it). Checks: does the host's PHP keep EXIF on upload,
      HEIC support, upload size limits for full-size iPhone photos (often 3–6 MB each), and pair with 2.12 so
      the public copies stay light. Uses his existing login and WordPress's own permissions, no new accounts.
      Only helps photos from now on; the old ones' data is already gone.

---

## 3. Site features

- [x] **Comments**, designed to need no moderation (approved by Jacob 2026-10-24; built 2026-10-25, see §1):
  - Why: posts actively ask for it ("Well please leave feedback, positive or negative", "if you have
    recommendations let me know") but there is no comment form, contact page or email anywhere.
  - Publish immediately unless flagged; Antispam Bee for spam filtering (free, no account, runs on the server);
    hold comments that contain links; spam auto-deleted after a few weeks; the assistant can triage held ones (§2.7).
  - Open comments on all posts (currently closed everywhere) and design a comment section for the theme.
  - Consider "notify me of replies" and an email to Shawn when someone comments (he'd enjoy reading them).
- [ ] **Ways to follow:** visible RSS link, optional email signup; links to any video channels Shawn starts
      (his 2025 update promises cooking videos).
- [x] **Open Graph / meta tags** so Facebook and Instagram shares show a photo, title and description.
- [x] **Structured data:** Recipe schema on recipes, Review schema on reviews, BreadcrumbList.
- [ ] **Clean URLs:** `/recipes/…` and `/reviews/…` instead of `/recipes_content/…`; fix numeric slugs
      (`/414-2/` Costco, `/recipes_content/792/`, `/review_content/553/`), all with redirects.
- [ ] **Review info box:** city, address, cuisine, price, visit date, website/map. Filled by the assistant
      from what Shawn wrote, with gaps flagged (§2.9).
- [ ] **Recipe card:** servings, prep/cook/total, numbered steps, print, jump-to-recipe.
- [ ] **Trip post navigation:** day-by-day contents and a styled "Worth a second stop / Don't waste your
      time" summary box. Depends on headings being real headings (§2.11).
- [ ] **Places hub:** destinations as navigation, with trips and reviews per place.
- [x] **Fuller footer:** contact, copyright, privacy note (needed with comments or a newsletter).
- (?) **Map of everywhere he's eaten:** one pin per reviewed restaurant, linking to its post. Depends on
      §2.4 for the data. Photo GPS data from §4 could place pins.

---

## 4. "AI Mode": voice-first authoring (idea, needs discussion)

> See `docs/research/authoring-ai-2026-10/REPORT.md` (memoir-app precedent, "arrange, don't rewrite", speech-to-text
> bake-off, disclosure options).

Shawn talks; AI turns it into posts in his voice. Proposed by Jacob, 2026-10-24.

### Flow
1. **Talk.** Shawn opens AI Mode in the editor and talks about the trip, meal or opinion. He can come
   back over several days and keep adding to the same session, matching how he writes now.
2. **Photos.** He adds his photos to the session (see open questions).
3. **Choose what to make.** At the end, AI Mode asks which content types he wants from this material.
   Blog post is pre-selected:
   - **Blog post** (default, long form): the main output, in his voice and structure (day by day, food highlights).
   - **Restaurant reviews:** one per place he rated, extracted from what he said, linked to the blog post (same as §2.4).
   - **Recipe** (rare): only when he walked through how to make something, e.g. "Thanksgiving night, here are my steps".
4. **Feedback (Jacob, 2026-10-25).** Before anything reaches the editor, the AI Mode screen shows the
   rendered draft next to the AI's notes and questions ("I wasn't sure where these 3 photos go", "you said
   the price was great but not what it was"). Shawn answers by talking or typing, says a part again, or
   skips; only the affected part is redone. The §2 helpers (alt text, excerpt, categories, spelling) run on
   this draft too, so he sees the finished post before publishing. Gaps are asked here, not by email.
5. **Review.** Everything lands as **drafts** in the normal editor for hands-on changes. Shawn reads, edits and publishes himself. AI Mode never publishes.

### Principles
- Write in **his** voice: first person, his phrasing and humour, swearing included. Use his existing
  posts as style examples.
- Never invent facts: no restaurant, dish, price or score he didn't say. Flag gaps instead
  ("You didn't give the Viking Café a score; want to add one?").
- Reviews are a by-product of the blog post, not a separate form he fills in. He stopped using the
  review form in Sep 2024 and now puts his reviews inside trip posts.

### Open questions
- **Shawn has publicly said he doesn't use AI.** His post "Where did 2025 go?" (Dec 2025) tells readers:
  "I don't use any AI hacks, so everything you get is me". AI-written posts would contradict that.
  **Jacob will talk to Shawn about it.** Options: AI Mode only transcribes and arranges his own spoken words
  (light touch); he updates that line; or AI stays limited to behind-the-scenes metadata (§2).
- **Images, the big one.** How do his photos get in and get placed?
  - Upload a batch from the laptop into the session; AI describes each one (vision) and places it next
    to the matching part of the story, with a caption and alt text he can adjust.
  - Use the photos' own data where present: the time taken gives the order and day; GPS gives the
    place, which could also feed the map. His iPhone records it, but emailing photos to himself strips it (checked 2026-10-26), so this needs the direct upload page (2.14); note that
    WordPress doesn't store GPS by default.
  - What if a photo matches nothing he said? Put it in an "unplaced photos" gallery at the end.
- **Voice to text:** browser dictation (simple, quality varies by browser) or recording audio and
  transcribing on the server (better accuracy, a cost per minute). Pick after a quick test with his voice.
- **Where it lives:** a panel inside the post editor (familiar to him) or a separate simple screen
  (fewer buttons). Lean towards the simplest screen he can't get lost in.
- **Cost and privacy:** voice recordings and drafts go to AI providers. Agree with Shawn what is OK,
  delete recordings after transcription, and estimate the monthly cost.
- **Editing by voice:** can he say "make the Dysart's bit shorter" after the draft exists? (Later phase.)

---

## 5. Maintenance backlog

Found while reviewing the site in October 2026. Test each on staging first.

- [x] Update outdated plugins: done by Jacob on staging and production, 2026-10-24.
- [~] **Image optimisation on production** (2026-10-25): LiteSpeed Cache linked to QUIC.cloud in anonymous mode
      (no account; `server_ip` set to 198.100.159.124). Lossy JPEG/PNG + WebP (also in srcset), originals kept as
      `.bk` files (LiteSpeed Cache → Image Optimization can revert), EXIF kept, new uploads auto-requested. The free
      tier takes one small batch every few minutes, so the ~1,040 images finish over days via WP-Cron. Check
      progress: `wp --path=public_html litespeed-image status` (WP-Cron requests and pulls every 15 min). First result: logo 132 KB → 33 KB, no visible change.
      Not on staging (basic auth blocks QUIC.cloud).
      **Too slow in anonymous mode** (checked 2026-10-25): quota 100 images, one small group per request, ~15 min
      apart; 1 of ~1,040 groups done after 30 min. Next step for Jacob: LiteSpeed Cache → General → link a free
      QUIC.cloud account using the admin address (account sign-up is a manual step), then check
      `litespeed-image status` again.
      Done 2026-10-25: Jacob linked a free QUIC.cloud account. 7 groups optimised the same hour, then the new account's
      daily quota ran out; WP-Cron carries on each day (the daily allowance grows as the account ages). WebP is served
      (e.g. `Gengies_Logo_Light-1-300x180.png.webp`). Nothing to do but check progress now and then.
- [x] Turn off `WP_DEBUG` on production (2026-10-25; WP_DEBUG, WP_DEBUG_LOG and WP_DEBUG_DISPLAY now false; it was
      showing PHP notices to visitors). Backup: `~/deploy/production/wp-config.before-debug-off-20261025.php`.
- [x] Old logs deleted on production (2026-10-25): the ~19 MB `error_log`, `debug.log` and 10 small `error_log`s
      left in core folders by bots requesting PHP files directly. Root cause of the big log: the `WP_DEBUG` lines had
      been added *below* `require wp-settings.php` in `wp-config.php`, so they never took effect on the site and logged
      two "already defined" warnings on every request. Moved above "stop editing" (backup:
      `~/deploy/production/wp-config.before-debug-move-20261025.php`). Remaining host noise: an Imagick version warning
      (WHC's PHP build, harmless).
- [x] Replace hard-coded image attachment IDs: the redesign uses the Customizer logo (falls back to `385`); the blog card icon is gone.
- [x] Remove the Font Awesome 4.7 CDN link in `header.php` (redesign). The Font Awesome *plugin* stays:
      it draws the Bite Rating drumsticks.
- [x] Query categories by slug instead of looping over every category (redesign).
- [x] Remove the redundant nested loop in `render_rating_shortcode()` (redesign).
- [x] Escape output in templates (redesign rewrote them).
- [x] Add a theme header to `style.css` (redesign).

---

## 6. Content concerns (parked)

Real issues inside the posts themselves. Parked on purpose: they need Shawn, or the AI assistant
(§2.9–2.11), not template changes. From the fresh-eyes review, 2026-10-24.

- Typos and small errors: "7.2Tasty Bites out of 10" (King's Head), "When its a snowy" (Nola),
  "The walk from hell than ended…" (Nola heading), "Dysart''s", trailing space in "Thanh Thanh Restaurant".
- Trip post subheads ("Day 1", "Lunch") typed as plain paragraphs, not headings (→ §2.11).
- 100+ consecutive single images in long trip posts instead of galleries (→ §2.11).
- Legacy row/column layouts inside some posts (Atlantic Kitchen, Buttermilk Biscuits) (→ §2.11; the template
  also gets a generic wrap rule).
- Ingredient lists with leading hyphens ("• -1 cup flour") and empty bullets.
- Inconsistent recipe times ("Over Night Marinade/ less than 1 hour" vs "20 min").
- Reviews missing location, cuisine, price and visit date; restaurants in trip posts have no review entries (→ §2.4, §2.9).
- Large PNG photos (Italian Center ~12 MB) (→ §2.12).
- About page: "Today" photo may need a better source photo (pending template check in §1).
- The 4 "About Content" posts are also public as thin standalone posts (fold into /about/ or noindex: decide later).
- Content age: most reviews and opinion pieces are from 2024; the 2025 recap mentions trips with no posts.
- Tone: the homepage "Meet Shawn" photo shows his "…all you f*ckers" t-shirt. On-brand, but a deliberate
  choice for Jacob and Shawn.

---

## Decision log

| Date | Decision | Why |
|---|---|---|
| 2026-10-24 | Deploy via GitHub Actions; staging on a WHC subdomain | Repeatable deploys with rollback; a safe place to test |
| 2026-10-24 | Design direction A · Road Journal | Jacob and Shawn both preferred it |
| 2026-10-24 | Restyle the classic theme + `theme.json`, not a full block theme | Keeps the Site Editor away from a non-technical author |
| 2026-10-25 | Image optimisation via LiteSpeed/QUIC.cloud, on production as a backlog item | Modern Image Formats made bigger AVIFs and broke a file record on staging |
| 2026-10-25 | RSS icon out of the social row; text link kept | `/feed/` is for feed readers and looked broken to people clicking it |
| 2026-10-25 | Shawn-facing approvals (categories, rating wording, privacy, comment emails) batched into one check-in | Fewer interruptions for Shawn |
| 2026-10-24 | Comments: auto-publish with spam filtering, no approval queue; do right after the redesign | Nobody will moderate; bots are the main risk |
| 2026-10-24 | AI acts on publish only, never on draft saves | Shawn writes over several days |
| 2026-10-24 | Design first; AI work after, behind the scenes | Minimal disruption to Shawn's workflow |
| 2026-10-24 | Template handles ground rules; content is changed to fit them (by AI later), not the other way round | Keeps the theme from filling up with edge cases |
| 2026-10-24 | Polish everything on staging before any production rollout | Launch once, launch well |
| 2026-10-25 | Two AI features: the quiet assistant (§2, his normal workflow) and AI Mode (§4, a separate workflow with its own screen) | Matches the research; AI Mode's screen handles the back-and-forth, the normal editor handles edits |
| 2026-10-25 | Spelling fixed automatically after publish (2.8); unsure words emailed to Jacob and Shawn immediately | Typos matter to the content; code checks only single words change |
| 2026-10-25 | Question emails per publish, no weekly 5-question cap; Jacob controls who gets which email (2.13) | A busy week would exhaust a weekly cap; Jacob can take over if Shawn is overrun |
| 2026-10-25 | AI Mode drafts get the §2 helpers before publish (exception to "publish only") | An AI Mode draft is complete, not half-written |
| 2026-10-25 | Layout tidy-ups may remove typed dashes and empty lines inside recipe/review patterns, but never change their structure | ~90 dashed ingredient lines are all inside patterns; text-level fixes don't affect the pattern |
| 2026-10-26 | AI spend limit $20/month per environment; one key per environment in its own Console workspace, stored in `~/.tastybites-secrets-<env>.php` | Jacob: a hard ceiling while it is tested; staging can never use the production key |
| 2026-10-26 | AI calls go straight to Anthropic's API (`tba_ai()`), not WordPress's AI Client | The core client needs an extra provider plugin and can switch models quietly; the alt-text backfill needs the Batches API anyway |
| 2026-10-26 | Privacy page says "an AI language model (LLM) service", not a vendor name | Jacob: keep the wording general |
| 2026-10-26 | $1/day spend limit in the plugin, on top of the Console's $20/month | A runaway bug stops within a day |
| 2026-10-26 | Spelling uses Opus 5.5 | Most real mistakes found in the 38-post test; cost is pennies a month |
| 2026-10-26 | Tidy-ups get a top-level menu open to Editors | Jacob: Shawn should be able to find it if he uses it; he is an Editor |
| 2026-10-24 | Research that reads the web runs in a separate agent | Keeps outside content (possible prompt injection) out of the main session |
