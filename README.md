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

### Maintenance backlog
Found while reviewing the site in October 2026. Test each on staging first.

- [ ] Update outdated plugins (ACF 6.3 → 6.8, WP Mail SMTP, Custom Post Type UI, WPForms Lite,
      LiteSpeed Cache, Font Awesome, Display a Meta Field as Block, Simple Local Avatars).
      Take a DB backup first (see DEPLOYMENT.md).
- [ ] Turn off `WP_DEBUG` on production (it's on, and `public_html/error_log` is ~19 MB; it is not publicly readable).
- [ ] Replace hard-coded image attachment IDs: the logo (`385` in `header.php`, could use WordPress's custom logo setting) and the blog card icon (`384` in `front-page.php`).
- [ ] Remove the Font Awesome 4.7 CDN link in `header.php`. The icons use FA 6 classes from the Font Awesome plugin.
- [ ] Query categories by slug (`'category_name' => 'blog'`) instead of looping over every category in `front-page.php` and `page-about.php`.
- [ ] Remove the redundant nested loop in `render_rating_shortcode()` (`functions.php`).
- [ ] Escape output in templates (`esc_attr( get_the_title() )` in the wrapper `id`s, `esc_url()` on image URLs).
- [ ] Add a theme header (`Theme Name:` etc.) to the top of `style.css` so WP admin shows proper theme details.

## Technologies Used
- **WordPress** 7.1 on PHP 8.2, LiteSpeed web server.
- **Theme:** this repo: classic PHP templates, Bootstrap 5.3 (CDN), custom CSS per page in `assets/css/`.
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
*.php, style.css        theme templates and main stylesheet
assets/css, assets/js   per-page styles and the mobile menu script
patterns/               block patterns used as templates for new recipes and reviews
scripts/                deploy, rollback, staging refresh (not shipped to the server)
.github/workflows/      GitHub Actions deploy workflow
DEPLOYMENT.md           how deployment and staging work
```

## Getting started on a new machine
1. Clone the repository.
2. Copy `.env.example` to `.env` and fill in the SSH details from WHC (hosting → SFTP/SSH).
3. Set up SSH key access (`~/.ssh/tastybites_deploy`). See *Credentials and access* in DEPLOYMENT.md.
4. Work on a branch and deploy to staging to try changes. There is no local WordPress setup;
   staging is the test environment.

---

Happy cooking with **Gengies Tasty Bites**! 🍽️
