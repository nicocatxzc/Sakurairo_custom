<?php
function array_html_props(array $props)
{
    $props_string = '';
    foreach ($props as $key => $value) {
        $props_string .= ' ' . $key . '="' . $value . '"';
    }
    return $props_string;
}

// html 标签处理器
function html_tag_parser($content)
{
    //html tag parser for rss
    if (is_feed()) {
        //Fancybox
        $url_regex = '((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
        if (preg_match_all('/\!\{.*?\)\[.*?\]/i', $content, $matches)) {
            foreach ($matches as $result) {
                $content = str_replace(
                    $result,
                    preg_replace('/!\{([^\{\}]+)*\}\(' . $url_regex . '\)\[' . $url_regex . '\]/i', '<a href="$2"><img src="$7" alt="$1" title="$1"></a>', $result),
                    $content
                );
            }
        }
        $content = preg_replace('/!\{([^\{\}]+)*\}\(' . $url_regex . '\)/i', '<a href="$2"><img src="$2" alt="$1" title="$1"></a>', $content);
    }
    return $content;
}
add_filter('the_content', 'html_tag_parser'); //替换文章关键词