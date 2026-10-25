# Phase 2: deep dives

Research agent, 2026-10-25, responding to `review-1.md`. All web sources accessed 2026-10-25. Same conventions as
Phase 1: **Fact** = stated by a cited source; **Inference** = my reading. ⚠ old = source older than ~2 years.
Source IDs here are `[Pn]` (Phase 1 IDs `[Sn]` are reused where relevant). I saw no prompt-injection attempts. Trustpilot
pages include Trustpilot's own AI-written summaries; I quote reviewers' words, not those summaries.

## What changed since Phase 1

1. **The closest precedent for AI Mode is the family-memoir industry, and it has converged on a pattern.** Remento,
   Storyworth and Storii all keep the **original recording and a near-verbatim transcript** next to any AI rewrite, and let
   the family choose how much rewriting to apply. Storyworth deliberately keeps phone answers word-for-word. Remento's storytellers get
   a **link by email or text with no login or password**. In reviews, the main complaints are about **effort, nagging,
   account access and support**, not about the AI distorting voices. I found only one voice-loss complaint, about a
   different service [P1–P9].
2. **Core already ships the structure fixer's approval screen.** WordPress 7.0's Visual Revisions shows a rendered
   before/after inside the block editor (green additions, red deletions, yellow changed blocks) with a **Restore** button
   [P20]. We don't need to build a diff viewer.
3. **Background execution on this host should not rely on WP-Cron's loopback.** LiteSpeed kills PHP when the client
   disconnects [P26]. Page caching and low traffic make traffic-triggered cron miss its schedule [P24]. Per CLAUDE.md, the
   host throttles requests from the server to itself. PHP from the command line has **no time limit by default** [P27]. The
   recommendation is a cPanel cron that runs WP-CLI.
4. **Confirmed from primary docs: the Claude API takes no audio input** [P31]. Message Batches give a **50% discount**, finish
   within 24 h (usually under 1 h), keep results for **29 days** and are **not** zero-data-retention eligible [P29, P30, P32]. The full
   1,060-image alt-text backfill costs **about $0.10 to $5**, depending on model (estimate, §6).
5. **Grounding:** Claude's Citations feature guarantees quotes point into the source, but it **cannot be combined with
   structured JSON output** [P35]. Inference: for review extraction, ask for a verbatim quote inside the JSON and check it in PHP.
6. **The email loop has a simpler variant.** Following Remento, the email could carry a link to a one-question page instead of
   needing a parsed free-text reply. Inbound-parse services already split out the stripped reply and a per-message token [P38].
7. **Disclosure:** a 2026 experiment found **one-line AI disclosures did not lower trust, while detailed ones did** [P44].
   Google asks for "How was this created?" disclosure "when it would be reasonably expected" [P42].
8. **Voice:** large language models (LLMs) over-correct by default when asked to fix text [P13–P15]. Imitating *informal blog* style is the hardest case
   [P16]. So a "light touch" has to be enforced and measured, not just requested in the prompt.

---

## 1. AI storytelling products for older adults (top priority)

### What they do

| Product | Capture | Text produced | Voice-keeping mechanism | Approval / corrections |
|---|---|---|---|---|
| **Remento** | Weekly prompt by **email or text**, link opens straight to the prompt; "no apps, passwords or technical expertise"; audio or video up to 30 min, can pause/redo; photo prompts [P1, P2] | Three modes the editor can switch any time: **cleaned transcript** (fillers removed, punctuation added), **first-person story** ("as if they had written it themselves"), third-person biography; length adjustable [P1] | Original recording always kept; printed book has a **QR code to the recording**; CEO: stories "retain all the tone and personality of the storyteller" [P1, P2] | Editor previews versions and presses Save, "full control over the final version"; manual sentence edits; default style set per project. **No stated limit on what the AI may add, and no warning about invented content** [P1] |
| **Storyworth** | Weekly email prompts; **Story Calls** by phone, landlines included; "Magic Interviews" (2026) where software asks follow-up questions; Family Calls with a relative [P3, P4] | Story Calls: **"transcribed word-for-word"**. Magic Interviews: turned into a narrative. Family Calls: a "cleaned up transcript" [P4] | Recordings kept, QR codes and an audiobook so families hear it "in their own voice" [P4] | Storyteller can edit any time; no pre-insert approval step described [P4] |
| **Storii** | Automated **phone calls** to the senior (landline OK), scheduled by a caregiver, 5–15 min, up to 3×/week [P5] | "AI Biography Rewrites" that fix pauses and mistakes; **both raw transcription and AI version kept** [P5] (vendor claim) | Raw kept alongside | Not described |
| **LifeBio Memory** | App for reminiscence in care settings; NIH-funded trial planned [P6] | Life Story Books | Not found | Not found |
| **Meminto** | AI-supported book platform | n/a | No independent reviews found | n/a |

A September 2026 commentary on Storyworth's voice tools names the core tension. "A verbatim transcript preserves the loops,
digressions and idiosyncratic phrasing of speech", while a polished narrative "is a different kind of document". It also says guided
follow-up questions "place a frame around the past" [P3]. Practitioners outside the industry agree: a memoir coach argues AI drafts
drain a memoir's authenticity [P10]. A writer who succeeded with AI used it to **retrieve his own words from archived
letters and transcripts, not to write for him** [P11].

### What users praise and complain about (first-hand reviews)

