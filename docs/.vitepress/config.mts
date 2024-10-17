import {defineConfig} from "vitepress";

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "Laravel Revisor",
    description: "Revisor is a robust versioning and publishing system for Laravel Eloquent Models.",
    cleanUrls: true,
    themeConfig: {
        // logo: {
        //     light: '/assets/ic_logo_landscape.svg',
        //     dark: '/assets/ic_logo_landscape_dark.svg',
        // },
        siteTitle: 'Laravel Revisor',
        // https://vitepress.dev/reference/default-theme-config

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
    },
});
