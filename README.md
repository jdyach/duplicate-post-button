# Duplicate Post Button

A lightweight WordPress plugin that adds a one-click **Duplicate** row action to the Posts and Pages list screen. Clones the post content, all post meta, and taxonomy terms into a new draft — no configuration needed.

[![Try it live in WordPress Playground](https://img.shields.io/badge/Try%20it%20live-WordPress%20Playground-3858e9?logo=wordpress&logoColor=white&style=for-the-badge)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/jdyach/duplicate-post-button/main/_playground/blueprint.json)

The live demo runs a real WordPress site in your browser with this plugin, the [Fieldnote](https://github.com/jdyach/fieldnote) block theme and sample content. It opens the Posts screen. Hover any post and click **Duplicate** to get a draft copy with its categories, tags, featured image and custom fields. Nothing to install.

## Features

- **One-click duplication** from the post/page list screen row actions
- Clones post content, excerpt, attributes, and password
- Copies **all post meta** (skipping internal WordPress keys like `_edit_lock`)
- Copies **all taxonomy terms** (categories, tags, and custom taxonomies)
- New post is always created as a **Draft**, authored by the current user
- Redirects directly to the new draft's edit screen with a success notice
- Works on **Posts**, **Pages**, and any custom post type that uses standard row actions
- Nonce-verified and capability-checked on every request

## WordPress Concepts Demonstrated

| Concept | Where Used |
|---|---|
| `post_row_actions` / `page_row_actions` filters | Adding the Duplicate link to list tables |
| `admin_action_{action}` hook | Handling the GET request securely |
| `check_admin_referer()` | Nonce verification on the duplicate action |
| `current_user_can( 'edit_post', $id )` | Per-post capability check |
| `wp_insert_post()` | Creating the cloned draft |
| `get_post_meta()` / `add_post_meta()` | Copying all post meta to the new post |
| `get_object_taxonomies()` | Discovering all taxonomies for a post type |
| `wp_get_object_terms()` / `wp_set_object_terms()` | Copying taxonomy assignments |
| `wp_safe_redirect()` + `exit` | Safe redirect after processing |
| `admin_notices` hook | Showing a success message after redirect |

## File Structure

```
duplicate-post-button/
├── duplicate-post-button.php   # Plugin bootstrap
├── includes/
│   └── class-dpb-duplicator.php  # Row actions, duplication logic, admin notice
├── assets/
│   └── css/
│       └── admin.css           # Row action link styling
└── README.md
```

## Installation

1. Upload the `duplicate-post-button` folder to `/wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **Posts** or **Pages** and hover over any entry — a **Duplicate** link appears in the row actions

## Usage

1. Hover over any post or page in the list screen
2. Click **Duplicate** in the row actions
3. You are taken directly to the edit screen of the new draft
4. The new post is titled **"[Original Title] (Copy)"** and is set to **Draft**

## Requirements

- WordPress 6.0+
- PHP 8.0+
