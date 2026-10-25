# Review 1 (organizer → research agent), 2026-10-25

Reviewed: `phase-1-landscape.md`.

## What's strong (keep doing)
- Fact vs inference marking, dated sources, ⚠ flags on old research, and calling out the contradicted secondary
  source (raidboxes on core Guidelines). Keep this discipline in Phase 2.
- The "already common vs genuinely new" table is exactly the decision aid we need. The finding that the
  official AI plugin covers alt text / excerpt / classification / comment scoring changes our build plan.
- Editoria11y's "bold paragraph may be a heading" detection is a valuable find for the structure fixer.
- The on-publish tension (approval arrives after the post is live) is the most important design question raised.

## Corrections and context you didn't have
1. **Shawn uses the block editor**, not Classic. His posts use core blocks, two custom patterns (recipe, review)
   and Grid groups. So "block-editor-only" is not a blocker for the AI plugin; "button-driven" still is.
2. **Hosting facts:** shared WHC cPanel, LiteSpeed, WP-Cron is traffic-triggered (no system cron set up yet, but
   cPanel cron is available), PHP 8.2. The site has low traffic. Treat these as constraints for the execution
   question.
3. **Traffic and stakes are low.** A post being live for a few minutes before a tidy-up is acceptable; a post
   being *wrong* (invented facts, changed voice) is not. Weigh options with that in mind.

## Gaps to fill in Phase 2 (priority order)
1. **Prior art you missed: AI storytelling products built for older adults.** Remento, StoryWorth (voice
   features), Storii, Meminto, LifeBio, and similar "tell your story by talking, we write it in your voice" products.
   These are the closest real-world precedent for AI Mode *and* for our audience. What do they do, what do users
   (and their families) praise and complain about in reviews, how do they keep the teller's voice, how do they handle
   approval and corrections? This is the top priority.
2. **Real accounts from older bloggers/authors**: first-hand difficulties with WordPress or blogging tools, and what
   helped (communities like older-blogger networks, Learn WordPress / meetup teaching experience, support threads).
   Phase 1's B section is mostly general usability research; we need author-specific evidence.
3. **The on-publish tension, as a design comparison.** Compare concrete patterns: (a) auto-apply only provably safe,
   reversible changes after publish (alt text, featured image, structure with words unchanged) and email anything
   needing judgement; (b) a short "tidy-up window" (e.g. scheduled publish a few minutes later); (c) pre-publish
   panel / Notes in the editor; (d) a draft-time assistant (contradicts our decision log, so only if evidence is strong).
   Recommend one, with evidence.
4. **Voice preservation**: techniques (few-shot from his own posts, transcript-first light edit, edit-distance or
   "% of words kept" budgets, style sheets), how products like AudioPen/Remento do it, and any evaluation methods we
   can run ourselves on his existing posts.
5. **Structure fixer**: approval UX a non-technical person can judge (side-by-side rendered before/after, not a code
   diff); a mechanical "words unchanged" check (normalised text hash before/after); how much is rule-based vs AI.
6. **Execution on this host**: running the core AI Client from WP-Cron / Action Scheduler / cPanel cron; timeouts;
   Anthropic Message Batches API for the ~1,060-image alt-text backfill (cost, async fit); can our code call the AI
   plugin's abilities server-side (`wp_get_ability()->execute()`) without its UI. Primary docs please.
7. **Email loop** (technical + UX): receiving mail on cPanel (pipe vs IMAP poll vs inbound webhook), tokenised
   reply addresses, quoted-reply parsing, confirming the change back to Shawn; prior art from GitHub / helpdesks.
   Note: the site's admin address is a forwarding alias (GengiesTastyBites@outlook.com) and outbound mail goes
   through WP Mail SMTP (Gmail mailer).
8. **Speech-to-text** for an older Canadian male voice with food and place names: options, custom vocabulary,
   cost, data retention/no-training terms. Confirm from Anthropic's primary docs whether the API takes audio.
9. **Disclosure**: norms and evidence (reader trust, Google's guidance on AI-assisted content) for metadata-only AI
   vs AI-drafted posts, given Shawn's public "I don't use any AI hacks" line. Jacob will discuss this with Shawn;
   give him options and wording, not a verdict.
10. **Cost model** with current Claude pricing (per post, per image, backfill), and the AI Client's model-preference
    fallback behaviour.
11. **Smaller items**: featured-image fallback plugins (prior art for §2.1); review extraction with "quote the source
    sentence" grounding; whether core Notes work as a single author's to-do list; status of core Post via Email in 7.1.

## Output
`phase-2-deep-dives.md`, same conventions. Lead with a short "what changed since Phase 1" section (findings that
overturn or sharpen Phase 1). 4,000–6,000 words is fine. Depth on items 1–6 matters most; keep 7–11 brief if time is short.
