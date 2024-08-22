<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width">
  <link rel="profile" href="http://gmpg.org/xfn/11">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <?php wp_head(); ?>
</head>
 
<body <?php body_class(); ?>>
  <div class="brand-section">
    <div class="logo-slogan-container">
      <?php 
      // Retrieve the site icon URL
      $site_icon_url = get_site_icon_url();
      if($site_icon_url){
      echo '<div class="logo-container"><a href="'. get_home_url() .'"> <img src="' . esc_url($site_icon_url) . '" class="brand-logo" alt="Site Icon"> </a> </div>';
      }
      ?>
      <a href="<?php echo get_home_url(); ?>">
        <h1 class="brand-title"><?=get_bloginfo( "name" )?></h1>
      </a>
    </div>

    <div class="primary_menu">
      <div class="nav-container">
        <nav>
          <?php 
          wp_nav_menu_no_ul()
          ?>
        </nav>
      </div>
    </div>
  </div>
