---
# https://vitepress.dev/reference/default-theme-home-page
layout: home

hero:
  name: "Laravel Revisor"
  text: "By IndraCollective"
  tagline: "Robust Draft, Publishing and Versioning\nfor Laravel Eloquent Models."
  actions:
    - theme: brand
      text: Docs
      link: /introduction
    - theme: alt
      text: Github
      link: https://github.com/indracollective/laravel-revisor

features:
  - title: Seamless Database Design
    details: Separate, complete database tables for Draft, Published and Version history records per Eloquent Model, reducing exposure to the added complexity of context-dependent records
  - title: Intuitive API
    details: Super simple, feature complete API for Publishing and Versioning records, including Version rollbacks, pruning and more.
  - title:  Flexible Context Management
    details: Easily switch between Draft, Published and Version contexts at any level including Global Config, Route Middleware, Query Scopes and context-isolating Closures.
---
