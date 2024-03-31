document.addEventListener('DOMContentLoaded', function() {
    var outerCursor = document.querySelector('.outer');
    var innerCursor = document.querySelector('.inner');
    var mouseX = 0;
    var mouseY = 0;

    // Check if cursor position is stored in localStorage
    if (localStorage.getItem('cursorX') !== null && localStorage.getItem('cursorY') !== null) {
        mouseX = parseInt(localStorage.getItem('cursorX'));
        mouseY = parseInt(localStorage.getItem('cursorY'));
        outerCursor.style.left = mouseX + 'px';
        outerCursor.style.top = mouseY + 'px';
        innerCursor.style.left = mouseX + 'px';
        innerCursor.style.top = mouseY + 'px';
    }

    document.addEventListener('mousemove', function(e) {
        var scrollX = window.scrollX || window.pageXOffset;
        var scrollY = window.scrollY || window.pageYOffset;

        // Calculate the new position based on the previous position and mouse movement
        mouseX += (e.pageX - mouseX - scrollX) * 0.40;
        mouseY += (e.pageY - mouseY - scrollY) * 0.40;

        outerCursor.style.left = mouseX + 'px';
        outerCursor.style.top = mouseY + 'px';
        innerCursor.style.left = mouseX + 'px';
        innerCursor.style.top = mouseY + 'px';

        // Store cursor position in localStorage
        localStorage.setItem('cursorX', mouseX);
        localStorage.setItem('cursorY', mouseY);
    });

    document.addEventListener('mousedown', function() {
        innerCursor.classList.add('active');
        outerCursor.classList.add('active');
    });

    document.addEventListener('mouseup', function() {
        innerCursor.classList.remove('active');
        outerCursor.classList.remove('active');
    });

    window.addEventListener('scroll', function() {
        outerCursor.classList.add('scroll');
        innerCursor.classList.add('scroll');
        setTimeout(function() {
            outerCursor.classList.remove('scroll');
            innerCursor.classList.remove('scroll');
        }, 1000); // Change the duration as needed
    });
});