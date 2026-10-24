# Blog best practices: research notes (October 2026)

Summary of a research pass on food & travel blog UX, written for this site. Gathered by a separate
research agent (so outside web content never entered the main working session). Sources are listed
per topic; NN/g had no specific guidance on related posts, so that section is judgement.

Status markers show what the Road Journal theme already does (✅), what's planned in ROADMAP.md (→), and open ideas.

## Breadcrumbs
- ✅ On single posts and archives, not the homepage. One canonical trail (post type + primary category / destination).
- ✅ `<nav aria-label="Breadcrumb"><ol>`, last item plain text with `aria-current="page"`, separator drawn in CSS.
- ✅ Phones: one line, scrolls sideways; links keep a ~44px tap height.
- ✅ BreadcrumbList JSON-LD printed by the same function as the visible trail. Since Jan 2025 Google mobile results
  no longer show breadcrumbs (domain only), so the SEO value is mainly desktop, but it's cheap.
- Sources: nngroup.com/articles/breadcrumbs · developers.google.com/search/docs/appearance/structured-data/breadcrumb ·
  developers.google.com/search/blog/2025/01/simplifying-breadcrumbs

## Search with filters
- ✅ One filter row: content type, as link chips with counts (`?s=…&type=reviews`): shareable, Back works, no JS.
- ✅ Hide zero-count chips; "Search everything instead" when a filter causes zero results.
- ✅ No-results page is never a dead end: example searches, sections, latest posts.
- Later: a destination/place filter row once there are 5+ destinations with content; "Newest" sort; spelling suggestions.
- Native WordPress search is fine to a few hundred posts; consider Relevanssi beyond that.
- Sources: nngroup.com/articles/applying-filters · baymard.com/research-articles/no-results-page

## Related posts
- ✅ After the post, before comments; 3 items; same destination first, then category/type, then recent.
- ✅ Honest labels ("More from Vancouver" only when all cards are from there; otherwise "More reviews").
- ✅ Phones: stacked horizontal cards; review cards keep their score badge.
- Later: cross-type links (a review from the city of a travel post) once reviews have destinations; cache in a transient if traffic grows.

## Restaurant reviews
- ✅ Score as a number plus a one-word verdict, linking to a personal "How I rate" guide (Bite Rating index).
- → Info box at the top: rating, city, cuisine, price, visited, address/map, website. Every field optional, hidden when empty.
- → Review → Restaurant JSON-LD with `bestRating: 10` (otherwise Google assumes 5); only mark up what's visible.
- → Visit date and an optional "since my visit" note.
- Sources: theinfatuation.com/ratings-explainer · developers.google.com/search/docs/appearance/structured-data/review-snippet ·
  developers.google.com/search/docs/appearance/structured-data/sd-policies · schema.org/Restaurant

## Recipes
- → Card: description, prep/cook/total, serves, ingredients (with optional groups), numbered steps, notes.
- → "Jump to recipe" + "Print" (print stylesheet showing only the card); large tap targets, ≥18px text in the card.
- → Recipe JSON-LD (ISO 8601 durations, `HowToStep` instructions); never two sets of Recipe schema.
- Build in the theme or use WP Recipe Maker (free); pick one.
- Source: developers.google.com/search/docs/appearance/structured-data/recipe

## Long photo-heavy posts
- ✅ Single photos capped at `min(85vh, 860px)`; accurate `sizes` so browsers fetch reading-column-sized files; lightbox on.
- ✅ Hero not lazy-loaded, `fetchpriority="high"`; everything else lazy.
- → Modern Image Formats plugin (WordPress core team) for automatic WebP/AVIF on upload; regenerate old images with WP-CLI.
- → Table of contents generated from H2s when a post has 4+; in the body, not sticky; anchors + back-to-top. Needs
  "Day 1" lines to be real headings (AI structure fixer, ROADMAP §2.11).
- Watch: lightbox styling quirks in classic themes; never more than ~3 large single images in a row (→ galleries, §2.11).
- Sources: developer.wordpress.org/apis/responsive-images · web.dev/articles/browser-level-image-lazy-loading ·
  wordpress.org/plugins/webp-uploads · nngroup.com/articles/table-of-contents ·
  developer.wordpress.org/themes/global-settings-and-styles/settings/lightbox

## Footer and social
- ✅ Icon links with visually hidden text labels (better supported than `aria-label`), SVG `aria-hidden`, 44px targets, RSS link.
- → Fuller footer: a short about line, browse links, contact, privacy policy, an "I pay for my meals" ethics note, © year.
- Note: icons plus visible words are clearer for older readers. We chose icons only (Jacob's preference); revisit if needed.
- Sources: kittygiraudel.com/2020/12/10/accessible-icon-links · w3.org/WAI/WCAG22/Understanding/target-size-minimum

## Mobile testing
- Test at 320 (quick break check), 360, 375, 390/393, 414/412 and 768. Also 200% text size (older audience),
  landscape, and real iPhone Safari.
- How we test here: Chrome won't shrink its viewport below ~660px, so load pages into 360/390/414px iframes on a
  staging page (see DEPLOYMENT.md).
- Common failures: sideways scroll from wide content, tall portrait photos, tiny links, inputs under 16px (iOS zoom).
- Source: gs.statcounter.com/screen-resolution-stats/mobile/worldwide

## Comments (low moderation)
- → Antispam Bee (honeypot + local checks, no third party); delete spam after 7–30 days; trust approved commenters.
- → Hold comments with 1+ links; consider closing comments on posts older than 6–12 months; pingbacks off.
- → Form: name, email ("never shown"), comment; drop the Website field (spam magnet); 16px inputs; privacy note.
- → List: 2 levels of replies max, a "Chef" badge on Shawn's replies (`bypostauthor`), comment count linking to `#comments`.
- Source: wordpress.org/plugins/antispam-bee
