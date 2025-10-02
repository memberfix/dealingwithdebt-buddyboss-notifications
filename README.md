# Series and Author Subscribe

A WordPress plugin that allows users to subscribe to series and follow authors to receive BuddyBoss notifications when new posts are published.

## Description

Series and Author Subscribe extends your WordPress site with powerful subscription features, enabling users to:
- Subscribe to specific series to get notified when new articles are published
- Follow authors to receive notifications about their new content
- Receive notifications through BuddyBoss's notification system (both in-app and email)
- Manage subscriptions with a simple, user-friendly interface

## Features

- **Series Subscriptions**: Users can subscribe to any series taxonomy term and receive notifications when new posts are published in that series
- **Author Following**: Automatically notifies users when authors they follow publish new content (requires BuddyBoss Activity Follow)
- **BuddyBoss Integration**: Seamlessly integrates with BuddyBoss notification system for in-app, email, web push, and app push notifications
- **Smart Notifications**: Prevents duplicate notifications (users subscribed to a series won't receive separate author follow notifications for the same post)
- **Customizable Shortcode**: Flexible shortcode with multiple attributes for different use cases
- **AJAX-Powered**: Subscribe/unsubscribe actions happen instantly without page reloads
- **Subscription Management**: Database-backed subscription tracking with helper functions for managing subscriptions
- **Translation Ready**: Fully internationalized and ready for translation

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- BuddyBoss Platform plugin
- A custom taxonomy named `series` for post categorization
- (Optional) BuddyBoss Activity Follow for author following features

## Installation

1. Download the plugin files
2. Upload the `series-subscribe` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The plugin will automatically create the necessary database tables

## Usage

### Shortcode

The plugin provides a `[series_subscribe_button]` shortcode with the following attributes:

```
[series_subscribe_button
    series_id="123"
    series_slug="my-series"
    button_class="custom-btn-class"
    subscribe_text="Subscribe to Series"
    unsubscribe_text="Unsubscribe from Series"
    login_text="Login to Subscribe"
    show_count="false"
]
```

#### Shortcode Attributes

| Attribute | Default | Description |
|-----------|---------|-------------|
| `series_id` | `0` | The term ID of the series. If not provided, the plugin will auto-detect from context |
| `series_slug` | `''` | The slug of the series. Alternative to `series_id` |
| `button_class` | `'series-subscribe-btn'` | Custom CSS class for the button |
| `subscribe_text` | `'Subscribe to Series'` | Text shown when user is not subscribed |
| `unsubscribe_text` | `'Unsubscribe from Series'` | Text shown when user is subscribed |
| `login_text` | `'Login to Subscribe'` | Text shown to non-logged-in users |
| `show_count` | `'false'` | Whether to show subscriber count (future feature) |

### Auto-Detection

The shortcode can automatically detect the series in the following contexts:

1. **Series archive page**: When used on a series taxonomy archive, it automatically detects the current series
2. **Single post page**: When used on a single post, it detects the first series assigned to that post
3. **Manual specification**: You can always manually specify the series using `series_id` or `series_slug`

### Example Usage

```
<!-- On a series archive page -->
[series_subscribe_button]

<!-- For a specific series by ID -->
[series_subscribe_button series_id="42"]

<!-- For a specific series by slug with custom text -->
[series_subscribe_button series_slug="javascript-basics" subscribe_text="Follow this Series"]

<!-- With custom styling -->
[series_subscribe_button button_class="btn btn-primary custom-subscribe"]
```

## Notification Types

### Series Notifications

When a new post is published in a series, all subscribed users receive:
- **In-app notification**: "{Author} published a new article in "{Series}": {Post Title}"
- **Email notification**: Customizable email with post excerpt and link
- **Push notifications**: Mobile and web push support through BuddyBoss

### Author Follow Notifications

When an author publishes a new post, their followers receive:
- **In-app notification**: "An article titled {Post Title} has been published by {Author}"
- **Email notification**: Customizable email with post details
- **Push notifications**: Optimized messages for mobile and web push

## Developer Hooks

### Actions

```php
// Fired when a user subscribes to a series
do_action( 'series_subscribe_user_subscribed', $user_id, $series_id );

// Fired when a user unsubscribes from a series
do_action( 'series_subscribe_user_unsubscribed', $user_id, $series_id );

// Fired before Series Subscribe initialization
do_action( 'before_series_subscribe_init' );

// Fired after Series Subscribe initialization
do_action( 'series_subscribe_init' );
```

### Database Methods

Access the database class for custom functionality:

```php
$db = new Series_Subscribe_Database();

// Subscribe a user to a series
$db->subscribe_user( $user_id, $series_id );

// Unsubscribe a user from a series
$db->unsubscribe_user( $user_id, $series_id );

// Check if user is subscribed
$is_subscribed = $db->is_user_subscribed( $user_id, $series_id );

// Get all subscribers for a series
$subscriber_ids = $db->get_series_subscribers( $series_id );

// Get all series a user is subscribed to
$series_ids = $db->get_user_subscriptions( $user_id );

// Get subscriber count for a series
$count = $db->get_subscriber_count( $series_id );

// Get subscription statistics
$stats = $db->get_subscription_stats();

// Clean up orphaned subscriptions
$db->cleanup_orphaned_subscriptions();
```

## Database Structure

The plugin creates a custom table `wp_series_subscriptions` with the following structure:

```sql
CREATE TABLE wp_series_subscriptions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    series_id bigint(20) NOT NULL,
    subscribed_date datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_subscription (user_id, series_id),
    KEY user_id (user_id),
    KEY series_id (series_id)
);
```

## Customization

### Custom Styles

The plugin enqueues a CSS file at `assets/css/series-subscribe.css`. You can override these styles in your theme or add custom classes using the `button_class` shortcode attribute.

### Custom JavaScript

The plugin uses jQuery for AJAX functionality. The JavaScript file is located at `assets/js/series-subscribe.js`.

### Email Templates

Email notifications are managed through BuddyBoss's email template system. You can customize:
- Email title
- Email content (HTML and plain text)
- Unsubscribe text

Navigate to BuddyBoss > Settings > Emails to customize the following email types:
- "New Article in Subscribed Series"
- "New Article by Followed Author"

## Changelog

### Version 1.1
- Added author follow notification functionality
- Improved notification system integration
- Enhanced duplicate notification prevention
- Updated documentation

### Version 1.0.0
- Initial release
- Series subscription functionality
- BuddyBoss notification integration
- Shortcode support

## Support

For issues, questions, or feature requests, please visit [https://memberfix.rocks](https://memberfix.rocks)

## Credits

**Author**: Memberfix
**Plugin URI**: https://memberfix.rocks
**Text Domain**: series-subscribe

## License

This plugin is proprietary software developed by Memberfix.
