document.addEventListener('DOMContentLoaded', function() {
            var menu = document.querySelector('.menu');
            var menuVisible = true;

            document.querySelector('nav h2').addEventListener('click', function() {
                menu.style.display = menuVisible ? 'none' : 'block';
                menuVisible = !menuVisible;
            });
        });
