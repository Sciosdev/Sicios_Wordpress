function cake_shop_bakery_openNav() {
  jQuery(".sidenav").addClass('show');
}
function cake_shop_bakery_closeNav() {
  jQuery(".sidenav").removeClass('show');
}

( function( window, document ) {
  function cake_shop_bakery_keepFocusInMenu() {
    document.addEventListener( 'keydown', function( e ) {
      const cake_shop_bakery_nav = document.querySelector( '.sidenav' );

      if ( ! cake_shop_bakery_nav || ! cake_shop_bakery_nav.classList.contains( 'show' ) ) {
        return;
      }

      const elements = [...cake_shop_bakery_nav.querySelectorAll( 'input, a, button' )],
        cake_shop_bakery_lastEl = elements[ elements.length - 1 ],
        cake_shop_bakery_firstEl = elements[0],
        cake_shop_bakery_activeEl = document.activeElement,
        tabKey = e.keyCode === 9,
        shiftKey = e.shiftKey;

      if ( ! shiftKey && tabKey && cake_shop_bakery_lastEl === cake_shop_bakery_activeEl ) {
        e.preventDefault();
        cake_shop_bakery_firstEl.focus();
      }

      if ( shiftKey && tabKey && cake_shop_bakery_firstEl === cake_shop_bakery_activeEl ) {
        e.preventDefault();
        cake_shop_bakery_lastEl.focus();
      }
    } );
  }
  cake_shop_bakery_keepFocusInMenu();
} )( window, document );

var btn = jQuery('#button');

jQuery(window).scroll(function() {
  if (jQuery(window).scrollTop() > 300) {
    btn.addClass('show');
  } else {
    btn.removeClass('show');
  }
});

btn.on('click', function(e) {
  e.preventDefault();
  jQuery('html, body').animate({scrollTop:0}, '300');
});

jQuery(document).ready(function() {
  var slider_loop = jQuery('#skip-content').attr('slider-loop');
  var owl = jQuery('#top-slider .owl-carousel');
    owl.owlCarousel({
      margin: 0,
      nav: true,
      autoplay:true,
      autoplayTimeout:3000,
      autoplayHoverPause:true,
      autoHeight: true,
      loop: slider_loop == 0 ? false : slider_loop,
      dots:false,
      navText : ['<i class="fa fa-lg fa-chevron-left" aria-hidden="true"></i>','<i class="fa fa-lg fa-chevron-right" aria-hidden="true"></i>'],
      responsive: {
        0: {
          items: 1
        },
        600: {
          items: 1
        },
        1024: {
          items: 1
      }
    }
  })
})

window.addEventListener('load', (event) => {
  jQuery(".loading").delay(2000).fadeOut("slow");
  jQuery(".loading2").delay(2000).fadeOut("slow");
});

jQuery(window).scroll(function() {
  var data_sticky = jQuery('.main-header').attr('data-sticky');

  if (data_sticky == "true") {
    if (jQuery(this).scrollTop() > 1){
      jQuery('.main-header').addClass("stick_header");
    } else {
      jQuery('.main-header').removeClass("stick_header");
    }
  }
});
