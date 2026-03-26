# YouTube Sync Feature — Developer Guide

**Plugin:** Islami Dawa Tools
**Feature:** YouTube-to-WordPress Video Sync
**Since:** 1.0.0

---

## Overview

The YouTube Sync feature connects a YouTube channel to WordPress via the **YouTube Data API v3**.  
When a new video is uploaded, it is imported as a `video` custom post with:

| Mapping | WordPress target |
|---|---|
| YouTube title | Post title |
| YouTube watch URL | ACF field `isdc_video_url` |
| Best available thumbnail | Featured image (sideloaded) |

Both **manual** (admin button) and **automatic** (WP-Cron) syncing are supported.

---

## Architecture

```
Inc/
  Api/
    YouTube/
      YouTubeApiService.php    — YouTube Data API v3 HTTP wrapper
      YouTubeImporter.php      — Imports one video (post, meta, ACF, thumbnail)
      YouTubeSyncManager.php   — Orchestrates full & latest-video sync
  Cron/
    CronManager.php            — WP-Cron scheduling & event dispatch
Admin/
  YouTubeSyncSettings.php      — WordPress Settings API registration
  YouTubeSyncPage.php          — Admin UI (card design + SweetAlert2)
  assets/
    css/youtube-sync.css       — Professional admin CSS design tokens
    js/youtube-sync.js         — SweetAlert2 confirm dialogs & toasts
docs/
  youtube-sync-guide.md        — This file
```

### Namespaces

| Path | Namespace |
|---|---|
| `Inc/Api/YouTube/` | `IslamiDawaTools\Api\YouTube` |
| `Inc/Cron/` | `IslamiDawaTools\Cron` |
| `Inc/` | `IslamiDawaTools` |
| `Admin/` | `IslamiDawaTools\Admin` |

---

## Setup

### 1. Get a YouTube API Key

1. Go to [Google Cloud Console → Credentials](https://console.cloud.google.com/apis/credentials)
2. Create (or select) a project
3. Enable **YouTube Data API v3** under *APIs & Services → Library*
4. Create an **API Key** credential
5. (Recommended) Restrict the key to the YouTube Data API v3

### 2. Find Your Channel ID

- In YouTube Studio → **Settings → Channel → Advanced settings**
- Or from your channel URL: `youtube.com/channel/UCxxxxxxxx` (the `UCxxxxxxxx` part)

### 3. Configure the Plugin

1. WordPress Admin → **Islami Dawa Tools → YouTube Sync**
2. Enter your **API Key** and **Channel ID**
3. Click **Save Settings**
4. The WP-Cron event schedules automatically once both fields are saved

---

## Manual Sync

| Button | Behaviour |
|---|---|
| **Sync All Videos** | Fetches every video (full pagination), imports missing ones |
| **Run Latest Sync Now** | Fetches 10 most recent uploads, imports missing ones |

After each sync a **SweetAlert2 modal** displays the result:
- Videos found
- Imported (new)
- Skipped (already imported)
- Failed

---

## Automatic Sync (WP-Cron)

The cron event `islami_dawa_tools_youtube_sync` runs on a filterable interval (default **every 15 minutes**).  
It calls `YouTubeSyncManager::sync_latest_videos(10)` — checking the 10 most recent uploads.

The event is only scheduled when both the API key and Channel ID are saved.  
It is **cleared automatically** when the plugin is deactivated.

### WP-CLI Testing

```bash
# Trigger the cron event manually
wp cron event run islami_dawa_tools_youtube_sync

# Check the event schedule
wp cron event list | grep islami_dawa_tools

# After deactivation — confirm event is gone
wp plugin deactivate islami-dawa-tools
wp cron event list | grep islami_dawa_tools   # should return nothing
```

---

## Duplicate Prevention

Each imported video stores its YouTube ID in post meta:

```
_islami_dawa_tools_youtube_video_id  → "dQw4w9WgXcQ"
```

Before inserting, `YouTubeImporter::video_exists()` queries by this meta key.  
If a match is found, the video is **skipped** — no duplicate post is created.

---

## Post Meta Reference

| Meta Key | Description |
|---|---|
| `_islami_dawa_tools_youtube_video_id` | YouTube video ID (used for duplicate check) |
| `_islami_dawa_tools_youtube_published_at` | ISO 8601 publish date from YouTube |
| `_islami_dawa_tools_youtube_thumbnail_url` | Remote thumbnail URL (avoids re-download) |

---

## ACF Field

The video URL is saved to the `isdc_video_url` ACF field using `update_field()` when ACF is active, falling back to `update_post_meta()` otherwise.

---

## Developer Hooks

```php
// Change cron interval (default 900 = 15 minutes)
add_filter( 'islami_dawa_tools_cron_interval', function() { return 3600; } );

// Change imported post status (default 'publish')
add_filter( 'islami_dawa_tools_video_post_status', function() { return 'draft'; } );

// Modify post title before insertion
add_filter( 'islami_dawa_tools_video_post_title', function( $title, $video ) {
    return '[Video] ' . $title;
}, 10, 2 );

// Run code before/after each video import
add_action( 'islami_dawa_tools_before_video_import', function( $video ) { /* ... */ } );
add_action( 'islami_dawa_tools_after_video_import', function( $post_id, $video ) { /* ... */ }, 10, 2 );
```

---

## Adding Future API Services

The `Inc/Api/` directory is intentionally structured for extensibility.

To add a new service (e.g. Vimeo):

1. Create `Inc/Api/Vimeo/VimeoApiService.php` with namespace `IslamiDawaTools\Api\Vimeo`
2. Create `Inc/Api/Vimeo/VimeoImporter.php`
3. Create `Inc/Api/Vimeo/VimeoSyncManager.php`
4. If a new cron job is needed, create a method/class under `Inc/Cron/`
5. Instantiate in `Inc/Manager.php` following the existing pattern
6. Add an admin submenu page in `Admin/`

No changes to `composer.json` are required — `IslamiDawaTools\\` is already mapped to `Inc/` via PSR-4.

---

## API Quota Reference

| Operation | API Units |
|---|---|
| `channels.list` (get playlist ID) | 1 unit/call |
| `playlistItems.list` (50 videos/page) | 1 unit/page |
| Full sync of 500-video channel | ~11 units |
| Cron sync (1 page, every 15 min) | 2 units/run = 192 units/day |

**Free tier:** 10,000 units/day — well within limits for typical usage.