- **Remento, Trustpilot (4.8/5, ~1,750 reviews, mostly 2025–26)** [P7, P8]:
  - Praise from older tellers: "considering I am 87" (Oct 2026); "a great way to help us older people by using prompts";
    "Instructions are simple and easy"; "I was able to edit easily and add photos". One teller values editing because
    "when I just talk" she repeats herself.
  - Complaints:
    - Effort and output: "My father recorded 7 prompts (in 1 year). He's 74!"; an elderly relative's material was **deleted when the year ran out**, causing "huge emotional disappointment".
    - Confusing setup: an elderly reviewer said the three recording methods were unclear until late.
    - Nagging: "daily emails AND texts telling me to get online".
    - Access: account lockouts, an iPad that wouldn't work, a missed prompt after a phone change.
    - Reading and redoing: a teller with **macular degeneration** finds the screen hard to read; another wants to **re-record a small section** instead of the whole story.
    - Support: slow email support and no phone line.
  - **No reviewer I found complained that the AI-written story didn't sound like the teller.** Absence of evidence: reviewers
    may not compare closely, and many books are read by family, not the teller.
- **Storyworth (review aggregators, competitor blogs)** [P9]:
  - Prompts quietly stop arriving.
  - Parents don't answer ("didn't answer a single question for a year").
  - Weekly writing feels like homework: "writing every week is just too much for people in their late 70s".
  - Paying to extend access after expiry.
  - These are secondary quotes, partly from Remento's own marketing, so they're weak evidence.
- **Voice loss, other service:** one Feb 2026 review of a memoir-editing service says the AI "erased their distinctive voice and
  invented content", and the original draft couldn't be recovered [P12]. A single review, but it matches our two red lines.

### Lessons for us (inference)

1. **Keep the raw material forever and show it.** Every serious product keeps the recording and a near-verbatim transcript. For
   Shawn: store the transcript with the draft so he and Jacob can check any sentence against what he actually said, and keep
   recordings at least until the post is published (privacy question for Jacob and Shawn).
2. **Offer a dial, default to the light end.** Remento's cleaned transcript and first-person story map neatly onto our "light
   touch" vs "draft it for me" question. Storyworth's choice of verbatim for phone answers is a business decision built on voice
   authenticity.
3. **The failure mode is engagement, not AI quality.** Tellers stop. Weekly cadence is "homework" for people in their late 70s.
   Nagging annoys. For the quiet assistant's email loop, that means **few emails, batched, never daily**, each answerable
   in one step.
