<?php
function iro_post_pagination()
{
?>
    <div class="pagination">
        <?php
        the_posts_pagination([
            'mid_size'  => 2,
            'prev_text' => '<',
            'next_text' => '>',
        ]); ?>
        </div>
        <?php
    }

    function iro_comment_pagination()
    {
        ?>
            <div class="pagination">
                <?php
                the_comments_pagination([
                    'mid_size'  => 2,
                    'prev_text' => '<',
                    'next_text' => '>',
                ]);
                ?>
                </div>
                <?php
            }
