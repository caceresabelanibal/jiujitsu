<?php
view_header('404');
echo '<div class="card center"><h1>404</h1><p class="muted">Página no encontrada / Page not found</p><a class="btn" href="' . APP_URL . '/">' . icon('home', 15) . '</a></div>';
view_footer();
