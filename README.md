# Kntnt Popup
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Requires PHP: 8.2+](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Requires WordPress: 6.8+](https://img.shields.io/badge/WordPress-6.8+-blue.svg)](https://wordpress.org)

WordPress plugin that provides shortcode for creating popups.

## Description

Kntnt Popup is a lightweight, customizable WordPress plugin that provides an easy-to-use shortcode for creating modal popups on your website. With this plugin, you can create popups triggered by various user actions without writing any JavaScript or HTML code.

### Key Features:
- Simple shortcode implementation with extensive customization options
- Multiple trigger options: exit intent, time delay, scroll position
- Customizable animations for opening and closing
- Full control over positioning, dimensions, and appearance
- Lightweight implementation using Micromodal.js
- No additional JavaScript configuration required
- Fully responsive design
- Built with modern PHP and JavaScript practices

## Installation
1. [Download the plugin zip archive.](https://github.com/Kntnt/kntnt-popup/releases/latest/download/kntnt-popup.zip)
2. Go to WordPress admin panel → Plugins → Add New.
3. Click "Upload Plugin" and select the downloaded zip archive.
4. Activate the plugin.

## Usage
The plugin provides a shortcode `[kntnt-popup]...[/kntnt-popup]` where the content between the opening and closing tags will be displayed in the popup.

### Basic Usage:
```
[kntnt-popup show-after-time="5"]
Your popup content here. Can include text, images, forms, and even other shortcodes.
[/kntnt-popup]
```

### Available Parameters:

#### Trigger Parameters:
| Parameter | Values | Default | Description |
|-----------|--------|---------|-------------|
| `shown-on-exit-intent` | `true`, `false` | `false` (if omitted)<br>`true` (if no value) | Shows popup when user moves cursor to leave page |
| `show-after-time` | Number of seconds or `false` | `false` (if omitted)<br>`30` (if no value) | Shows popup after specified number of seconds |
| `show-after-scroll` | Percentage or `false` | `false` (if omitted)<br>`80` (if no value) | Shows popup when user has scrolled the specified percentage of page |

#### Appearance and Behavior:
| Parameter | Values | Default | Description |
|-----------|--------|---------|-------------|
| `id` | Any valid ID string or `false` | `false` | Sets the ID attribute for popup's wrapper div |
| `close-button` | Character to use or `false` | `false` (if omitted)<br>`✖` (if no value) | Displays a close button with specified character |
| `close-outside-click` | `true`, `false` | `false` (if omitted)<br>`true` (if no value) | Closes popup when clicking outside popup area |
| `modal` | `true`, `false` | `false` (if omitted)<br>`true` (if no value) | Makes popup modal (focus trapped, scrolling prevented) |
| `overlay-color` | Any valid CSS color | `rgba(0,0,0,80%)` | Sets color of overlay behind popup |
| `width` | Any valid CSS length | `clamp(300px, 90vw, 800px)` | Sets desired popup width |
| `max-height` | Any valid CSS length | `95vh` | Sets maximum popup height before scrolling activates |
| `padding` | Any valid CSS length | `clamp(20px, calc(5.2vw - 20px), 160px)` | Sets popup padding |
| `position` | `center`, `top`, `top-right`, `right`, `bottom-right`, `bottom`, `bottom-left`, `left`, `top-left` | `center` | Determines popup position |
| `class` | CSS class name(s) | None | Adds custom CSS classes to popup element |

#### User Experience:
| Parameter | Values | Default | Description |
|-----------|--------|---------|-------------|
| `reappear-delay` | Number with optional prefix:<br>- No prefix or `s`: seconds<br>- `m`: minutes<br>- `h`: hours<br>- `d`: days | `0` (if omitted)<br>`1d` (if no value) | Time before popup can appear again after being closed |

#### Animation Parameters:
| Parameter | Values | Default | Description |
|-----------|--------|---------|-------------|
| `open-animation` | `false`, `tada`, `fade-in`, `fade-in-top`, `fade-in-right`, `fade-in-bottom`, `fade-in-left`, `slide-in-top`, `slide-in-right`, `slide-in-bottom`, `slide-in-left` | `false` (if omitted)<br>`tada` (if no value) | Animation when popup appears |
| `close-animation` | `false`, `fade-out`, `fade-out-top`, `fade-out-right`, `fade-out-bottom`, `fade-out-left`, `slide-out-top`, `slide-out-right`, `slide-out-bottom`, `slide-out-left` | `false` (if omitted)<br>`fade-out` (if no value) | Animation when popup closes |
| `open-animation-duration` | Time in milliseconds | Animation's default duration | Overrides default open animation duration |
| `close-animation-duration` | Time in milliseconds | Animation's default duration | Overrides default close animation duration |

### Examples:

Show a popup after 5 seconds with a close button:
```
[kntnt-popup show-after-time="5" close-button]
<h2>Welcome to our site!</h2>
<p>Check out our latest offers.</p>
[/kntnt-popup]
```

Show a popup on exit intent with custom width and position:
```
[kntnt-popup shown-on-exit-intent width="400px" position="top"]
<h2>Wait before you go!</h2>
<p>Subscribe to our newsletter to get exclusive content.</p>
[/kntnt-popup]
```

Show a popup after scrolling 50% with fade-in animation:
```
[kntnt-popup show-after-scroll="50" open-animation="fade-in" close-outside-click]
<h2>You're halfway there!</h2>
<p>Want to learn more about our products?</p>
[/kntnt-popup]
```

### Developer Hooks

The plugin provides a few hooks for developers to customize the popup behavior:

#### WordPress Filters:

##### `kntnt_popup_shortcode_atts`
Modifies shortcode attributes before they're processed.
```php
add_filter('kntnt_popup_shortcode_atts', function($attributes) {
    // Modify attributes based on conditions
    if (is_front_page()) {
        $attributes['show-after-time'] = '5';
    }
    return $attributes;
});
```

##### `kntnt_popup_content`
Filters the popup content before it's displayed.
```php
add_filter('kntnt_popup_content', function($content, $popup_id) {
    // Personalize content for logged-in users
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $content = str_replace('{user_name}', $user->display_name, $content);
    }
    return $content;
}, 10, 2);
```

##### `kntnt_popup_armed`
Determines whether a popup should be shown at all.
```php
add_filter('kntnt_popup_armed', function($armed) {
    // Disable popups for administrators
    if (current_user_can('administrator')) {
        return false;
    }
    // Disable popups on specific pages
    if (is_page('contact')) {
        return false;
    }
    return $armed;
});
```

#### JavaScript Events:

Event listeners should be added before the popup is initialized:

##### `kntnt_popup:before_open`
Triggered before a popup is opened.
```javascript
document.addEventListener('kntnt_popup:before_open', function(event) {
    // Pause videos when popup opens
    document.querySelectorAll('video').forEach(function(video) {
        video.pause();
    });
});
```

##### `kntnt_popup:after_close`
Triggered after a popup is closed.
```javascript
document.addEventListener('kntnt_popup:after_close', function(event) {
    // Resume background content after popup closes
    document.querySelectorAll('.background-video').forEach(function(video) {
        video.play();
    });
});
```

## Questions & Answers

### How can I get help?
If you have questions about the plugin and cannot find an answer here, start by looking at issues and pull requests on our GitHub repository. If you still cannot find the answer, feel free to ask in the plugin's issue tracker on GitHub.

### How can I report a bug?
If you have found a potential bug, please report it on the plugin's issue tracker on GitHub.

### How can I contribute?
Contributions to the code or documentation are much appreciated.
If you are familiar with Git, please do a pull request.
If you are not familiar with Git, please create a new ticket on the plugin's issue tracker on GitHub.

## Changelog
### 1.0.0 (2025-04-19)
- Initial release
- Added shortcode functionality with comprehensive parameter options
- Implemented multiple trigger mechanisms (exit intent, time delay, scroll position)
- Added customizable positioning and styling options
- Included animation options for opening and closing popups
- Integrated Micromodal.js for lightweight modal functionality