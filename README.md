# Kntnt Popup

[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Requires PHP: 8.2+](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Requires WordPress: 6.8+](https://img.shields.io/badge/WordPress-6.8+-blue.svg)](https://wordpress.org)

WordPress plugin that provides shortcode for creating popups.

## Description

Kntnt Popup is a lightweight, customizable WordPress plugin that provides an easy-to-use shortcode for creating modal popups on your website. With this plugin, you can create popups triggered by various user actions without writing any JavaScript or HTML code.

This plugin is built on top of [Micromodal.js](https://micromodal.vercel.app/) by Indrashish Ghosh, a powerful and accessible micro modal library. We extend our sincere gratitude to the Micromodal.js project and its contributors for providing such an excellent foundation for creating accessible modal dialogs.

### Key Features:

- Simple shortcode implementation with extensive customization options
- Multiple trigger options: exit intent, time delay, scroll position
- Customizable animations for opening and closing
- Full control over positioning, dimensions, and appearance
- Lightweight implementation using Micromodal.js
- No additional JavaScript configuration required
- Fully responsive design
- Supports nested shortcodes within the popup content (e.g., forms, galleries)
- Built with modern PHP and JavaScript practices

## Installation

1. [Download the plugin zip archive.](https://github.com/Kntnt/kntnt-popup/releases/latest/download/kntnt-popup.zip)
2. Go to WordPress admin panel → Plugins → Add New.
3. Click "Upload Plugin" and select the downloaded zip archive.
4. Activate the plugin.

## Usage

The plugin provides a shortcode `[popup]...[/popup]` where the content between the opening and closing tags will be displayed in the popup. This content is processed normally, meaning you can include text, images, HTML, and even other shortcodes inside the popup.

Basic usage:

```
[popup modal show-after-time=3 close-button close-outside-click close-esc-key]This is a typical pop-up.[/popup]
```

Here's a sophisticated popup that combines multiple opening triggers and closing methods:

```
[popup modal show-after-time=30 show-after-scroll=50 show-on-exit-intent close-button close-outside-click close-esc-key overlay-color="rgba(0 0 0 / 50%)" style-overlay="backdrop-filter:blur(5px);" open-animation="fade-in-top" close-animation="fade-out-top" aria-label-popup="Demo"]
<h2>Popup demo</h2>
<p>This popup can be triggered by:</p>
<ul>
  <li>Waiting 30 seconds on the page</li>
  <li>Scrolling 50% of the page</li>
  <li>Moving your mouse to leave the page</li>
</ul>
<p>You can close it by:</p>
<ul>
  <li>Clicking the × button</li>
  <li>Clicking outside the popup</li>
  <li>Pressing the ESC key</li>
  <li>Or by <a data-popup-close>clicking this link</a></li>
</ul>
[/popup]
```

This example creates a modal popup that will appear when ANY of the three trigger conditions are met (whichever happens first). Users can then close it using any of the four available methods, providing maximum flexibility and user control.

## Triggers

Kntnt Popup offers multiple ways to control when and how popups appear and disappear. You can use automatic triggers, manual controls, or combine multiple methods to create the perfect user experience.

### Time-based trigger

**Delayed display:** Show a popup after a specified number of seconds:

```
[popup show-after-time="10"]This popup appears after 10 seconds.[/popup]
```

**Immediate display:** Show a popup as soon as the page loads:

```
[popup show-after-time="0"]This popup appears immediately when the page loads.[/popup]
```

### Scroll-based trigger

Show a popup when the user has scrolled a certain percentage of the page:

```
[popup show-after-scroll="75"]This popup appears when you've scrolled 75% of the page.[/popup]
```

### Exit intent trigger

Trigger a popup when the user moves their mouse cursor toward the browser's address bar or tab area, indicating they might be about to leave:

```
[popup show-on-exit-intent]Wait! Don't leave yet. Check out this special offer![/popup]
```

*Note: Exit intent only works on desktop/laptop devices with a mouse cursor.*

### Clickable triggers

1. **Define the popup:** First, create a popup with a unique ID:
   ```
   [popup id="newsletter-signup"]
   <h2>Subscribe to our newsletter</h2>
   <p>Get weekly updates delivered to your inbox.</p>
   [/popup]
   ```

2. **Create trigger elements:** Add the `data-popup-open` attribute to any HTML element to make it open the popup:

   **Text link:**
   ```html
   <a href="#" data-popup-open="newsletter-signup">Subscribe to our newsletter</a>
   ```

   **Button:**
   ```html
   <button data-popup-open="newsletter-signup">Sign Up Now</button>
   ```

   **Image:**
   ```html
   <img src="signup-banner.jpg" data-popup-open="newsletter-signup" alt="Click to subscribe">
   ```

   **Any element:**
   ```html
   <div class="promo-box" data-popup-open="newsletter-signup">
     <h3>Special Offer!</h3>
     <p>Click anywhere on this box to learn more</p>
   </div>
   ```

*Important:* Both the popup shortcode and trigger elements must exist on the same page.

## Closing popups

Kntnt Popup provides several ways for users to close popups, giving you complete control over the user experience.

### Built-in close button

Add a close button (×) in the top-right corner of the popup:

```
[popup close-button]This popup has a close button.[/popup]
```

You can customize the close button character:

```
[popup close-button="✕"]This popup uses a different close icon.[/popup]
```

### Click outside to close

Allow users to close the popup by clicking anywhere outside the popup area:

```
[popup close-outside-click]Click outside this popup to close it.[/popup]
```

### ESC key to close

Enable closing the popup by pressing the ESC key:

```
[popup close-esc-key]Press ESC to close this popup.[/popup]
```

### Custom close triggers

Make any element inside or outside the popup close it by adding the `data-popup-close` attribute:

**Close link inside popup content:**

```
[popup modal show-after-time="5"]
<h2>Welcome!</h2>
<p>Thanks for visiting our site.</p>
<p><a data-popup-close>Close this message</a></p>
[/popup]
```

**Close button inside popup:**

```
[popup show-after-scroll="50"]
<h2>Newsletter Signup</h2>
<form>
  <!-- form fields here -->
  <button type="submit">Subscribe</button>
  <button type="button" data-popup-close>Maybe Later</button>
</form>
[/popup]
```

**External close trigger (anywhere on the page):**

```html
<!-- This button can be anywhere on your page -->
<button data-popup-close>Close any open popup</button>
```

## Parameters

The shortcode accepts various parameters to customize the popup's behavior and appearance. Parameters can be used in three different ways:

* **Assignment:** The parameter name followed by an equals sign and a value within quotation marks. Example: `show-after-time="15"` sets the parameter to show after 15 seconds.
* **Flag:** The parameter name alone, which sets the parameter to a predefined value called the *flag value*. Example: `show-after-time` is equivalent to `show-after-time="30"`.
* **Omitted:** If the parameter is not included at all, a *default value* is used. Example: If `show-after-time` is omitted, it's equivalent to `show-after-time="false"`.

Some parameters accept different types of values, such as numbers, strings, or booleans.

### Trigger Parameters

These parameters control when and how the popup appears.

#### `show-on-exit-intent`

Controls whether the popup shows when the user moves their cursor to leave the page.

*Format:* `show-on-exit-intent=<true|false>`

*Flag value:* `true`

*Default value:* `false`

*Examples:*

* `[popup show-on-exit-intent="true"]`: Triggers popup when user attempts to leave the page
* `[popup show-on-exit-intent]`: Same as above since flag value is `true`
* `[popup]`: Won't trigger popup when user attempts to leave the page since default value is `false`

#### `show-after-time`

Controls whether the popup shows after a specified number of seconds.

*Format:* `show-after-time=<seconds|false>`

*Flag value:* `30` (seconds)

*Default value:* `false`

*Note:* This parameter expects a numeric value without units, representing seconds.

*Examples:*

* `[popup show-after-time="5"]`: Triggers popup after 5 seconds
* `[popup show-after-time="0"]`: Triggers popup immediately when the page loads
* `[popup show-after-time]`: Triggers popup after 30 seconds (flag value)
* `[popup]`: Won't trigger popup based on time since default value is `false`

#### `show-after-scroll`

Controls whether the popup shows after the user has scrolled a certain percentage of the page.

*Format:* `show-after-scroll=<percentage|false>`

*Flag value:* `80` (percent)

*Default value:* `false`

*Note:* This parameter expects a numeric value without units, representing percentage (0-100).

*Examples:*

* `[popup show-after-scroll="50"]`: Triggers popup after scrolling 50% of the page
* `[popup show-after-scroll]`: Triggers popup after scrolling 80% of the page (flag value)
* `[popup]`: Won't trigger popup based on scrolling since default value is `false`

### Identification and Styling Parameters

#### `id`

Sets a custom ID attribute for the popup's wrapper div.

*Format:* `id=<string|false>`

*Flag value:* None (must provide a value)

*Default value:* Automatically generated ID

*Examples:*

* `[popup id="newsletter-popup"]`: Sets the popup ID to "newsletter-popup"
* `[popup]`: Assigns an automatically generated ID

#### `class`

Adds custom CSS classes to the popup element.

*Format:* `class=<string>`

*Flag value:* None (must provide a value)

*Default value:* None (no additional classes)

*Examples:*

* `[popup class="custom-theme large-popup"]`: Adds "custom-theme" and "large-popup" classes
* `[popup]`: No additional CSS classes

#### `style-overlay`

Adds inline CSS for the overlay element.

*Format:* `style-overlay=<css-string>`

*Flag value:* None (must provide a value)

*Default value:* None (no inline styles)

*Examples:*

* `[popup style-overlay="backdrop-filter: blur(5px);"]`: Adds a blur effect to the overlay
* `[popup]`: No additional inline styles for the overlay

#### `style-dialog`

Adds inline CSS for the dialog element.

*Format:* `style-dialog=<css-string>`

*Flag value:* None (must provide a value)

*Default value:* None (no inline styles)

*Examples:*

* `[popup style-dialog="box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);"]`: Adds shadow
* `[popup]`: No additional inline styles for the dialog

#### `style-close-button`

Adds inline CSS for the close button element.

*Format:* `style-close-button=<css-string>`

*Flag value:* None (must provide a value)

*Default value:* None (no inline styles)

*Examples:*

* `[popup style-close-button="font-size: 24px; color: #ff0000;"]`: Creates a larger, red close button
* `[popup style-close-button="background-color: #333; border-radius: 50%; color: white;"]`: Creates a dark circular close button
* `[popup]`: No additional inline styles for the close button

#### `style-content`

Adds inline CSS for the popup content area.

*Format:* `style-content=<css-string>`

*Flag value:* None (must provide a value)

*Default value:* None (no inline styles)

*Examples:*

* `[popup style-content="color: #333; font-size: 16px;"]`: Styles text in the popup content
* `[popup style-content="background-color: #f9f9f9; padding: 10px;"]`: Adds background and padding to content area
* `[popup]`: No additional inline styles for the content area

### Layout and Positioning Parameters

#### `position`

Determines the popup position on the screen.

*Format:* `position=<position-value>`

*Flag value:* None (must provide a value)

*Default value:* `center`

*Examples:*

* `[popup position="top"]`: Positions the popup at the top center
* `[popup position="bottom-right"]`: Positions the popup at the bottom right
* `[popup]`: Centers the popup (default position)

Valid position values: `center`, `top`, `top-right`, `right`, `bottom-right`, `bottom`, `bottom-left`, `left`, `top-left`

#### `width`

Sets the desired width of the popup.

*Format:* `width=<css-length>`

*Flag value:* None (must provide a value)

*Default value:* `clamp(300px, 90vw, 800px)`

*Examples:*

* `[popup width="500px"]`: Sets popup width to 500 pixels
* `[popup width="50%"]`: Sets popup width to 50% of viewport width
* `[popup]`: Uses the default responsive width

#### `max-height`

Sets the maximum height of the popup before scrolling is activated.

*Format:* `max-height=<css-length>`

*Flag value:* None (must provide a value)

*Default value:* `95vh`

*Examples:*

* `[popup max-height="80vh"]`: Sets maximum height to 80% of viewport height
* `[popup max-height="600px"]`: Sets maximum height to 600 pixels
* `[popup]`: Uses the default maximum height (95% of viewport)

#### `padding`

Sets the internal padding of the popup.

*Format:* `padding=<css-length>`

*Flag value:* None (must provide a value)

*Default value:* `clamp(20px, calc(5.2vw - 20px), 160px)`

*Examples:*

* `[popup padding="30px"]`: Sets padding to 30 pixels on all sides
* `[popup padding="20px 40px"]`: Sets vertical padding to 20px and horizontal to 40px
* `[popup]`: Uses the default responsive padding

#### `overlay-color`

Sets the color of the overlay behind the popup.

*Format:* `overlay-color=<css-color>`

*Flag value:* None (must provide a value)

*Default value:* `rgba(0,0,0,80%)`

*Examples:*

* `[popup overlay-color="rgba(0,0,50,70%)"]`: Sets a semi-transparent dark blue overlay
* `[popup overlay-color="#000000cc"]`: Sets a semi-transparent black overlay
* `[popup]`: Uses the default semi-transparent black overlay

### Interaction Parameters

#### `close-button`

Displays a close button with the specified character.

*Format:* `close-button=<character|false>`

*Flag value:* `✖`

*Default value:* `false`

*Examples:*

* `[popup close-button="×"]`: Shows a close button with the × character
* `[popup close-button]`: Shows a close button with the default ✖ character
* `[popup]`: No close button is displayed

#### `close-outside-click`

Determines whether clicking outside the popup area closes it.

*Format:* `close-outside-click=<true|false>`

*Flag value:* `true`

*Default value:* `false`

*Examples:*

* `[popup close-outside-click="true"]`: Clicking outside the popup closes it
* `[popup close-outside-click]`: Same as above (using flag value)
* `[popup]`: Clicking outside doesn't close the popup

#### `close-esc-key`

Determines whether pressing the ESC key closes the popup.

*Format:* `close-esc-key=<true|false>`

*Flag value:* `true`

*Default value:* `false`

*Examples:*

* `[popup close-esc-key="true"]`: Popup closes when the ESC key is pressed
* `[popup close-esc-key]`: Same as above (using flag value)
* `[popup]`: Popup does not close when the ESC key is pressed

#### `modal`

Controls whether the popup behaves as a modal dialog.

*Format:* `modal=<true|false>`

*Flag value:* `true`

*Default value:* `false`

*Examples:*

* `[popup modal="true"]`: Creates a modal popup (focus trapped, background scrolling prevented, overlay added)
* `[popup modal]`: Same as above (using flag value)
* `[popup]`: Creates a non-modal popup (focus not trapped, background scrolling not prevented, overlay not added)

#### `reappear-delay`

Controls how long before the popup can appear again after being closed.

*Format:* `reappear-delay=<time-value>`

*Flag value:* `1d` (1 day)

*Default value:* `0` (no delay)

*Note:* This parameter is converted to seconds internally. For example, "30s" becomes 30 seconds, "2h" becomes 7,200 seconds, etc.

*Examples:*

* `[popup reappear-delay="4h"]`: Popup won't reappear for 4 hours after being closed
* `[popup reappear-delay="30s"]`: Popup won't reappear for 30 seconds after being closed
* `[popup reappear-delay]`: Popup won't reappear for 1 day after being closed (flag value)
* `[popup]`: Popup can reappear immediately after being closed

Time values can use these units:

- No unit or `s`: seconds (e.g., `30` or `30s`)
- `m`: minutes (e.g., `5m`)
- `h`: hours (e.g., `2h`)
- `d`: days (e.g., `1d`)

### Animation Parameters

#### `open-animation`

Sets the animation used when the popup appears.

*Format:* `open-animation=<animation-name|false>`

*Flag value:* `tada`

*Default value:* `false` (no animation)

*Examples:*

* `[popup open-animation="fade-in"]`: Popup fades in when appearing
* `[popup open-animation="slide-in-top"]`: Popup slides in from the top
* `[popup open-animation]`: Uses the tada animation (flag value)
* `[popup]`: No animation when popup appears

Valid open animation names: `tada`, `fade-in`, `fade-in-top`, `fade-in-right`, `fade-in-bottom`, `fade-in-left`, `slide-in-top`, `slide-in-right`, `slide-in-bottom`, `slide-in-left`

#### `close-animation`

Sets the animation used when the popup closes.

*Format:* `close-animation=<animation-name|false>`

*Flag value:* `fade-out`

*Default value:* `false` (no animation)

*Examples:*

* `[popup close-animation="fade-out-top"]`: Popup fades out toward the top when closing
* `[popup close-animation="slide-out-bottom"]`: Popup slides out to the bottom
* `[popup close-animation]`: Popup fades out (flag value)
* `[popup]`: No animation when popup closes

Valid close animation names: `fade-out`, `fade-out-top`, `fade-out-right`, `fade-out-bottom`, `fade-out-left`, `slide-out-top`, `slide-out-right`, `slide-out-bottom`, `slide-out-left`

#### `open-animation-duration`

Overrides the default duration of the open animation.

*Format:* `open-animation-duration=<milliseconds|false>`

*Flag value:* None (must provide a value)

*Default value:* Animation's default duration

*Note:* This parameter expects a numeric value representing milliseconds.

*Examples:*

* `[popup open-animation="fade-in" open-animation-duration="500"]`: Fade in animation lasts 500ms
* `[popup open-animation="slide-in-top"]`: Uses the default duration for slide-in-top

#### `close-animation-duration`

Overrides the default duration of the close animation.

*Format:* `close-animation-duration=<milliseconds|false>`

*Flag value:* None (must provide a value)

*Default value:* Animation's default duration

*Note:* This parameter expects a numeric value representing milliseconds.

*Examples:*

* `[popup close-animation="fade-out" close-animation-duration="300"]`: Fade out animation lasts 300ms
* `[popup close-animation="slide-out-bottom"]`: Uses the default duration for slide-out-bottom

### Accessibility Parameters

#### `aria-label-popup`

Sets the ARIA label for the popup element.

*Format:* `aria-label-popup=<string>`

*Flag value:* None (must provide a value)

*Default value:* `"Popup"` (localized if translations are available)

*Examples:*

* `[popup aria-label-popup="Newsletter Signup"]`: Sets a custom ARIA label
* `[popup]`: Uses the default "Popup" label (translated if available)

#### `aria-label-close`

Sets the ARIA label for the close button.

*Format:* `aria-label-close=<string>`

*Flag value:* None (must provide a value)

*Default value:* `"Close popup"` (localized if translations are available)

*Examples:*

* `[popup aria-label-close="Dismiss newsletter"]`: Sets a custom ARIA label for close button
* `[popup]`: Uses the default "Close popup" label (translated if available)

### Combining Parameters

```
[popup show-on-exit-intent show-after-time="15" show-after-scroll="60" 
       position="center" width="600px" overlay-color="rgba(0,0,50,80%)"
       close-button modal close-esc-key open-animation="fade-in" close-animation="fade-out"]
<h2>Subscribe to Our Newsletter</h2>
<p>Get the latest updates and special offers delivered directly to your inbox.</p>
[contact-form-7 id="contact-form-12345" title="Newsletter Signup Form"]
[/popup]
```

In this example, the popup will appear when the user tries to leave the page, OR after 15 seconds, OR after scrolling 60% of the page - whichever happens first. It will be centered with a width of 600px, have a dark blue overlay, display a close button, allow ESC key closing, prevent background scrolling, and use fade animations.

### Examples

Show a popup after 5 seconds with a close button:

```
[popup show-after-time="5" close-button]
<h2>Welcome to our site!</h2>
<p>Check out our latest offers.</p>
[/popup]
```

Show a popup on exit intent with custom width and position:

```
[popup show-on-exit-intent width="400px" position="top"]
<h2>Wait before you go!</h2>
<p>Subscribe to our newsletter to get exclusive content.</p>
[/popup]
```

Show a popup after scrolling 50% with fade-in animation:

```
[popup show-after-scroll="50" open-animation="fade-in" close-outside-click]
<h2>You're halfway there!</h2>
<p>Want to learn more about our products?</p>
[/popup]
```

Show a popup containing a contact form (using Contact Form 7 shortcode as an example):

```
[popup show-after-time="10" close-button position="center" width="600px"]
<h2>Contact Us!</h2>
<p>Have a question? Fill out the form below:</p>
[contact-form-7 id="your-form-id" title="Popup Contact Form"]
[/popup]
```

## Developer Hooks

The plugin provides a few hooks for developers to customize the popup behavior:

### WordPress Filters:

#### `kntnt-popup-shortcode-atts`

Modifies shortcode attributes before they're processed.

```php
add_filter('kntnt-popup-shortcode-atts', function($attributes) {
    // Modify attributes based on conditions
    if (is_front_page()) {
        $attributes['show-after-time'] = '5';
    }
    return $attributes;
});
```

#### `kntnt-popup-content`

Filters the popup content before it's displayed.

```php
add_filter('kntnt-popup-content', function($content, $popup_id) {
    // Personalize content for logged-in users
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $content = str_replace('{user_name}', $user->display_name, $content);
    }
    return $content;
}, 10, 2);
```

#### `kntnt-popup-armed`

Determines whether a popup should be shown at all.

```php
add_filter('kntnt-popup-armed', function($armed) {
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

#### `kntnt-popup-shortcode`

Filters the entire popup HTML output.

```php
add_filter('kntnt-popup-shortcode', function($output, $atts, $content) {
    // Modify the final HTML
    // For example, add tracking attributes
    $output = str_replace('<div id="', '<div data-tracking="popup" id="', $output);
    return $output;
}, 10, 3);
```

### JavaScript Events:

Event listeners should be added before the popup is initialized:

#### `kntnt_popup:before_open`

Triggered before a popup is opened.

```javascript
document.addEventListener('kntnt_popup:before_open', function (event) {
  // Pause videos when popup opens
  document.querySelectorAll('video').forEach(function (video) {
    video.pause();
  });
});
```

#### `kntnt_popup:after_close`

Triggered after a popup is closed.

```javascript
document.addEventListener('kntnt_popup:after_close', function (event) {
  // Resume background content after popup closes
  document.querySelectorAll('.background-video').forEach(function (video) {
    video.play();
  });
});
```

## Programmatic Usage with `do_shortcode()`

While Kntnt Popup typically detects the `[popup]` shortcode within your post content to load necessary assets (CSS and JavaScript), there might be cases where you want to render a popup programmatically using the WordPress `do_shortcode()` function, for instance, by hooking into a theme action.

The plugin is designed to avoid loading assets if the shortcode is not detected in the page's main content. If the shortcode is added later in the WordPress loading sequence using `do_shortcode()` (e.g., after the `<head>` section of your page has already been generated), it might be too late for the plugin to enqueue its CSS files in the optimal place. This could result in the popup appearing unstyled initially.

To ensure assets are loaded correctly when using `do_shortcode()` programmatically, you need to call the `mark_assets_as_needed()` method on the plugin's asset manager instance to manually inform the plugin that its assets will be required on the page. This should be done early enough in the WordPress lifecycle, typically during the `wp_enqueue_scripts` action, before the plugin's own asset loading logic runs.

Example:

```php
add_action( 'wp_enqueue_scripts', function () {
  if ( is_plugin_active( 'kntnt-popup/kntnt-popup.php' ) ) {
	  \Kntnt\Popup\Plugin::get_instance()->get_assets_manager()->mark_assets_as_needed();
  }
} );

add_action( 'wp_body_open', function () {
  if ( is_plugin_active( 'kntnt-popup/kntnt-popup.php' ) ) {
    echo do_shortcode( '[popup]Hello world![/popup]' );
  }
} );
```

## Building from Source (for Developers)

If you have downloaded the plugin source code directly from GitHub, you'll need to install dependencies and build the plugin for distribution.

### Development Setup

For development work, you need Node.js installed on your system, which includes npm (Node Package Manager). If you don't have it installed, download and install the LTS (Long Term Support) version from the official website:

* [https://nodejs.org/](https://nodejs.org/)

You can verify the installation by opening your terminal or command prompt and running:

```bash
node -v
npm -v
```

### Install Dependencies

Navigate to the plugin's root directory (`kntnt-popup/`) in your terminal and run:

```bash
npm install
```

This command reads the package.json file and downloads the required dependencies into a node_modules folder.

### Development Build

For development purposes (to get the MicroModal.js dependency), run:

```bash
npm run dev-build
```

This copies the necessary JavaScript file (`micromodal.min.js`) from `node_modules` to the correct location within the plugin's `vendor/micromodal/` directory.

### Production Build

To create a production-ready version of the plugin:

```bash
npm run build
```

This command:

1. Cleans the `dist` directory
2. Copies all necessary plugin files to `dist/kntnt-popup/`
3. Minifies and optimizes the CSS file using cssnano
4. Minifies and optimizes the JavaScript file using terser
5. Creates a `dist/kntnt-popup.zip` file ready for distribution

The production build creates optimized files that are smaller and faster to load, making them ideal for production environments.

### Build Output

After running `npm run build`, you'll find:

- `dist/kntnt-popup/` - The complete plugin directory with optimized files
- `dist/kntnt-popup.zip` - A ZIP archive ready for WordPress installation

### Updating Dependencies

To check if there are newer versions of the dependencies:

```bash
npm outdated
```

To update dependencies:

```bash
npm update
```

**Important:** After updating dependencies, you must run the appropriate build command again:

- For development: `npm run dev-build`
- For production: `npm run build`

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

## 1.1.1 (2025-05-24)

- enhance: add modern build system with npm scripts for development and production
- enhance: implement CSS minification using cssnano for optimized file sizes
- enhance: implement JavaScript minification using terser for better performance
- enhance: add automated ZIP file generation for easy distribution
- enhance: reorganize build scripts into dedicated `scripts/` directory for better project structure
- enhance: move third-party dependencies to `vendor/` directory following WordPress best practices
- enhance: improve dependency management with separate dev and production workflows
- improve: restructure development workflow with separate dev and production builds
- improve: add comprehensive build documentation for developers
- improve: switch from zip-a-folder to archiver for more reliable ZIP generation

## 1.1.0 (2025-05-23)

- feature: add new `close-esc-key` parameter to control whether ESC key closes the popup
- enhance: improve documentation with better examples and clearer usage instructions
- enhance: add acknowledgment of Micromodal.js library
- improve: reorganize README structure for better navigation

## 1.0.2 (2025-05-23)

- fix: resolve JavaScript initialization timing issue that prevented popups from working
- fix: move script enqueueing to wp_footer to ensure popup configurations are properly passed to JavaScript
- improve: split asset loading into CSS (early) and JavaScript (late) phases for better performance

## 1.0.1 (2025-05-23)

- fix: show-after-time=0 works
- fix: functions works in css
- fix: correct aria is used

## 1.0.0 (2025-05-22)

- Initial release
- Added shortcode functionality with comprehensive parameter options
- Implemented multiple trigger mechanisms (exit intent, time delay, scroll position)
- Added customizable positioning and styling options
- Included animation options for opening and closing popups
- Integrated Micromodal.js for lightweight modal functionality
