<?php

/**
 * @param array{class?: string, style?: string} $props
 */
function iro_content_container_start($props = [])
{
?>
    <div class="content flex-center <?= $props['class'] ?? '' ?>" style="<?= $props['style'] ?? '' ?>">
        <div class="container">
        <?php
    }

    function iro_content_container_end()
    {
        ?>
        </div>
    </div>
<?php
    }
