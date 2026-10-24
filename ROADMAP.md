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

## 1. Redesign: "Road Journal" (in progress)

Direction **A · Road Journal** was chosen on 2026-10-24 by Jacob and Shawn.
Design canvas: https://claude.ai/artifact/Uu314G9Q5YizEABVDquG9c (private; also has the
rejected directions B and C for reference).

Look: warm paper ground, Young Serif headlines over Source Sans 3 body, tomato `#A8321C`
and mustard `#C8921A` accents on charcoal `#1F1B17`, passport-stamp labels.

Approach: restyle the existing classic theme and add a `theme.json` for design tokens and
editor styles. Not a full block theme: that would expose the Site Editor to a non-technical author.

### How each homepage section gets its content

Every section must work with **no extra effort from Shawn**. When data is missing, a
section hides itself rather than showing something broken.

| Section | Content source | Needs from Shawn | Later improvement |
|---|---|---|---|
| Header + search | WordPress search | Nothing | Search results page designed to match |
| Hero: latest trip | Newest post in the Road Trips category; its featured photo and first sentence | Nothing | Stamps ("10 days · 2 countries") from trip fields the assistant fills in |
| Road Trips & Travels | Latest posts in Road Trips | Nothing | |
| Destination stamps (NOLA, SFO…) | A *Destination* taxonomy; each term has a short stamp code, set once per place | Nothing (Jacob or the assistant tags) | Assistant suggests the destination on publish |
| The Bite Rating | Existing 7 reviews (`review_content`, `review_rating`) | Nothing | Reviews extracted from trip posts (§2.4) |
| From Shawn's Kitchen | Existing recipes (`recipes_content`) | Nothing | |
| My 2 Cents | My 2 Cents category | Nothing | |
| Meet Shawn | Fixed text and photo, editable in one admin settings screen | Nothing | |
| Next stops | An *Upcoming trips* list (month + places) in one admin settings screen. Entries hide themselves once their month has passed | Nothing (Jacob keeps it current) | Assistant proposes updates when a post mentions future plans (§2.6) |

### Tasks
- [ ] Restructure content: categories Road Trips, Restaurant Reviews, My 2 Cents, The Early Years
      (+ About Content). Re-tag the existing 15 posts. Fix the "Grandville Island" spelling. Remove the
      duplicate Uncategorized. Needs Shawn's OK on category names.
- [ ] `theme.json` with the Road Journal tokens (colours, fonts, spacing), so the editor matches the site.
- [ ] New header, footer and homepage sections per the table above.
- [ ] Single post template for long trip posts: readable measure, day headings, big photos, gallery lightbox.
- [ ] Category/archive pages, search results page, About page ("Meet Shawn" + Early Years).
- [ ] Recipe and review templates restyled.
- [ ] Comments section on posts (see §3).
- [ ] Check every template on desktop and phone in the browser before each deploy.
- [ ] Remove Bootstrap and the Font Awesome 4.7 CDN once nothing uses them.

---

## 2. The quiet assistant (AI in the background)

Runs on the server when Shawn **publishes or updates a published post**, never on draft saves,
because he writes over several days. It fills in what is missing and never changes his
wording without approval. Built on WordPress 7.1's core AI client (Settings → Connectors) with
Claude as the provider. Estimate the cost before building; it should be small at his volume.

- [ ] **2.1 Featured photo fallback** (no AI): on publish, if there is no featured image, use the
      first image in the post. Later: let AI pick the best photo instead.
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
- (?) **2.8 Proofread:** optional button in the editor that fixes spelling and grammar only, keeps his
      voice and swearing, and shows each change for him to accept.

---

## 3. Site features

- [ ] **Comments**, designed to need no moderation (proposed 2026-10-24, awaiting Jacob's OK):
  - publish immediately unless flagged; Antispam Bee for spam filtering (free, no account, runs on the server);
  - hold comments that contain links; spam is auto-deleted after a few weeks;
  - open comments on all posts (currently closed everywhere) and add a comment section to the theme.
- [ ] **Search** (part of the redesign header).
- [ ] **Open Graph / meta tags** so Facebook and Instagram shares show a photo, title and description.
- [ ] **Clean recipe/review URLs:** `/recipes/…` and `/reviews/…` instead of `/recipes_content/…`,
      with redirects from the old addresses.
- (?) **Map of everywhere he's eaten:** one pin per reviewed restaurant, linking to its post. Depends on
      §2.4 for the data. Photo GPS data from §4 could place pins.
- (?) **Recipe plugin** (e.g. WP Recipe Maker: jump-to-recipe, print, Google rich results). Only if
      he starts writing recipes again; no new recipes since Sep 2024.

---

## 4. "AI Mode": voice-first authoring (idea, needs discussion)

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
4. **Review.** Everything lands as **drafts**. Shawn reads, edits and publishes himself. AI Mode never publishes.

### Principles
- Write in **his** voice: first person, his phrasing and humour, swearing included. Use his existing
  posts as style examples.
- Never invent facts: no restaurant, dish, price or score he didn't say. Flag gaps instead
  ("You didn't give the Viking Café a score; want to add one?").
- Reviews are a by-product of the blog post, not a separate form he fills in. He stopped using the
  review form in Sep 2024 and now puts his reviews inside trip posts.

### Open questions
- **Images, the big one.** How do his photos get in and get placed?
  - Upload a batch from the laptop into the session; AI describes each one (vision) and places it next
    to the matching part of the story, with a caption and alt text he can adjust.
  - Use the photos' own data where present: the time taken gives the order and day; GPS gives the
    place, which could also feed the map. Check whether his camera/phone keeps this, and note that
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
- [ ] Turn off `WP_DEBUG` on production (it's on, and `public_html/error_log` is ~19 MB; it is not publicly readable).
- [ ] Replace hard-coded image attachment IDs: the logo (`385` in `header.php`, could use WordPress's custom
      logo setting) and the blog card icon (`384` in `front-page.php`). Likely solved by the redesign.
- [ ] Remove the Font Awesome 4.7 CDN link in `header.php`. The icons use FA 6 classes from the Font Awesome plugin.
- [ ] Query categories by slug (`'category_name' => 'blog'`) instead of looping over every category in
      `front-page.php` and `page-about.php`.
- [ ] Remove the redundant nested loop in `render_rating_shortcode()` (`functions.php`).
- [ ] Escape output in templates (`esc_attr( get_the_title() )` in the wrapper `id`s, `esc_url()` on image URLs).
- [ ] Add a theme header (`Theme Name:` etc.) to the top of `style.css` so WP admin shows proper theme details.

---

## Decision log

| Date | Decision | Why |
|---|---|---|
| 2026-10-24 | Deploy via GitHub Actions; staging on a WHC subdomain | Repeatable deploys with rollback; a safe place to test |
| 2026-10-24 | Design direction A · Road Journal | Jacob and Shawn both preferred it |
| 2026-10-24 | Restyle the classic theme + `theme.json`, not a full block theme | Keeps the Site Editor away from a non-technical author |
| 2026-10-24 | AI acts on publish only, never on draft saves | Shawn writes over several days |
| 2026-10-24 | Design first; AI work after, behind the scenes | Minimal disruption to Shawn's workflow |
