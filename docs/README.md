# Introduction

Laravel Revisor aims to provide the maximum power and flexibility possible in versioned record management, while maintaining a very low tolerance for complexity.&#x20;

### Features

✅ Separate, complete database tables for draft, published and version history records of each Model. Drafts and versions of recods are not second class citizens stored in single column json blobs

✅ Migration API for managing draft, published and version history tables with no additional overhead

✅ Easy context management for setting the appropriate reading/writing "mode" at all levels of operation - global config, middleware, mode isolation callbacks and query builder modifiers.

✅ Simple and intuitive API for drafting, publishing and versioning records

✅ High configurability and excellent documentation
