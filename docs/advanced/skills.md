---
title: "AI Assistant Skills"
description: "Install the oilab-laravel-sign skill in a host application"
section: advanced
order: 4
---

# AI Assistant Skills

The package ships an AI skill describing its contract, actions, events and rules.

```bash
php artisan oi:skills oilab-laravel-sign --project
```

It is synced from `resources/stubs/ai-skill.md` into `.claude/skills/` and `.junie/skills/` on every
`composer install`.
