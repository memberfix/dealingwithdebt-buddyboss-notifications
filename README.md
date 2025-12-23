# MFX Series Subscribe & Channels

A complete WordPress series management system with subscriptions, favorites, view tracking, popularity scoring, and Netflix-style content discovery channels.

## Features

### Netflix-Style Content Discovery
- **Channels Interface** - Modern, responsive Netflix-inspired content browsing
- **Featured Carousel** - Auto-rotating hero carousel with:
  - Custom carousel images for posts and series
  - View and My Favs buttons
  - Progress indicator dots
  - Category/tag display
  - Configurable rotation speed
- **Tabbed Navigation** - Switch between Series and Articles views
- **Content Rows** - Multiple row types for content organization:
  - Featured content
  - My Favorites (personalized)
  - Most Popular articles
  - Most Popular series
  - Most Recent
  - Series categories

### Series Subscriptions
- **Subscribe/Unsubscribe** - Users can subscribe to series
- **Email Notifications** - Subscribers notified when new posts published in series
- **Subscriber Counts** - Display subscriber counts on series
- **Database Storage** - Custom table for efficient subscription management

### Favorites System
- **Article Favorites** - Save favorite posts via star button or BuddyBoss activity likes
- **Series Favorites** - Subscribing to a series = favoriting it (unified system)
- **My Favorites Row** - Personalized row showing user's favorites

### View Tracking & Popularity
- **View Tracking** - Track post views with configurable time window to prevent repeat counting
- **Popularity Scoring** - Automatic daily calculation based on:
  - Views
  - Comments
  - Subscriptions
  - Favorites
  - Recency
- **Configurable Weights** - Adjust importance of each popularity factor
- **Lookback Period** - Configure how many days to consider for popularity

### BuddyBoss Integration
- **Activity Sync** - Liking/reacting to post activity auto-adds to favorites
- **Notifications** - BuddyBoss notification system integration for:
  - New posts in subscribed series
  - Author follow notifications

### Admin Features
- **Featured Content** - Mark posts and series as featured
- **Carousel Images** - Upload custom images for posts and series
- **Settings Panel** - Configure:
  - Enabled content rows
  - Tracking window (minutes)
  - Popularity lookback (days)
  - Carousel item limit
  - Carousel rotation speed (seconds)
- **Rebuild Popularity** - Manual button to recalculate all scores

## Shortcodes

### Main Channels Display
```
[series_channels]
```
Displays the full Netflix-style channels interface.

**Parameters:**
| Parameter | Default | Description |
|-----------|---------|-------------|
| `rows` | `featured,favorites,popular_articles,popular_series,recently_published,categories` | Comma-separated list of rows to display |
| `fullbleed` | `0` | Set to `1` for full-width display |

### Series Subscribe Button
```
[series_subscribe_button]
```
Displays a subscribe/unsubscribe button for the current series.

**Parameters:**
| Parameter | Default | Description |
|-----------|---------|-------------|
| `series_id` | `0` | Specific series ID (auto-detects if not set) |
| `series_slug` | `''` | Series slug (alternative to ID) |
| `button_class` | `series-subscribe-btn` | CSS class for button |
| `subscribe_text` | `Subscribe to Series` | Button text when not subscribed |
| `unsubscribe_text` | `Unsubscribe from Series` | Button text when subscribed |
| `login_text` | `Login to Subscribe` | Button text for logged-out users |
| `show_count` | `false` | Show subscriber count |

### Series Categories Carousel
```
[series_categories_carousel]
```
Displays series organized by their categories/groups.

**Parameters:**
| Parameter | Default | Description |
|-----------|---------|-------------|
| `exclude` | `''` | Category IDs to exclude |
| `include` | `''` | Category IDs to include |
| `limit` | `-1` | Maximum categories to show |
| `orderby` | `name` | Order by field |
| `order` | `ASC` | Sort order |
| `hide_empty` | `true` | Hide empty categories |

### Dynamic Series Heading
```
[mfx_series_heading]
```
Displays dynamic heading based on current series context.

### Series Icon URL
```
[series_icon_url]
```
Returns the URL of the current series icon.

## Admin Settings

Navigate to **Series Subscribe** in the WordPress admin menu.

### Channels Settings
- **Enabled Rows** - Toggle visibility of each content row
- **Tracking Window** - Minutes between counting repeat views (default: 30)
- **Popularity Lookback** - Days to consider for popularity (default: 120)
- **Featured Carousel Limit** - Max items in carousel (default: 10)
- **Carousel Rotation Speed** - Seconds between slides (default: 5)

