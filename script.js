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

    // Initialize custom cursor
    const customCursor = new CustomCursor();
    customCursor.init();

    // JavaScript to add/remove class when hovering over links
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('mouseenter', function() {
            document.querySelector('.ring').classList.add('clickable');
        });
        link.addEventListener('mouseleave', function() {
            document.querySelector('.ring').classList.remove('clickable');
        });
    });

    // JavaScript to add/remove class when hovering over <i> elements
    document.querySelectorAll('i').forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            document.querySelector('.ring').classList.add('clickable');
        });
        icon.addEventListener('mouseleave', function() {
            document.querySelector('.ring').classList.remove('clickable');
        });
    });

    // JavaScript to add/remove class when hovering over buttons
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('mouseenter', function() {
            // Add a class to the button or any other element you want to target
            document.querySelector('.ring').classList.add('clickable');
        });
        button.addEventListener('mouseleave', function() {
            // Remove the class when the mouse leaves
            document.querySelector('.ring').classList.remove('clickable');
        });
    });

    // Slider Dragging
    let isDragging = false;
    let startPosition = 0;
    let currentTranslate = 0;

    const slider = document.querySelector('.slider');

    // Slider Dragging for Mouse Devices
    slider.addEventListener('mousedown', (e) => {
        isDragging = true;
        startPosition = e.pageX - slider.offsetLeft;
        currentTranslate = getTranslateX();
        slider.style.transition = 'none'; // Deaktiviere die Transition beim Ziehen
    });

    slider.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const currentPosition = e.pageX - slider.offsetLeft;
        const move = currentPosition - startPosition;
        slider.style.transform = `translateX(${currentTranslate + move}px)`;
    });

    slider.addEventListener('mouseup', () => {
        isDragging = false;
        slider.style.transition = ''; // Setze die Transition wieder ein
    });

    slider.addEventListener('mouseleave', () => {
        isDragging = false;
        slider.style.transition = ''; // Setze die Transition wieder ein
    });

    // Slider Dragging for Touch Devices
    slider.addEventListener('touchstart', (e) => {
        const touch = e.touches[0];
        isDragging = true;
        startPosition = touch.pageX - slider.offsetLeft;
        currentTranslate = getTranslateX();
        slider.style.transition = 'none';
    });

    slider.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        const touch = e.touches[0];
        const currentPosition = touch.pageX - slider.offsetLeft;
        const move = currentPosition - startPosition;
        slider.style.transform = `translateX(${currentTranslate + move}px)`;
    });

    slider.addEventListener('touchend', () => {
        isDragging = false;
        slider.style.transition = '';
    });

    slider.addEventListener('touchcancel', () => {
        isDragging = false;
        slider.style.transition = '';
    });

    function getTranslateX() {
        const style = window.getComputedStyle(slider);
        const matrix = new DOMMatrixReadOnly(style.transform);
        return matrix.m41;
    }

});

//! Cursor functionality
class CustomCursor {
    constructor() {
        this.ringCursor = document.querySelector('.ring');
        this.starCursor = document.querySelector('.star');
        this.mouseX = 0;
        this.mouseY = 0;
    }

    init() {
        // Check if cursor position is stored in localStorage
        if (localStorage.getItem('cursorX') !== null && localStorage.getItem('cursorY') !== null) {
            this.mouseX = parseInt(localStorage.getItem('cursorX'));
            this.mouseY = parseInt(localStorage.getItem('cursorY'));
            this.ringCursor.style.left = this.mouseX + 'px';
            this.ringCursor.style.top = this.mouseY + 'px';
            this.starCursor.style.left = this.mouseX + 'px';
            this.starCursor.style.top = this.mouseY + 'px';
        }

        // Add event listeners
        document.addEventListener('mousemove', this.handleMouseMove.bind(this));
        document.addEventListener('mousedown', this.handleMouseDown.bind(this));
        document.addEventListener('mouseup', this.handleMouseUp.bind(this));
        window.addEventListener('scroll', this.handleScroll.bind(this));

        // Touch events
        document.addEventListener('touchstart', this.handleTouchStart.bind(this));
        document.addEventListener('touchmove', this.handleTouchMove.bind(this));
        document.addEventListener('touchend', this.handleTouchEnd.bind(this));
    }

    handleMouseMove(e) {
        var scrollX = window.scrollX || window.pageXOffset;
        var scrollY = window.scrollY || window.pageYOffset;

        // Calculate the new position based on the previous position and mouse movement
        this.mouseX += (e.pageX - this.mouseX - scrollX) * 0.40;
        this.mouseY += (e.pageY - this.mouseY - scrollY) * 0.40;

        this.ringCursor.style.left = this.mouseX + 'px';
        this.ringCursor.style.top = this.mouseY + 'px';
        this.starCursor.style.left = this.mouseX + 'px';
        this.starCursor.style.top = this.mouseY + 'px';

        // Add a class to indicate mouse movement
        this.ringCursor.classList.add('moving');
        this.starCursor.classList.add('moving');

        // Store cursor position in localStorage
        localStorage.setItem('cursorX', this.mouseX);
        localStorage.setItem('cursorY', this.mouseY);
    }

    handleMouseDown() {
        this.starCursor.classList.add('active');
        this.ringCursor.classList.add('active');
    }

    handleMouseUp() {
        this.starCursor.classList.remove('active');
        this.ringCursor.classList.remove('active');
    }

    handleScroll() {
        this.ringCursor.classList.add('scroll');
        this.starCursor.classList.add('scroll');
        setTimeout(() => {
            this.ringCursor.classList.remove('scroll');
            this.starCursor.classList.remove('scroll');
        }, 500); // Change the duration as needed
    }

    // Touch event handlers
    handleTouchStart(e) {
        const touch = e.touches[0];
        this.mouseX = touch.pageX;
        this.mouseY = touch.pageY;
    }

    handleTouchMove(e) {
        const touch = e.touches[0];
        this.mouseX = touch.pageX;
        this.mouseY = touch.pageY;
        this.ringCursor.style.left = this.mouseX + 'px';
        this.ringCursor.style.top = this.mouseY + 'px';
        this.starCursor.style.left = this.mouseX + 'px';
        this.starCursor.style.top = this.mouseY + 'px';
    }

    handleTouchEnd() {
        // No specific action needed on touch end
    }

    //! What is this for?
    changeCursor(cursorType) {
        document.body.style.cursor = cursorType;
    }
}
