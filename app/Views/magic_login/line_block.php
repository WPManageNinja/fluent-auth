<?php foreach ($lines as $line): ?>
    <p><?php echo wp_kses_post($line); ?></p>
<?php endforeach; ?>