### Post Edit Screen
- **Mark as Featured Post** - Include in featured carousel
- **Carousel Image** - Custom image for carousel display

### Series Edit Screen
- **Mark as Featured Series** - Include in featured carousel
- **Carousel Image** - Custom image for carousel display

## CSS Classes

BEM-style classes for easy customization:

### Main Container
- `.series-channels` - Main wrapper
- `.series-channels__tabs` - Tab navigation container
- `.series-channels__tab` - Individual tab
- `.series-channels__tab--active` - Active tab state
- `.series-channels__rows` - Rows container

### Featured Carousel
- `.series-hero-carousel` - Carousel section
- `.series-hero-carousel__wrap` - Carousel wrapper
- `.series-hero-carousel__track` - Slide track
- `.series-hero-carousel__slide` - Individual slide
- `.series-hero-carousel__imgwrap` - Image wrapper
- `.series-hero-carousel__nav` - Navigation arrows
- `.series-hero-carousel__tags` - Tags/categories display
- `.series-hero-carousel__buttons` - Action buttons container
- `.series-hero-carousel__btn` - Action button
- `.series-hero-carousel__btn--primary` - View button
- `.series-hero-carousel__btn--secondary` - Favorite button
- `.series-hero-carousel__dots` - Progress dots container
- `.series-hero-carousel__dot` - Individual dot

### Content Rows
- `.series-row` - Row section
- `.series-row__title` - Row heading
- `.series-row__scroller` - Horizontal scroller
- `.series-row__arrow` - Navigation arrow

### Content Cards
- `.series-card` - Card wrapper
- `.series-card__image` - Card image
- `.series-card__meta` - Card metadata
- `.series-card__title` - Card title
- `.series-card__subscribe` - Favorite button
- `.series-card__subscribe.subscribed` - Favorited state

## REST API Endpoints

Base: `/wp-json/series-subscribe/v1/`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/channels/rows` | GET | Get channel rows data |
| `/channels/favorite` | POST | Toggle favorite status |
| `/record-view/{post_id}` | POST | Record a post view |

## Database

Creates custom table: `{prefix}_series_subscriptions`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | WordPress user ID |
| `series_id` | bigint | Series term ID |
| `subscribed_date` | datetime | Subscription timestamp |

## Hooks & Filters

### Actions
- `series_subscribe_user_subscribed` - Fired when user subscribes
- `series_subscribe_user_unsubscribed` - Fired when user unsubscribes
- `series_subscribe_activity_favorite_synced` - Fired when BuddyBoss like synced
- `before_series_subscribe_init` - Before plugin initializes
- `series_subscribe_init` - After plugin initializes

## Requirements

- WordPress 5.0+
- PHP 7.4+
- PublishPress Series Pro (for series functionality)
- BuddyBoss Platform (optional, for activity/notifications integration)

## Installation

1. Upload the `mfx-series-subscribe` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Configure settings under **Series Subscribe** in admin
4. Add `[series_channels]` shortcode to any page

## File Structure

```
mfx-series-subscribe/
├── assets/
│   ├── css/
│   │   ├── channels-style.css
│   │   └── series-subscribe.css
│   └── js/
│       ├── channels-frontend.js
│       └── series-subscribe.js
├── includes/
│   ├── class-admin.php
│   ├── class-buddyboss-integration.php
│   ├── class-database.php
│   ├── class-favorites.php
│   ├── class-notifications.php
│   ├── class-popularity.php
│   ├── class-rest-api.php
│   ├── class-shortcodes.php
│   └── class-view-tracking.php
├── README.md
└── series-subscribe.php
```

## Changelog

### 2.0
- Added Netflix-style channels interface
- Added featured carousel with auto-rotation, custom images, progress dots
- Added View and My Favs buttons on carousel slides
- Added category/tag display on carousel items
- Added Series/Articles tabbed navigation
- Added carousel image uploads for posts and series
- Added admin settings for carousel customization
- Unified series subscriptions with favorites system
- Added popularity scoring with configurable weights
- Added BuddyBoss activity like sync to favorites
- Added REST API endpoints

### 1.0
- Initial release
- Series subscription system
- BuddyBoss notifications
- Basic shortcodes

## Author

[Memberfix](https://memberfix.rocks)

## License

GPL v2 or later
