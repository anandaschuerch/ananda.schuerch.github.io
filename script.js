$(document).ready(function() {
    
    // Function to toggle navigation
    $(".handle").click(function() {
        $("nav").toggleClass("showing");
        $(".handle i").toggleClass("bx-menu bx-x");
        $(".header").toggleClass("nav-open");
    });
    
    // Function to scroll to top
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Attach click event handler to the scroll up button
    $('.scrollupbutton').click(function() {
        scrollToTop();
    });

    // Check if the device supports touch events
    if ('ontouchstart' in window || navigator.maxTouchPoints) {
        // Touch-enabled device detected, add class to body
        document.body.classList.add('touch-device');
        // Log message
        console.log('Touch is supported');
    } else {
        // No touch support detected, add class to body
        document.body.classList.add('non-touch-device');
        // Log message
        console.log('Touch is not supported');
    }

    // Initialize cursor functionality when DOM is loaded
    var ringCursor = document.querySelector('.ring');
    var starCursor = document.querySelector('.star');
    var mouseX = 0;
    var mouseY = 0;

    // Check if cursor position is stored in localStorage
    if (localStorage.getItem('cursorX') !== null && localStorage.getItem('cursorY') !== null) {
        mouseX = parseInt(localStorage.getItem('cursorX'));
        mouseY = parseInt(localStorage.getItem('cursorY'));
        ringCursor.style.left = mouseX + 'px';
        ringCursor.style.top = mouseY + 'px';
        starCursor.style.left = mouseX + 'px';
        starCursor.style.top = mouseY + 'px';
    }

    document.addEventListener('mousemove', function(e) {
        var scrollX = window.scrollX || window.pageXOffset;
        var scrollY = window.scrollY || window.pageYOffset;

        // Calculate the new position based on the previous position and mouse movement
        mouseX += (e.pageX - mouseX - scrollX) * 0.40;
        mouseY += (e.pageY - mouseY - scrollY) * 0.40;

        ringCursor.style.left = mouseX + 'px';
        ringCursor.style.top = mouseY + 'px';
        starCursor.style.left = mouseX + 'px';
        starCursor.style.top = mouseY + 'px';

        // Store cursor position in localStorage
        localStorage.setItem('cursorX', mouseX);
        localStorage.setItem('cursorY', mouseY);
    });

    document.addEventListener('mousedown', function() {
        starCursor.classList.add('active');
        ringCursor.classList.add('active');
    });

    document.addEventListener('mouseup', function() {
        starCursor.classList.remove('active');
        ringCursor.classList.remove('active');
    });

    window.addEventListener('scroll', function() {
        ringCursor.classList.add('scroll');
        starCursor.classList.add('scroll');
        setTimeout(function() {
            ringCursor.classList.remove('scroll');
            starCursor.classList.remove('scroll');
        }, 1000); // Change the duration as needed
    });
});
