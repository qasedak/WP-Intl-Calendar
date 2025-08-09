# WP Intl Calendar

WP Intl Calendar is a WordPress plugin that allows you to display dates and times in different calendar systems based on the user's locale. It leverages the JavaScript Intl API to provide accurate and localized date formatting.

## Features

- Convert dates to various calendar systems such as Persian, Islamic, Buddhist, Japanese, and Chinese.
- Automatically detect and use the user's locale or set a custom locale.
- Customize date and time formats including year, month, day, weekday, hour, minute, and timezone.
- Support for both 12-hour and 24-hour time formats.
- Specify custom CSS selectors to target elements containing dates for conversion.
- Display the date in desired language (match WordPress language or match with selected language).
- Auto-detect browser language.
- Automatic date detection for posts, comments, and archives.
- Performance optimization options for better site speed.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wp-intl-calendar` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings -> Intl Calendar screen to configure the plugin.

## Usage

1. **Locale Settings**: Choose whether to automatically detect the user's locale or set a specific locale for date conversion.
2. **Date and Time Formats**: Customize the format for year, month, day, weekday, hour, minute, and timezone.
3. **Automatic Date Detection**: Enable this option to automatically detect and convert WordPress date elements (posts, comments, archives).
4. **Custom Date Selector**: Enter CSS selectors for elements containing dates, separated by commas. Default is `.date, time`.

### Performance Options

1. **Lazy Loading**: Only convert dates when they become visible on screen. This improves initial page load performance, especially on pages with many dates.
2. **Date Caching**: Cache converted dates to reduce server load by storing converted dates for reuse.
3. **Admin Area Conversion**: Choose whether to enable date conversion in the WordPress admin area. (Buggy and needs more testing)

## Changelog

### 1.0.9 Beta
- Better date conversion at Wordpress dashbord (WP Classic)

### 1.0.8 Beta
- Dynamic date conversion: Improved handling of dynamically loaded content like AJAX responses and JavaScript insertions.
- Changed versioning style from vX.XX to vX.X.X

### 1.07 Beta
- Added new Automatic Date Detection feature
- Improved support for non-English WordPress installations
- Improved date parsing reliability
- Added performance optimization options:
  - Lazy loading for better frontend performance
  - Date caching to reduce server load
  - Optional admin area conversion (Buggy and needs more testing)
- Improved bot detection to skip processing for search engines
- Enhanced error handling and logging

### 1.06 Beta
- Added support for displaying the date in desired language.
- Improved the code structure.
- Auto-detect browser language.
- Gregorian calendar added to the options.

### 1.05 Alpha
- Added support for custom CSS selectors to target elements containing dates.
- Improved handling of timezone settings to use WordPress default timezone.
- Enhanced boolean handling for 12-hour time format option.
- More organized code.
- Translation ability introduced.

### 1.04 Alpha
- In locale setting, you can now select "Auto" and it will get WordPress locale automatically (better option for multi-language websites).

### 1.03 Alpha
- Dates on the WordPress dashboard post section (Partially).

### 1.0 Alpha
- Initial release with support for converting dates on the front-end.

## Support

For support, please contact me at [m_anbarestany@hotmail.com](mailto:m_anbarestany@hotmail.com).