4. **No-login links work for this audience** (Remento's core design). This is the strongest prior art for §2.10: a link that opens one
   question, rather than a reply that must be parsed.
5. **Partial redo matters.** Tellers want to fix one bit, not redo everything. AI Mode should let Shawn re-say one section.
6. **None of these products promises "no invented facts".** Our "never invent" rule plus quote-checking (§10) would go beyond them.

---

## 2. First-hand accounts from older bloggers

Evidence specific to older *bloggers* is thin. Here is everything usable I found.

- **Older Bloggers Survey (Write Into Life, May–June 2018, n=123; 40% aged 55–64, 34% 65–74, 11% 75+)** ⚠ old, self-selected,
  and from before the block editor (Dec 2018) [P17]:
  - **87% blog on WordPress; 92% write and publish on a laptop or desktop** ("Easier to do on a bigger screen"; "iPad is too small and slow").
  - **47% agreed "technical issues with a blog can be a challenge."** Specific issues: uploading pictures is "very hard"; front page vs
    static page options confusing; "WP.org and WP.com are confusing to me"; platform changes breaking things (Blogger stopped emailing comments
    after GDPR); "Phone app sometimes crashes"; **difficulty getting answers to technical questions** came up repeatedly.
  - **Physical:** ~18% limited by vision, hearing, energy, mobility or pain. **Low energy was the main brake on how often they post.** Requests
    for "larger and bolder print".
  - **What helped:** someone to help ("especially if you have someone helping that is an IT person"); lowering expectations about posting
    frequency; short posts on bad days. Enjoyment was high: 96% satisfied, 70% value "contact with the outside world".
- **Research on older content creators:**
  - Interviews with 20 older bloggers found blogging supports identity and meaningful engagement in retirement (Brewer & Piper, CHI 2016, abstract via search) [P18] ⚠ old.
  - A CSCW 2021 study of older content producers found **time constraints, lack of professional support, and a preference to create collaboratively** [P18] ⚠ old.
  - ASSETS 2021: volunteers maintaining a retirement-community website struggled because accessibility guidance is written for experts [P19] ⚠ old.
- **Long-time bloggers on the block editor (ages unknown):** one blogging since 2011 called it "new, less intuitive, and
  needlessly complicated" and objected to having a "blogging experience" when he "simply wants to write words and add pictures"
  (Apr 2021) [P19b] ⚠ old. This echoes the 2024 roundup in Phase 1 [S57].
- **Memoir-product reviews (§1)** are the richest *recent* first-hand evidence from people in their 70s and 80s: effort and energy,
  screens hard to read, confusion over options, wanting someone to help.

**Inference for Shawn:** the evidence fits his situation and our plan. He writes on a laptop. Images and "technical bits" are the
hard part. Help from a trusted person (Jacob) is the main support. Energy, not skill, limits his output. So features that remove
work (featured photo, alt text, structure) are well aimed, and anything that asks him to do something should be rare and one-step.

---

## 3. The on-publish tension: options compared

**Constraints (from review-1):**
- Traffic and stakes are low. A post being live for minutes before a tidy-up is fine; a post being *wrong* is not.
- WP-Cron is traffic-triggered on a LiteSpeed-cached site. Shawn is not technical.

**Relevant facts:**
- **Core Visual Revisions** (7.0): rendered compare, highlighting, Restore [P20].
- **Scheduled posts** depend on WP-Cron firing. On low-traffic or cached sites they routinely become "Missed schedule" unless a real cron runs [P24].
- **Facebook's link preview** is taken from the page when it is first shared and re-scraping is manual (third-party guides) [P25]. So anything that affects the *share card*, such as the featured image and description, should be in place at the moment of publishing.
- **Pre-publish panel and Notes** are supported extension points [S41, S12].
- The AI plugin's Editorial Updates applies AI rewrites automatically and relies on revisions for rollback [S13]. That is the model we reject for words.

| Option | What Shawn sees | Readers might see | Reliability here | Fits "needs nothing from him"? | Verdict |
|---|---|---|---|---|---|
| **(a) Tiered after-publish:** instant non-AI fixes at publish; AI metadata in the background minutes later; anything needing judgement goes to email/Jacob | Nothing new | Missing alt text or excerpt for a few minutes; never changed words | Good with a real cron (§6) | Yes | **Recommended** |
| **(b) Tidy-up window:** Publish quietly becomes "scheduled in 10 min" | "Scheduled" instead of "Published": likely confusing (inference from NN/g on unexpected behaviour [S49]) | Nothing until tidy | Weak: missed-schedule risk on this host unless real cron [P24]; if the job fails, the post is late | Changes his core action | Not recommended |
| **(c) Pre-publish panel / Notes** | A checklist or Notes he must read before publishing | Only approved content | Good | No: adds a step on every post; the AI plugin's notes cover readability/SEO we don't want [S12] | Use only for one *informational* line (see below) |
| **(d) Draft-time assistant** | Suggestions while writing over several days | n/a | Runs on half-finished drafts, repeated cost | Contradicts the decision log | No evidence strong enough to reverse the decision |

**Recommendation: (a), in four tiers.**

- **Tier 0, synchronous at publish, no AI, milliseconds:**
  - featured-photo fallback (first image);
  - deterministic structure rules that pass the words-unchanged check (§5).
  - Doing these synchronously means the first share card is right [P25], and nothing depends on cron.
- **Tier 1, background job within ~5 min, AI, metadata only (no visible words):**
  - alt text, excerpt/meta description;
  - category/destination **only from existing terms above a confidence floor** (the AI plugin already uses this design: 0.6 floor, existing-only [S14]);
  - review records created as **pending/draft** with quoted evidence.
  - Each change is a normal post update, so it appears in revisions.
- **Tier 2, needs judgement (scores missing, ambiguous headings, a "Next stops" change):**
  - goes into a flags list;
  - Jacob gets anything technical; Shawn gets a **batched** email at most weekly with one-tap answer links (§7).
  - Tier 2 never edits his words without approval.
- **Optional, informational only:** one line in the pre-publish panel, e.g. "No cover photo picked: I'll use your first
  photo." It needs no action and tells him what will happen, which follows NN/g's advice to explain clearly [S49]. Jacob decides whether even this is too much.

For the structure fixer specifically: **apply automatically only after a trust period.** For the first N posts, Tier 0 rules
*propose* rather than apply, and Jacob reviews them via Visual Revisions or a staging preview. Once a rule has a clean record, it
moves to auto-apply.

---

## 4. Voice preservation

### Evidence

- **LLMs over-correct by default.**
  - ChatGPT is "not particularly inclined to use minimal edits" and its fluency comes partly from over-correction [P13] ⚠ 2023.
  - GPT-3.5/4 "over-edit" in ways that can change meaning [P14] ⚠ 2023.
  - Plain "correct the errors" prompts produce "systematic overcorrection" (Sept 2026) [P15].
  - Mitigations in the literature: constrained prompting, larger context, batching, edit-level majority voting (+14 F0.5) [P13–P15].
- **Imitating an everyday person's style is still unreliable**, and is worst for "nuanced, informal writing in blogs and forums".
  News and email are easier (EMNLP 2025 Findings, 400+ authors, 40k generations per model). The same paper's metrics are **authorship
  attribution, authorship verification, style matching and AI-text detection**; data and code are open [P16]. A second
  (unverified) paper reports near-human stylometric scores with few-shot / text-completion prompting, but AI text stays more
  predictable [P16b].
- Phase 1: AI suggestions homogenise style [S55]; ownership drops as AI does more [S54].
- **Products:** Remento's cleaned transcript and first-person modes [P1]; AudioPen imitates a supplied writing sample [S27]; Storii keeps raw and
  rewrite side by side [P5].

### Techniques, ordered from least to most rewriting (inference, built on the above)

1. **Transcript-first "light touch" (default).** Keep his sentences in order. Allowed edits: remove fillers and false starts,
   punctuation, paragraphing, spelling of names, headings inserted *between* his sentences. The prompt specifies an *allow-list
   of edit types*, not "improve". This mirrors Remento's cleaned transcript and the minimal-edit literature.
2. **Arrange, don't rewrite.** Let the AI reorder whole sentences into day-by-day sections and attach photos, but output only sentence
   IDs plus headings. PHP then assembles the post from his actual sentences. This **makes word invention structurally impossible** for
   body text; only headings and captions are new words.
3. **Few-shot from his corpus,** for when a real draft is wanted. Pick 3–5 of his own posts on similar topics, plus a short style sheet of
   his habits (first person, swearing kept, his rating phrases like "a solid 8.8/10", how he signs off). The WordPress "Guidelines"
   store [S8] could hold the style sheet, but the shipped core doesn't have it [S6]; a site option is fine.
4. **Hard budgets checked in code:**
   - **Retention:** at least X% of the draft's word tokens must appear in the transcript (multiset overlap).
   - **Novelty:** every number, price, score, proper noun and dish name in the draft must appear in the transcript (or in Shawn's
     earlier posts for spellings). Otherwise the draft is blocked or flagged.
   - **Profanity check:** swear words in the transcript must appear in the draft, and none may be added.
5. **Two-pass review:** a second model call lists every sentence that has no near-match in the transcript. Jacob or Shawn sees
   those highlighted.

### An evaluation we can run ourselves on his existing posts

- **Corpus:** his ~N published posts, which are ground truth for his voice.
- **Test 1, light-touch fidelity:**
  - Take a post and make a messy "spoken" version: remove punctuation and paragraphing, add fillers, scramble a few sentences.
  - Run the pipeline, then measure word-level overlap with the original (token F1 / chrF).
  - Count **new content words** and **dropped swearing**.
  - The target is near-identity. This gives a reproducible number per prompt or model change.
- **Test 2, style similarity:**
  - For drafted posts, compare function-word frequency profiles (Burrows-style delta) between the draft, his corpus, and a
    generic model rewrite.
  - Optionally use the open-source authorship-verification setup from [P16].
- **Test 3, blind human check:** Jacob (and, if willing, Shawn) reads pairs of paragraphs (his original vs the pipeline's output) and
  picks "which is Shawn". If they can tell reliably, the voice is drifting.
- **Real voice test:** record Shawn telling one story he has already blogged. Compare the pipeline's draft with his real post. It's the
  closest thing to a gold standard we can get.

---

## 5. Structure fixer: approval screen and "words unchanged" check

### Approval UX a non-technical person can judge

- **Core Visual Revisions (7.0)** shows each revision rendered as it looks, with additions green, deletions red, changed blocks
  outlined in yellow. A scrollbar mini-map jumps to changes and a **Restore** button replaces Publish while browsing [P20]. Fact (secondary sources agree [P20]).
- **Inference, a three-step plan:**
  1. During the trust period, the fixer saves its proposal as a revision (or a stored proposal); Jacob reviews it in Visual
     Revisions on desktop.
  2. Once trusted, it applies directly. If anyone dislikes a change, Restore undoes it.
  3. For Shawn, never ask him to judge structure diffs; the fixer only changes layout, which is Jacob's area.
  - If Shawn ever needs to see one, an email with **two screenshots** ("before" and "after") is clearer than any diff. That is Phase 3
    detail, and the screenshot needs a renderer, which this host may not have.

### Rule-based vs AI

- **Gallery conversion is lossless by design:** since 5.9 the Gallery block stores each photo as a nested core Image block [P21]. Turning a run
  of ≥3 consecutive Image blocks into a Gallery wrapping the *same* blocks keeps captions, alt text and links. Fully deterministic.
- **"Day 2" → heading:** a deterministic candidate rule, e.g. a paragraph of ≤6 words, no ending punctuation, matching patterns like
  `Day \d+`, meal names, place names, or a paragraph that is entirely bold. That last pattern is exactly what Editoria11y flags [S42]; accessibility
  guidance lists bold-as-heading as a top author error [S58]. Converting paragraph → heading keeps the inner text.
- **Empty headings and bullets, stray leading hyphens:** deterministic.
- **Legacy row/column layouts:** deterministic unwrap (Columns → their inner blocks in order).
- **AI's role:** only to *classify* ambiguous candidates (is "Wow" a heading? no), returning block indexes. The transformation itself stays in PHP.
  Inference: most of §2.11 needs no AI at all.

### A mechanical "words unchanged" check (design, inference built on documented core functions)

1. For before and after, get `post_content`. Strip tags with `wp_strip_all_tags()`, which removes block comments and HTML (it's commonly
   used on `innerHTML` to extract text [P22]). Decode entities, normalise Unicode (NFC; needs PHP `intl`, to check on the host), unify
   quote and dash characters, collapse whitespace.
2. Tokenise into words. **Compare the sequences exactly.** The only allowed differences are an allow-list of deletions: a leading `-`/`•`
   on list items, and empty headings.
3. Separately compare the **multiset of attachment IDs** (image blocks and galleries) so no photo is lost or duplicated.
4. Store `sha256(normalised token stream)` before and after in post meta. Any mismatch aborts the change and logs a flag.
5. Unit-test it against real posts from staging (all ~N posts), expecting zero false passes. This is cheap to run on staging.

---

## 6. Running AI jobs on this host

### Facts

- **AI Client timeout:** default **30 s**, changeable site-wide via `wp_ai_client_default_request_timeout` (float seconds, since 7.0;
  invalid values fall back to 30) [P23]. Vision calls with many photos, or long drafting, can exceed 30 s. Streaming is not natively supported [S4].
- **PHP limits:** `max_execution_time` defaults to 30 s for web requests and **0 (unlimited) from the command line** [P27].
- **LiteSpeed:** stops a running PHP process when the client disconnects. This breaks WP-Cron's fire-and-forget loopback unless `noabort` is
  set for that script. LiteSpeed's own doc advises scoping `noabort` narrowly because it can tie up a CloudLinux LVE [P26].
- **WP-Cron:** traffic-triggered. With page caching, requests may never reach PHP, so jobs and scheduled posts miss [P24]. The fix is a real
  cron plus `DISABLE_WP_CRON` [P24]. CLAUDE.md adds that **server-to-self requests are throttled for minutes here**, which also rules out
  loopback-based runners.
- **Action Scheduler:** processes batches with a ~30 s budget per request and uses async loopback requests to continue [P28]. It has the
  same loopback problem unless driven from WP-CLI.
- **Abilities from PHP:**
  - Executed via `WP_Ability::execute()`, retrieved with `wp_get_ability()` (Trac #64407 shows `wp_get_ability('…')->execute()`) [P34].
  - The permission callback checks **the current user** [P33].
  - The AI plugin's excerpt ability requires `edit_post` on the post and `show_in_rest`, and accepts a post ID as `context` [P36]. The alt-text ability accepts optional `context` [S11].
  - Its docs show REST and JS calls only, no PHP example [P36].
  - Inference: from a CLI job, `wp_set_current_user()` to a dedicated low-privilege "assistant" editor account, then `execute()`, should work. Verify on staging.
- **Message Batches API:**
  - 50% off input and output; up to 100,000 requests or 256 MB per batch; most finish in <1 h; expire if not done in 24 h (unbilled) [P29].
  - **Results available 29 days**; vision supported; prompt caching stacks but hits are best-effort (30–98%) [P29, P30].
  - Not ZDR-eligible ("29-day retention") [P32].
  - The core AI Client documents no batch interface [S2]. Inference: batches mean calling Anthropic's API directly (Anthropic PHP SDK or `wp_remote_post`), outside the AI Client.

### Recommended setup (inference)

- **One cPanel cron, every 5 min:** `wp cron event run --due-now` (no spaces-in-argument issue). Set `DISABLE_WP_CRON` on production only after the cron is
  confirmed running. This runs in the CLI, so there's no 30 s PHP limit and no LiteSpeed abort.
- **On publish:** Tier 0 runs inline; Tier 1 is queued with `wp_schedule_single_event()` and runs at the next cron tick (≤5 min).
  Raise the AI Client timeout only inside the job (filter applied in the job's context, e.g. 120 s).
- **Backfill (1,060 images):** a one-off WP-CLI command that submits a Message Batch. A cron step polls it and writes results, or simply re-run
  the command next day. Keep `custom_id` = attachment ID (results come back in any order).
- **Calling the AI plugin vs our own prompts:** calling `ai/alt-text-generation` / `ai/excerpt-generation` server-side looks feasible.
  The ability takes our post context, and it keeps prompt maintenance upstream. Risks: experimental, recent breaking renames [S8], and every call goes
  through the AI Client (no batch discount). Suggestion: **own prompts via the AI Client for per-post work; direct Batches API for the backfill;
  re-evaluate the plugin at Phase 3.**

---

## 7. Email loop (brief)

- **Receiving mail on cPanel:**
  - (i) **Pipe to a program:** a Forwarder under Advanced Options pipes each message to a script in the home directory (chmod 700; the host can disable this in WHM) [P37]. Instant, no third party; the script runs in CLI.
  - (ii) **IMAP poll** from cron (Postie's model [S37]).
  - (iii) **Inbound webhook service** (e.g. Postmark): JSON with **`StrippedTextReply`** (English, best-effort, needs In-Reply-To/References) and **`MailboxHash`** (the `+token` part of `user+token@domain`) for linking a reply to a flag, plus SPF and spam headers [P38]. This is the least code, but a third party sees Shawn's mail.
- **Tokens and sender check:** use a per-flag `assistant+<token>@domain` address. Accept a reply only if the token is valid and unused, the From
  address is Shawn's, and DMARC/SPF pass (SPF/DKIM alone don't prove From [S38]). Expire tokens after N days.
- **Quoted-reply parsing:** `willdurand/email-reply-parser` (PHP port of GitHub's parser; last release Jan 2022) marks quoted and signature
  fragments. It is known to miss Gmail-wrapped "On … wrote:" lines; a regex workaround is documented [P39].
- **Context for this site:** the admin address forwards to Outlook.com, and outbound goes through WP Mail SMTP's Gmail mailer. Inference:
  replies would go to whatever Reply-To we set, so set Reply-To to the tokenised address on our domain. The Outlook forward doesn't matter for inbound
  if Shawn replies to that address.
- **Simpler alternative (from §1):** each flag in the weekly email is a **link to a one-question page** (signed, expiring token in the URL, no login),
  e.g. "Where is Stan's? [St. Albert] [Edmonton] [Other: ___]". Shawn can still reply in plain words; the page just removes parsing ambiguity.
  Recommend building the link page first and adding reply parsing later if he prefers replying.
- **Confirming back:** after applying an answer, send one short "Done: added 'St. Albert, Alberta' to Stan's review" line, with an undo link. Batch
  confirmations into the next digest unless he asks.
- **Cadence:** weekly at most, skip weeks with nothing to ask (memoir reviews: daily nudges annoy, weekly feels like homework for people in their late 70s [P7, P9]).

## 8. Speech-to-text (brief)

- **Claude API: no audio input.** The Messages API's user content types are text, image, document and tool blocks; no audio block exists [P31].
  The core AI Client lists text-to-speech but not transcription [S2]. A separate STT provider or the browser is needed.
- **Options (prices from secondary 2026 pages; verify):**
  - Deepgram Nova-3: ~$0.0043/min batch, with **keyterm prompting** (~$0.0013/min) for names like "Dysart's", "St. Albert" [P40].
  - AssemblyAI: $0.15–0.21/audio hour, keyterms add-on ~$0.04–0.05/h [P41].
  - OpenAI transcription models and Whisper: also used by ClassifAI and AI Engine [S19, S20].
  - Browser Web Speech API: free, but limited availability, and Chrome sends audio to Google [S32].
- **Accuracy:** ASR is worse on elderly speech [S33]. Keyterm lists built from his existing posts (restaurant names, towns, dishes) should help.
  Inference: test 2–3 providers on one 10-minute recording of Shawn before choosing.
- **Retention and training terms:** Anthropic's API doesn't train on data without permission [P32]. I did not verify each STT vendor's retention or
  "model improvement" defaults (some vendors run opt-out programs). Phase 3 must check before choosing.

## 9. Disclosure: options for Jacob to discuss with Shawn (not a verdict)

**Evidence:**
- 92% of US adults 50+ want to know if information is AI-made [S52].
- Labelling stories "AI-generated" cut trust 7–14 points in news experiments; listing sources offset some of that [P43].
- A Jan 2026 experiment found **one-line disclosures did not reduce trust or subscriptions, detailed ones did**, though two-thirds *preferred* detail and wanted it available on demand [P44].
- Google: say how content was made "when it would be reasonably expected", including AI-assisted content [P42].
- Shawn's line: "I don't use any AI hacks, so everything you get is me" (Dec 2025, ROADMAP §4).

**Options:**
1. **Metadata-only AI (quiet assistant only).** His line stays true for the writing. Optional footer or About-page note: "Photo descriptions and
   summaries on this site are generated automatically; every word of every post is Shawn's."
2. **AI Mode in light-touch mode (his spoken words, tidied and arranged).** Possible wording: "I talk, a computer types it up and tidies it, I check
   every word before it goes out." Update the Dec 2025 line accordingly.
3. **AI Mode drafting in his voice.** Needs the most honest disclosure. One line per post, e.g. "Written from my voice notes with an AI
   assistant; I checked and approved it." Per [P44], keep it to one line, with detail on an About page.
4. **No AI in writing, ever.** AI only behind the scenes, and AI Mode limited to transcription. Fully consistent with his current statement.

## 10. Cost model (estimates, current Anthropic prices)

**Prices per million tokens (input/output), standard vs batch:**

| Model | Standard | Batch |
|---|---|---|
| Opus 5.5 | $4 / $20 | $2 / $10 |
| Sonnet 5.5 | $2 / $10 | $1 / $5 |
| Haiku 5.5 | $0.10 / $0.50 | $0.05 / $0.25 |

Cache reads cost 0.05× input on Opus/Sonnet 5.5 [P30]. Images cost ⌈w/28⌉×⌈h/28⌉ tokens, capped by tier; a 1000×1000 image is ~1,300 tokens; **Claude reads no EXIF** [S30].
Opus 5.5 always thinks (effort default `medium`) and thinking is billed as output (skill reference, 2026-10-06).

**Assumptions:** images resized to ~1000 px before sending (~1,300 tokens), plus ~400 tokens of prompt and post context, and ~60 output tokens of alt
text plus a few hundred thinking tokens.

| Job | Haiku 5.5 | Sonnet 5.5 | Opus 5.5 |
|---|---|---|---|
| Alt-text backfill, 1,060 images, batch | ≈ $0.10–0.25 | ≈ $2–3 | ≈ $4–8 |
| One post on publish: ~20 photos of alt text + 3–4 text tasks (~50k input, ~5k output, standard) | ≈ $0.01 | ≈ $0.15 | ≈ $0.30–0.40 |
| AI Mode draft: ~60 min transcript (~13k tokens) + 5k-token style examples (cached) + 30 photos (~40k) → ~60k input, ~10k output incl. thinking | n/a (quality) | ≈ $0.20 | ≈ $0.45; a few revisions ≈ $1–2 per post |
| Speech-to-text, 60 min | ≈ $0.30–0.60 (Deepgram/AssemblyAI, unverified) | | |

**Inference:**
- At 2–4 posts a month, everything stays **under ~$5/month even on Opus**. Model choice should be driven by quality on the voice and extraction tests, not cost.
- Opus 4.7+ tokenizers use ~1–1.35× more tokens than older ones (skill reference); the estimates above include headroom.
- **Model-preference fallback:** the AI Client treats `using_model_preference()` as "a preference, not a requirement" and falls back to any compatible model [S2]. Configure only the Anthropic connector, and log `modelMetadata` from each result to detect silent fallbacks.

## 11. Smaller items

- **Featured-image fallback, prior art:** Auto Post Thumbnail (v5.0.8, 2026-10-22, 40,000+ installs) uses the first image in content, bulk for
  old posts, with fallbacks to a default image. Whether it runs on publish isn't stated [P45]. Inference: ~20 lines of our own code on
  `transition_post_status` avoid another plugin. The plugin's bulk mode is prior art for a one-off backfill.
- **Review extraction with quote grounding:**
  - Citations guarantee valid pointers into the source, and `cited_text` is free of output-token cost, but they **can't be combined with structured outputs (400 error)** [P35]. The AI Client is provider-agnostic, so Citations likely aren't exposed through it (inference).
  - Practical design: JSON output with, for each review, `restaurant`, `score`, `quote` (the exact sentence). PHP verifies that `quote` is a substring of the normalised post text and that `score` appears inside `quote`. If either fails, the score is dropped and flagged. Never invent.
- **Core Notes as a single author's to-do list:** Notes are block-level comments with notifications. 7.0 emails collaborators on new notes [P20]; 7.1 adds inline notes and @mentions [S5]. The AI plugin posts Notes as a "WordPress AI" author [S12], so a bot author works. Inference: workable for *Jacob's* review queue inside a draft, but it adds things to Shawn's screen and only shows in the block editor. For Shawn, email is better.
- **Core Post via Email in 7.1:** still unconfirmed. Trac #22942 (removal) is open; a 2024 ticket (#61144) fixed a save error on its settings, implying it still shipped in 2024 [P46] ⚠. Irrelevant to our design (it only creates posts); Jacob can glance at Settings → Writing on staging.

---

## Open questions for Phase 3

1. Exact WP-CLI cron line and `DISABLE_WP_CRON` rollout on this host; whether PHP `intl` (Normalizer) is installed.
2. Where the code lives: the repo deploys the theme only. An mu-plugin or a separate plugin for the assistant? (Design choice.)
3. Verify on staging that `wp_get_ability('ai/excerpt-generation')->execute()` works from CLI under a dedicated user.
4. Pick the STT provider after a test recording; verify retention and training terms.
5. Words-unchanged check: run against all staging posts to measure false positives before trusting it.
6. Flag-answer pages: token signing, expiry, and what "Other: ___" free-text does (AI-parsed, then confirmed back).

## Sources (all accessed 2026-10-25)

- [P1] https://resources.remento.co/getting-started/turning-spoken-words-into-writing (2026-09-30): Remento modes, editing control, recordings kept; no limits on AI additions stated.
- [P2] https://home.agetechcollaborative.org/blogs/mark-ogilbee/2026/06/10/rementos-approach-to-healthy-aging (2026-06-11): no apps/passwords, QR to audio, CEO on tone.
- [P3] https://thenextweb.com/news/storyworth-voice-tools-family-memory (2026-09-15, contributed): Storyworth voice tools; verbatim vs narrative tension.
- [P4] https://welcome.storyworth.com/blog/storyworths-new-voice-recording-and-preservation-features (2026, undated): Story Calls word-for-word, Magic Interviews, Family Calls.
- [P5] https://www.storii.com/blog/ai-tools-family-stories and https://www.storii.com/blog/ultimate-guide-ai-assisted-memoirs (2025–26, vendor): phone calls, raw + AI versions kept.
- [P6] https://publichealth.gmu.edu/news/2025-10/nih-grant-george-mason-researcher-refines-ai-storytelling-tool-dementia-care (2025-10): LifeBio Memory trial.
- [P7] https://www.trustpilot.com/review/remento.co?stars=1&stars=2&stars=3 (reviews Dec 2025–Sep 2026): Remento complaints.
- [P8] https://www.trustpilot.com/review/remento.co (reviews Sep–Oct 2026): rating, older tellers' praise, readability, re-record wish.
- [P9] https://thingtesting.com/brands/storyworth/reviews and https://www.remento.co/journal/storyworth-review-why-to-buy-and-when-to-hold-off (via search, 2025–26; competitor content): Storyworth complaints, "late 70s".
- [P10] https://www.alisonwearing.com/blog/can-ai-help-you-write-your-memoir (via search, undated): memoir voice and AI.
- [P11] https://thewritersteve.substack.com/p/i-wrote-a-book-with-ai-heres-what (via search, undated): AI retrieving own words.
- [P12] https://br.trustpilot.com/review/getmemowrite.com (Feb 2026, via search): voice erased, content invented.
- [P13] https://arxiv.org/abs/2304.01746 ⚠ 2023: ChatGPT GEC over-correction.
- [P14] https://arxiv.org/abs/2303.14342 ⚠ 2023: GPT-3.5/4 over-editing.
- [P15] https://arxiv.org/abs/2609.10810 (2026-09) and https://arxiv.org/abs/2605.13624 (2026): minimal-edit prompting; edit-level voting.
- [P16] https://arxiv.org/abs/2509.14543 (EMNLP 2025 Findings): LLM personal-style imitation; blogs hardest; metric ensemble.
- [P16b] https://www.themoonlight.io/en/review/how-well-do-llms-imitate-human-writing-style (third-party review, unverified).
- [P17] https://writeintolife.com/2018/07/29/what-older-bloggers-think-and-do-and-feel-a-super-summary-of-survey-results/, https://writeintolife.com/2018/08/16/challenges-and-physical-barriers-for-older-bloggers/, https://writeintolife.com/2018/08/12/older-bloggers-choice-of-devices-and-channels/, https://writeintolife.com/2018/08/02/who-answered-our-older-bloggers-survey/ ⚠ 2018: Older Bloggers Survey.
- [P18] https://www.springerpflege.de/doi/10.1145/2858036.2858379 (CHI 2016, via search abstract) ⚠ and CSCW 2021 summary via search ⚠: older bloggers / creators.
- [P19] https://scholarworks.indianapolis.iu.edu/items/44e8e532-9514-4cea-b7b7-4a001e35de3c (ASSETS 2021) ⚠: older content creators and accessibility guidance.
- [P19b] https://www.goodreads.com/author_blog_posts/21158457-wordpress-block-editor-work-around-part-1-how-to-find-and-use-the-class (2021-04-05) ⚠: long-time blogger on block editor.
- [P20] https://gutenbergtimes.com/wordpress-7-0-source-of-truth/ (2026-05-12, upd. 05-20) and https://wpshout.com/wordpress-revision-history/ (2026, via search): Visual Revisions, Notes email notifications.
- [P21] https://make.wordpress.org/core/2021/08/20/gallery-block-refactor-dev-note/ ⚠ 2021 (still current design): Gallery holds nested Image blocks.
- [P22] https://www.billerickson.net/access-gutenberg-block-data/ and https://developer.wordpress.org/reference/functions/parse_blocks/ (current): parse_blocks, wp_strip_all_tags usage.
- [P23] https://core.trac.wordpress.org/changeset/62255 (2026, 7.0): `wp_ai_client_default_request_timeout`, default 30 s.
- [P24] https://jorijn.com/en/knowledge-base/wordpress/errors/wordpress-missed-schedule-post/ (2026, via search): missed schedule on cached/low-traffic sites; real cron fix.
- [P25] https://kinsta.com/knowledgebase/facebook-debugger/ and https://woorkup.com/facebook-open-graph-debugger-cache-clearing/ (via search): Facebook caches previews; manual re-scrape.
- [P26] https://docs.litespeedtech.com/cp/cpanel/long-run-script/ (current): LiteSpeed aborts on disconnect; scoped noabort.
- [P27] https://www.php.net/manual/en/info.configuration.php (current): max_execution_time 30 web, 0 CLI.
- [P28] Action Scheduler `docs/perf.md` (mirrored at https://cdn.jsdelivr.net/wp/plugins/modula-best-grid-gallery/trunk/includes/libraries/action-scheduler/docs/perf.md): 30 s budget, loopback, batch size.
- [P29] https://platform.claude.com/docs/en/build-with-claude/batch-processing (current): limits, 24 h, 29 days, caching in batches.
- [P30] https://platform.claude.com/docs/en/about-claude/pricing (current): model, cache and batch prices.
- [P31] https://platform.claude.com/docs/en/api/messages/create (current): user content block types, no audio.
- [P32] https://platform.claude.com/docs/en/manage-claude/api-and-data-retention (current): no training without permission; Batch API not ZDR, 29-day retention.
- [P33] https://developer.wordpress.org/apis/abilities-api/ (current): execute(), permission callback is current-user based.
- [P34] https://core.trac.wordpress.org/ticket/64407 (2026): `wp_get_ability()->execute()` usage.
- [P35] https://platform.claude.com/docs/en/build-with-claude/citations (current): guaranteed valid pointers; cited_text free; incompatible with structured outputs.
- [P36] https://github.com/WordPress/ai/blob/develop/docs/experiments/excerpt-generation.md (2026): ability inputs, permissions, REST-only examples.
- [P37] https://docs.cpanel.net/cpanel/email/forwarders/ (cPanel 130+) and https://go.cpanel.net/Forwarders (mod. 2024-04): pipe to a program.
- [P38] https://postmarkapp.com/developer/user-guide/inbound/parse-an-email (current): StrippedTextReply, MailboxHash, SPF header.
- [P39] https://packagist.org/packages/willdurand/email-reply-parser (v2.10.0, 2022-01-30): PHP reply parser and Gmail caveat.
- [P40] https://www.activepieces.com/blog/deepgram-pricing-2026-per-minute-rates-and-add-ons and https://soniox.com/pricing/stt/soniox-vs-deepgram (2026, secondary): Deepgram prices, keyterms.
- [P41] https://www.assemblyai.com/pricing.md (2026): AssemblyAI prices, keyterms.
- [P42] https://developers.google.com/search/docs/fundamentals/creating-helpful-content (upd. 2026-10-05): Who/How/Why, AI disclosure when expected.
- [P43] https://ideas.darden.virginia.edu/AI-disclosure-dilemma and https://niemanlab.org/2023/12/most-readers-want-publishers-to-label-ai-generated-articles-but-trust-outlets-less-when-they-do (2023–25) ⚠ partly: AI labels lower trust; source lists offset.
- [P44] https://arxiv.org/abs/2601.09620 (2026-01-15): one-line vs detailed disclosure and trust.
- [P45] https://wordpress.org/plugins/auto-post-thumbnail/ (v5.0.8, 2026-10-22): featured image from first image, bulk.
- [P46] https://core.trac.wordpress.org/ticket/22942 and https://lists.wordpress.org/pipermail/wp-trac/2024-June/522523.html (#61144, 2024) ⚠: Post via email status.
