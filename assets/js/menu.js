document.querySelector('.menu-toggle').addEventListener('click', function() {
    const menuItems = document.querySelector('.menuItems');
    
    // Toggle show class
    menuItems.classList.toggle('show');
    
    // After the first toggle, trigger the active class to start the transition
    setTimeout(function() {
      menuItems.classList.toggle('active');
    }, 10); // Small delay to allow 'show' to apply first
  });