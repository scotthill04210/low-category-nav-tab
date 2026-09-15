# LOW Category Nav Tab

WordPress plugin for a category tab UI: a pill-shaped tab bar and colored content cards.

**Version 1.1.0** · Author: Scott Hill

Repository: [github.com/scotthill04210/low-category-nav-tab](https://github.com/scotthill04210/low-category-nav-tab)

## Features

- Custom post type **Category Tabs** (`low_cat_tab`) for titles, body copy, and order
- Shortcode `[low_category_nav_tab]` to place the tabs on any page
- Per-tab card background and text colors (hex, Settings API defaults for new tabs)
- Auto-contrast text when a tab has no saved text color
- **Desktop (over 800px):** equal-height cards in a horizontal carousel; choosing a tab slides that card to the left with the next card peeking
- **800px and under:** original single-panel tabs; stacked tab list on small phones
- **Mobile:** after a new category is picked, the page scrolls to that panel
- Drag-to-reorder on the admin list (saves `menu_order`)
- Front-end CSS/JS load only on pages that render the shortcode
- Shortcode HTML cached; cache clears when tabs or colors change
- CPT is not public (no URLs, REST, search, or core/Yoast/Rank Math/AIOSEO sitemaps)

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Copy the `low-category-nav-tab` folder into `wp-content/plugins/`.
2. Activate **LOW Category Nav Tab** in **Plugins**.
3. Go to **Category Tabs** in the admin menu and add each category (title + content).
4. Set card colors on the edit screen, or defaults under **Category Tabs → Settings**.
5. Drag rows on **All Tabs** to set left-to-right order.
6. Place this shortcode on a page:

```
[low_category_nav_tab]
```

The page heading stays in the page editor (the plugin does not output an H1).

## Typography

| Element        | Size |
|----------------|------|
| Tabs           | 16px |
| Panel title    | 24px |
| Panel body     | 14px |

## Colors

Settings (all sanitized as hex):

- Section background
- Tab bar background
- Active tab pill background and text
- Inactive tab text
- Default new-tab card background and text

Each tab can override card background and text on its edit screen.

## Performance and security notes

- One small query (max 20 published tabs), no term cache, no pagination count
- Reorder uses a targeted `menu_order` update, not `wp_update_post`
- Output is escaped; colors go through `sanitize_hex_color`
- Metabox and AJAX reorder require a nonce and capability checks

## File layout

```
low-category-nav-tab/
├── low-category-nav-tab.php
├── includes/
│   ├── class-low-cnt-cpt.php
│   ├── class-low-cnt-metabox.php
│   ├── class-low-cnt-settings.php
│   ├── class-low-cnt-shortcode.php
│   └── class-low-cnt-admin-list.php
└── assets/
    ├── css/frontend.css
    ├── css/admin.css
    ├── js/frontend.js
    ├── js/admin-post.js
    └── js/admin-list.js
```

## License

GPL-2.0-or-later, same as WordPress.
