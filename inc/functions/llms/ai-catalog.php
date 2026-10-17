<?php

/**
 * AI Catalog
 * https://ai-catalog.io/
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', 'iro_ai_catalog_dispatch', 1);
add_action('wp_head', 'iro_ai_catalog_head', 99);

function iro_ai_catalog_dispatch(): void
{
    if (get_query_var(IRO_AI_CATALOG_ROUTE_VAR) !== '1') {
        return;
    }

    header('Content-Type: application/ai-catalog+json; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');

    echo wp_json_encode(iro_ai_catalog_document(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    exit;
}

/**
 * 目录文档：只列真实存在的入口，避免客户端拿到死链
 *
 * @return array<string,mixed>
 */
function iro_ai_catalog_document(): array
{
    $host      = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $site_name = (string) get_bloginfo('name');
    $publisher = [
        'identifier'   => $host,
        'displayName'  => $site_name,
        'identityType' => 'dns',
    ];

    return [
        'specVersion' => '1.0',
        'host'        => [
            'displayName'      => $site_name,
            'identifier'       => $host,
            'documentationUrl' => home_url('/llms.txt'),
        ],
        'entries'     => [
            [
                'identifier'  => 'urn:air:' . $host . ':doc:llms-txt',
                'type'        => 'text/markdown',
                'url'         => home_url('/llms.txt'),
                'description' => 'Site index for AI agents: published posts, pages, categories and tags with markdown links. Add .md or ?md to any link to fetch only its content.',
                'tags'        => ['llms-txt', 'markdown', 'index'],
                'publisher'   => $publisher,
            ],
            [
                'identifier'  => 'urn:air:' . $host . ':webmcp:tools',
                'displayName' => $site_name . ' WebMCP Tools',
                'type'        => 'application/webmcp-tools+json',
                'data'        => [
                    'version' => '1.0',
                    'tools'   => iro_ai_catalog_tools(),
                ],
                'description' => 'In-page WebMCP tools for browser agents: search_site, read_page and get_llms_txt. They only read the public llms.txt and markdown endpoints.',
                'tags'        => ['webmcp', 'browser-agent', 'tools', 'llms-txt'],
                'publisher'   => $publisher,
            ],
        ],
    ];
}

/**
 * WebMCP 工具定义
 *
 * 浏览器端 frontend/app/plugins/webMCP.js 按 name 绑定实现，页面里通过 #iro_webmcp_tools
 * 读到同一份数据；目录的 data 也取自这里，避免描述与实现两处漂移。
 *
 * @return list<array<string,mixed>>
 */
function iro_ai_catalog_tools(): array
{
    return [
        [
            'name'        => 'search_site',
            'description' => 'Search this site\'s published posts by keyword. Returns matching titles with markdown links and excerpts.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'keyword' => [
                        'type'        => 'string',
                        'description' => 'Keyword or phrase to search for.',
                    ],
                    'limit'   => [
                        'type'        => 'integer',
                        'minimum'     => 1,
                        'maximum'     => 20,
                        'default'     => 10,
                        'description' => 'Maximum number of results to return.',
                    ],
                ],
                'required'   => ['keyword'],
            ],
        ],
        [
            'name'        => 'read_page',
            'description' => 'Read a page of this site as plain markdown. Pass a URL from llms.txt or search_site; only URLs on this site are accepted.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'url' => [
                        'type'        => 'string',
                        'description' => 'Absolute or relative URL of a page on this site.',
                    ],
                ],
                'required'   => ['url'],
            ],
        ],
        [
            'name'        => 'get_llms_txt',
            'description' => 'Read this site\'s llms.txt: a compact index of published posts, pages, categories and tags written for AI agents.',
            'inputSchema' => [
                'type' => 'object',
            ],
        ],
    ];
}

/**
 * 发现链接与页面内工具清单
 */
function iro_ai_catalog_head(): void
{
    echo '<link rel="ai-catalog" type="application/ai-catalog+json" href="' . esc_url(home_url('/.well-known/ai-catalog.json')) . '">' . "\n";

    echo '<script type="application/json" id="iro_webmcp_tools">' . wp_json_encode([
        'home'  => home_url('/'),
        'llms'  => home_url('/llms.txt'),
        'tools' => iro_ai_catalog_tools(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>' . "\n";
}
