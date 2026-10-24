# Gengies Tasty Bites

Welcome to **Gengies Tasty Bites**! This WordPress-powered website is dedicated to sharing delicious recipes, insightful reviews, and engaging blog posts. Whether you're a seasoned chef or just looking for your next meal inspiration, Gengies Tasty Bites has something for everyone.

- **Live site:** https://gengiestastybites.ca
- **Staging:** https://staging.gengiestastybites.ca (password protected; logins in your local `.env`)
- **Hosting:** WHC cPanel shared hosting · DNS at GoDaddy

This repository is the custom **TastyBites** theme. WordPress core, plugins, media and the
database live on the server. **How to deploy, roll back and use staging: see [DEPLOYMENT.md](DEPLOYMENT.md).**

## Current Version: V1.1

### V1: core site
- **Core Structure and Design:**
  - Major structural elements of the site have been built.
  - Core styling for all pages is completed.
  - Mobile-friendly design has been implemented to ensure a seamless experience across all devices.

- **Content Management:**
  - Block templates are in place for the content admin, allowing them to easily create consistent and structured recipes and reviews.
  - Custom post types for recipes (`recipes_content`) and reviews (`review_content`) are managed using the Custom Post Type UI plugin.
  - Advanced Custom Fields are used to add custom fields (`recipe_time`, `review_rating`) and enrich content management.

- **Mail Configuration:**
  - SMTP has been configured to ensure reliable email delivery for notifications and contact forms.

### V1.1: deployment pipeline (October 2026)
- Repo brought back in sync with the theme running on production (it had drifted).
- GitHub Actions deploys: push to `staging` → staging site, merge to `main` → production (with approval).
- Every deploy lints the PHP, refuses to overwrite edits made directly on the server, backs up the old
  theme, smoke tests key pages and rolls back automatically on failure.
- Staging: a password-protected full copy of production with outgoing email disabled.
- Pages now have proper `<title>` tags (`add_theme_support( 'title-tag' )`).

## Upcoming Version: V2

### Planned Features
- **Search Functionality:**
  - Implementing a search feature to help users find specific recipes, reviews, or blog posts with ease.
    (The theme has no `search.php` or search form yet.)

- **User Interaction:**
  - Enabling user comments on recipes and reviews to encourage community engagement.
    (`single.php` doesn't call `comments_template()` yet.)

- **Enhanced Styling:**
  - Improving the site's visual appeal and user experience with more refined styling and design elements.

- **User Accounts:**
  - Potentially adding user accounts, allowing visitors to log in, save favorite recipes, and more.

### Roadmap
The redesign ("Road Journal"), the AI assistant ideas, other features and the maintenance
backlog are tracked in **[ROADMAP.md](ROADMAP.md)**.

## Technologies Used
- **WordPress** 7.1 on PHP 8.2, LiteSpeed web server.
- **Theme:** this repo: classic PHP templates + `theme.json` design tokens ("Road Journal" design), one stylesheet (`style.css`), self-hosted Young Serif and Source Sans 3 fonts.
- **Plugins:**
  - **Advanced Custom Fields (ACF):** custom fields on posts.
  - **Custom Post Type UI:** the recipe and review post types.
  - **Display a Meta Field as Block:** shows ACF fields in the block patterns.
  - **WP Mail SMTP:** reliable email delivery.
  - **WPForms Lite:** forms.
  - **Font Awesome:** icons (drumstick ratings, social links).
  - **LiteSpeed Cache:** page caching.
  - **Simple Local Avatars:** author avatars.
  - WHC/Imunify security and SSO plugins come with the hosting.

## Repository layout

```
*.php, style.css        theme templates and the stylesheet
theme.json              design tokens (colours, fonts, sizes) and editor settings
template-parts/         cards shared by the homepage, archives and search
assets/                 fonts, editor stylesheet, phone-menu script
inc/patterns/           block patterns used as templates for new recipes and reviews
scripts/                deploy, rollback, staging refresh, content migrations (not shipped to the server)
.github/workflows/      GitHub Actions deploy workflow
DEPLOYMENT.md           how deployment and staging work
ROADMAP.md              feature backlog, ideas and decisions
```

## Getting started on a new machine
1. Clone the repository.
2. Copy `.env.example` to `.env` and fill in the SSH details from WHC (hosting → SFTP/SSH).
3. Set up SSH key access (`~/.ssh/tastybites_deploy`). See *Credentials and access* in DEPLOYMENT.md.
4. Work on a branch and deploy to staging to try changes. There is no local WordPress setup;
   staging is the test environment.

---

Happy cooking with **Gengies Tasty Bites**! 🍽️
