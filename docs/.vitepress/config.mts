import {defineConfig} from "vitepress";

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "Laravel Revisor",
    description: "Revisor is a robust versioning and publishing system for Laravel Eloquent Models.",
    head: [
        ['link', { rel: 'icon', type: 'image/svg+xml', href: 'https://indracollective.dev/favicon/favicon.svg' }],
        ['link', { rel: 'icon', type: 'image/png', href: 'https://indracollective.dev/favicon/favicon-48x48.png', sizes: '48x48' }],
        ['link', { rel: 'shortcut icon', href: 'https://indracollective.dev/favicon/favicon.ico' }],
        ['link', { rel: 'apple-touch-icon', sizes: '180x180', href: 'https://indracollective.dev/favicon/apple-touch-icon.png' }],
        ['meta', { name: 'apple-mobile-web-app-title', content: 'Indra' }],
        ['link', { rel: 'manifest', href: 'https://indracollective.dev/favicon/site.webmanifest' }],
    ],

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
                            {text: "Preparing your Models", link: "/preparing-your-models"},
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
