# MFX Series Subscribe & Channels

A complete WordPress series management system with subscriptions, favorites, view tracking, popularity scoring, and Netflix-style content discovery channels.

## Features

### Content Discovery
- **Netflix-style Channels Interface** - Modern, responsive content browsing experience
- **Featured Carousel** - Auto-rotating hero carousel with custom images, View/Favorite buttons, and progress dots
- **Tabbed Navigation** - Switch between Series and Articles views
- **Category Rows** - Browse content organized by series categories

### Series Management
- **Series Subscriptions** - Users can subscribe to series to receive notifications
- **Favorites System** - Save favorite articles and series (subscribed series = favorited)
- **View Tracking** - Track article views with configurable time windows
- **Popularity Scoring** - Automatic popularity calculation based on views over configurable time periods

### Admin Features
- **Featured Content** - Mark posts and series as featured for the carousel
- **Carousel Images** - Upload custom images for posts and series in the carousel
- **Carousel Settings** - Configure rotation speed and item limits
- **Popularity Settings** - Configure tracking window and lookback days

### Integrations
- **BuddyBoss Integration** - Activity feed integration and notifications
- **PublishPress Series Pro** - Full compatibility with series and series groups

## Shortcodes

### Main Channels Display
```
[series_channels]
```

**Parameters:**
- `rows` - Comma-separated list of rows to display (default: `featured,favorites,popular_articles,popular_series,recently_published,categories`)
- `fullbleed` - Set to `1` for full-width display

### Series Subscribe Button
```
[series_subscribe_button]
```
Displays a subscribe/unsubscribe button for the current series.

### Series Categories Carousel
```
[series_categories_carousel]
```
Displays series organized by their categories.

## Admin Settings

Navigate to **Series Subscribe** in the WordPress admin menu to configure:

### Channels Section
- **Enabled Rows** - Toggle which rows appear in the channels display
- **Tracking Window** - Minutes between counting repeat views (default: 30)
- **Popularity Lookback** - Days to consider for popularity scoring (default: 120)
- **Featured Carousel Limit** - Maximum items in the featured carousel (default: 10)
- **Carousel Rotation Speed** - Seconds between automatic slide changes (default: 5)

### Post Settings
When editing a post, you can:
- Mark as **Featured Post** for carousel inclusion
- Upload a custom **Carousel Image**

### Series Settings
When editing a series term, you can:
- Mark as **Featured Series** for carousel inclusion
- Upload a custom **Carousel Image**

## CSS Classes

The plugin uses BEM-style CSS classes for easy customization:

- `.series-channels` - Main container
- `.series-channels__tabs` - Tab navigation
- `.series-hero-carousel` - Featured carousel section
- `.series-hero-carousel__buttons` - View/Favorite buttons
- `.series-hero-carousel__tags` - Category tags display
- `.series-hero-carousel__dots` - Progress indicator
- `.series-row` - Content row section
- `.series-card` - Individual content card

## Requirements

- WordPress 5.0+
- PHP 7.4+
- PublishPress Series Pro (for series functionality)
- BuddyBoss Platform (optional, for activity/notifications)

## Installation

1. Upload the `mfx-series-subscribe` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Configure settings under **Series Subscribe** in the admin menu
4. Add the `[series_channels]` shortcode to any page

## Changelog

### 2.0
- Added Netflix-style channels interface
- Added featured carousel with auto-rotation
- Added View and My Favs buttons on carousel
- Added progress dots for carousel navigation
- Added category tags display on carousel items
- Added Series/Articles tabbed navigation
- Added carousel image uploads for posts and series
- Added admin settings for carousel customization
- Integrated series subscriptions with favorites system
- Added popularity scoring system

### 1.0
- Initial release with series subscriptions
- Basic favorites functionality
- View tracking

## Author

[Memberfix](https://memberfix.rocks)

## License

GPL v2 or later
