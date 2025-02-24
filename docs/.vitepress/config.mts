import {defineConfig} from "vitepress";

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "Laravel Revisor",
    description: "Revisor is a robust versioning and publishing system for Laravel Eloquent Models.",
    head: [
        ['link', {rel: 'icon', type: 'image/svg+xml', href: 'https://indracollective.dev/favicon/favicon.svg'}],
        ['link', {
            rel: 'icon',
            type: 'image/png',
            href: 'https://indracollective.dev/favicon/favicon-48x48.png',
            sizes: '48x48'
        }],
        ['link', {rel: 'shortcut icon', href: 'https://indracollective.dev/favicon/favicon.ico'}],
        ['link', {
            rel: 'apple-touch-icon',
            sizes: '180x180',
            href: 'https://indracollective.dev/favicon/apple-touch-icon.png'
        }],
        ['meta', {name: 'apple-mobile-web-app-title', content: 'Indra'}],
        ['link', {rel: 'manifest', href: 'https://indracollective.dev/favicon/site.webmanifest'}],
        [
            'script',
            {type: 'text/javascript'},
            `(function(c, l, a, r, i, t, y) { c[a] = c[a] || function() { (c[a].q = c[a].q || []).push(arguments)}; t = l.createElement(r); t.async = 1; t.src = "https://www.clarity.ms/tag/" + i; y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y); })(window, document, "clarity", "script", "okb6xj5brc");`
        ]
    ],
    markdown: {
        lazyLoading: true
    },
    cleanUrls: true,
    themeConfig: {
        siteTitle: 'Laravel Revisor',
        sidebar: [
            {
                items: [
                    {
                        text: "Getting Started",
                        items: [
                            {text: "Introduction", link: "/introduction"},
                            {text: "Installation", link: "/installation"},
                            {text: "Configuration", link: "/configuration"},
                        ],
                    },
                    {
                        text: "Usage",
                        items: [
                            {text: "Models & Migrations", link: "/preparing-your-models"},
                            {text: "Managing Context", link: "/managing-context"},
                            {text: "Publishing", link: "/publishing"},
                            {text: "Versioning", link: "/versioning"},
                            {text: "Model Events", link: "/model-events"}
                        ],
                    },
                    {
                        text: "Integrations",
                        items: [
                            {text: "FilamentPHP", link: "/filament-php"},
                        ],
                    },
                    {text: "IndraCollective", link: "https://indracollective.dev"},
                ],
            },
        ],

        socialLinks: [
            {icon: "github", link: "https://github.com/indracollective/laravel-revisor"},
            {icon: "twitter", link: "https://x.com/indracollective"},
            {
                icon:
                    {svg: '<svg fill="none" viewBox="0 0 64 57" width="28" style="width: 28px; height: 24.9375px;"><path fill="#ffffff" d="M13.873 3.805C21.21 9.332 29.103 20.537 32 26.55v15.882c0-.338-.13.044-.41.867-1.512 4.456-7.418 21.847-20.923 7.944-7.111-7.32-3.819-14.64 9.125-16.85-7.405 1.264-15.73-.825-18.014-9.015C1.12 23.022 0 8.51 0 6.55 0-3.268 8.579-.182 13.873 3.805ZM50.127 3.805C42.79 9.332 34.897 20.537 32 26.55v15.882c0-.338.13.044.41.867 1.512 4.456 7.418 21.847 20.923 7.944 7.111-7.32 3.819-14.64-9.125-16.85 7.405 1.264 15.73-.825 18.014-9.015C62.88 23.022 64 8.51 64 6.55c0-9.818-8.578-6.732-13.873-2.745Z"></path></svg>'},
                link: "https://bsky.app/profile/indracollective.bsky.social"
            }
        ],
        footer: {
            message: "Released under the MIT License.",
            copyright: 'Copyright © 2024-present <a href="https://indracollective.dev">IndraCollective</a>',
        },
        search: {
            provider: 'local'
        },
    },
});
