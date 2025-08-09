<?php
/**
 * Plugin Name: WP Intl Calendar
 * Plugin URI: https://github.com/qasedak/WP-Intl-Calender/
 * Description: Converts WordPress dates and times to all other calendars available in JS Intl method
 * Version: 1.0.8
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Mohammad Anbarestany
 * Author URI: https://anbarestany.ir/
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: wp-intl-calendar
 * Domain Path: /languages
 *
 * @package WP_Intl_Calendar
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Loads the plugin's text domain for internationalization.
 *
 * @since 1.04
 * @return void
 */
function intlCalen_load_textdomain() {
    load_plugin_textdomain(
        'wp-intl-calendar',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'intlCalen_load_textdomain');

// Include settings file
require_once plugin_dir_path(__FILE__) . 'settings.php';

/**
 * Gets calendar formatting options based on locale.
 *
 * @since 1.04
 * @param string $locale The locale identifier (e.g., 'en-US', 'fa-IR')
 * @return array Calendar formatting options for Intl.DateTimeFormat
 */
function get_calendar_options($locale) {
    // Base options from settings
    $options = [
        'year' => get_option('intlCalen_year_format', '2-digit'),
        'month' => get_option('intlCalen_month_format', 'numeric'),
        'day' => get_option('intlCalen_day_format', 'numeric'),
        'weekday' => get_option('intlCalen_weekday_format', 'short'),
        'hour' => get_option('intlCalen_hour_format', 'numeric'),
        'minute' => get_option('intlCalen_minute_format', 'numeric'),
        'timeZoneName' => get_option('intlCalen_timeZoneName_format', 'short'),
        'timeZone' => get_option('intlCalen_timeZone_format', get_option('timezone_string')),
        'hour12' => filter_var(get_option('intlCalen_hour12_format', 'false'), FILTER_VALIDATE_BOOLEAN),
    ];

    // Map language codes to their corresponding calendar systems
    // This mapping determines which calendar to use based on the locale's language code
    $calendar_mapping = [
        'fa' => 'persian',   // Persian/Farsi
        'ar' => 'islamic',   // Arabic countries
        'th' => 'buddhist',  // Thai
        'ja' => 'japanese',  // Japanese
        'zh' => 'chinese',   // Chinese
        'en' => 'gregory'    // English (Gregorian calendar)
    ];

    // Extract the language code from the full locale (e.g., 'en' from 'en-US')
    $lang = substr($locale, 0, 2);
    
    // Apply the calendar system if a mapping exists for this language
    if (isset($calendar_mapping[$lang])) {
        $options['calendar'] = $calendar_mapping[$lang];
    }

    return $options;
}

/**
 * Main function to initialize calendar conversion on frontend.
 * Handles locale detection and JavaScript initialization.
 *
 * @since 1.0
 * @return void
 */
function intlCalen()
{
    // Get locale setting
    $locale_setting = get_option('intlCalen_locale', 'auto');
    
    // Initialize locale variable
    $locale = 'en-US'; // fallback default
    $browser_default = false;
    $display_lang = null;
    // Determine locale and display language based on settings
    switch ($locale_setting) {
        case 'browser':
            $browser_default = true;
            $display_lang = get_option('intlCalen_display_language', 'wordpress') === 'wordpress'
                ? str_replace('_', '-', (is_admin() ? get_user_locale() : get_locale()))
                : '${navigator.language}';
            break;
        case 'auto':
            $locale = str_replace('_', '-', (is_admin() ? get_user_locale() : get_locale()));
            $display_lang = get_option('intlCalen_display_language', 'wordpress') === 'wordpress'
                ? str_replace('_', '-', (is_admin() ? get_user_locale() : get_locale()))
                : $locale;
            $browser_default = false;
            break;
        default:
            $locale = $locale_setting;
            // Determine display language based on setting
            $display_lang = get_option('intlCalen_display_language', 'wordpress') === 'wordpress'
                ? str_replace('_', '-', (is_admin() ? get_user_locale() : get_locale()))
                : $locale_setting;
            $browser_default = false;
    }

    // Get and sanitize calendar options
    $calendar_options = get_calendar_options($locale);
    foreach ($calendar_options as $option_key => $option_value) {
        if ($option_value === '' || $option_value === null) {
            unset($calendar_options[$option_key]);
        }
    }
    // Ensure hour12 is boolean
    if (isset($calendar_options['hour12'])) {
        $calendar_options['hour12'] = (bool) $calendar_options['hour12'];
    }
    $options_js = json_encode($calendar_options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Build the selector string based on settings
    $selectors = [];
    
    // Add auto-detect class if enabled
    if (get_option('intlCalen_auto_detect', 0)) {
        $selectors[] = '.wp-intl-date';
    }
    
    // Add custom selectors if not empty
    $custom_selector = get_option('intlCalen_date_selector', '.date, time');
    if (!empty($custom_selector)) {
        $selectors[] = $custom_selector;
    }
    // Ensure admin list table dates and media 'uploaded' dates are included like old behavior
    if (is_admin()) {
        $selectors[] = '.date';
        $selectors[] = '.uploaded';
    }
    
    // Combine selectors with comma
    $final_selector = implode(', ', array_filter($selectors));
    if (trim($final_selector) === '') {
        $final_selector = is_admin() ? '.date' : '.date, time';
    }
    
    ?>
    <script type="text/javascript">
    // Initialize date formatter configuration object
    const options = <?php echo $options_js ? $options_js : '{}'; ?>;
    const __isAdmin = <?php echo is_admin() ? 'true' : 'false'; ?>;
    
    try {
        // Handle locale selection and browser detection
        let localeToUse;
        
        if (<?php echo $browser_default ? 'true' : 'false'; ?>) {
            const browserFormatter = new Intl.DateTimeFormat(navigator.language);
            const resolvedOptions = browserFormatter.resolvedOptions();
            
            if (options.calendar) {
                delete options.calendar;
            }
            
            localeToUse = `<?php echo esc_js($display_lang); ?>-u-ca-${resolvedOptions.calendar}`;
        } else {
            localeToUse = "<?php echo esc_js($display_lang); ?>";
        }
        
        // Create the date formatter with final locale and options
        const formatter = new Intl.DateTimeFormat(localeToUse, options);
        
        // Helpers for localized admin strings
        function toAsciiDigits(input) {
            if (!input) return '';
            const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
            const arabicDigits = '٠١٢٣٤٥٦٧٨٩';
            let output = '';
            for (const ch of String(input)) {
                const pIndex = persianDigits.indexOf(ch);
                if (pIndex !== -1) { output += String(pIndex); continue; }
                const aIndex = arabicDigits.indexOf(ch);
                if (aIndex !== -1) { output += String(aIndex); continue; }
                output += ch;
            }
            return output;
        }

        function parseDateFromAdminCell(html) {
            // Split status and date part by <br>
            const tmpSplit = String(html).split(/<br\s*\/?/i);
            if (tmpSplit.length === 0) return { date: null, prefix: null };
            const prefix = tmpSplit[0];
            const datePartHtml = tmpSplit.slice(1).join(' ').trim();
            // Strip tags from date part and normalize digits
            const raw = toAsciiDigits(datePartHtml.replace(/<[^>]*>/g, ' ').trim());
            // Detect AM/PM tokens in multiple languages
            const pmTokens = [/\bpm\b/i, /ب\.ظ/i, /بعد\s*از\s*ظهر/i];
            const amTokens = [/\bam\b/i, /ق\.ظ/i, /قبل\s*از\s*ظهر/i];
            const isPM = pmTokens.some((rx) => rx.test(raw));
            const isAM = amTokens.some((rx) => rx.test(raw));
            // Extract Y/M/D and H:MM (tolerant to separators and words like 'at'/'در')
            const match = raw.match(/(\d{3,4})[\/\-.](\d{1,2})[\/\-.](\d{1,2}).*?(\d{1,2}):(\d{2})/);
            if (!match) return { date: null, prefix };
            let [, yStr, mStr, dStr, hStr, minStr] = match;
            let year = parseInt(yStr, 10);
            const month = Math.max(1, Math.min(12, parseInt(mStr, 10))) - 1;
            const day = Math.max(1, Math.min(31, parseInt(dStr, 10)));
            let hour = parseInt(hStr, 10);
            const minute = parseInt(minStr, 10);
            if (isPM && hour < 12) hour += 12;
            if (isAM && hour === 12) hour = 0;
            const date = __isAdmin ? new Date(Date.UTC(year, month, day, hour, minute)) : new Date(year, month, day, hour, minute);
            if (isNaN(date)) return { date: null, prefix };
            return { date, prefix };
        }

        // Helpers for media 'uploaded' block parsing
        function escapeRegExp(str) {
            return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        const monthNameToIndex = (function() {
            const map = new Map();
            const add = (names, idx) => names.forEach(n => map.set(n.toLowerCase(), idx));
            // Persian Gregorian month names
            add(['ژانویه'], 0);
            add(['فوریه'], 1);
            add(['مارس'], 2);
            add(['آوریل','ابریل'], 3);
            add(['مه'], 4);
            add(['ژوئن'], 5);
            add(['ژوئیه','ژوييه'], 6);
            add(['اوت'], 7);
            add(['سپتامبر'], 8);
            add(['اکتبر','اكتوبر'], 9);
            add(['نوامبر'], 10);
            add(['دسامبر'], 11);
            // Arabic month names
            add(['يناير'], 0);
            add(['فبراير','فيفري'], 1);
            add(['مارس'], 2);
            add(['أبريل','ابريل'], 3);
            add(['مايو'], 4);
            add(['يونيو'], 5);
            add(['يوليو'], 6);
            add(['أغسطس','اغسطس'], 7);
            add(['سبتمبر'], 8);
            add(['أكتوبر','اكتوبر'], 9);
            add(['نوفمبر'], 10);
            add(['ديسمبر'], 11);
            // English fallback
            add(['January'], 0);
            add(['February'], 1);
            add(['March'], 2);
            add(['April'], 3);
            add(['May'], 4);
            add(['June'], 5);
            add(['July'], 6);
            add(['August'], 7);
            add(['September'], 8);
            add(['October'], 9);
            add(['November'], 10);
            add(['December'], 11);
            return map;
        })();
        const monthPattern = Array.from(monthNameToIndex.keys()).map(escapeRegExp).join('|');
        const monthNameRegex = new RegExp('(' + monthPattern + ')', 'i');

        function parseMediaUploaded(element) {
            const strong = element.querySelector('strong');
            const labelHTML = strong ? strong.outerHTML : '';
            let tailHtml = '';
            if (strong) {
                let node = strong.nextSibling;
                while (node) {
                    tailHtml += node.outerHTML ? node.outerHTML : (node.textContent || '');
                    node = node.nextSibling;
                }
            } else {
                tailHtml = element.innerHTML;
            }
            const rawText = toAsciiDigits(tailHtml.replace(/<[^>]*>/g, ' ').trim());
            if (!rawText) return { date: null, labelHTML };
            const monthMatch = rawText.match(monthNameRegex);
            let year, monthIdx, day;
            if (monthMatch) {
                monthIdx = monthNameToIndex.get(monthMatch[1].toLowerCase());
                // Try patterns: Month D, YYYY or D Month YYYY
                const patternA = new RegExp('(' + monthPattern + ')\\s+(\\d{1,2})(?:,\\s*|\\s+)(\\d{4})', 'i');
                const mA = rawText.match(patternA);
                if (mA) {
                    day = parseInt(mA[2], 10);
                    year = parseInt(mA[3], 10);
                } else {
                    const patternB = new RegExp('(?:^|\\n|\\s)(\\d{1,2})\\s+(' + monthPattern + ')\\s+(\\d{4})', 'i');
                    const mB = rawText.match(patternB);
                    if (mB) {
                        day = parseInt(mB[1], 10);
                        year = parseInt(mB[3], 10);
                        monthIdx = monthNameToIndex.get(mB[2].toLowerCase());
                    }
                }
            }
            if (Number.isFinite(year) && Number.isFinite(monthIdx) && Number.isFinite(day)) {
                // Use noon to avoid timezone date-shift when no time is provided
                const date = __isAdmin ? new Date(Date.UTC(year, monthIdx, day, 12, 0)) : new Date(year, monthIdx, day, 12, 0);
                if (!isNaN(date)) return { date, labelHTML };
            }
            // Fallback: try direct Date parsing
            const d = new Date(rawText);
            if (!isNaN(d)) return { date: d, labelHTML };
            return { date: null, labelHTML };
        }
        
        // Function to convert date for a single element
        function convertDate(element) {
            try {
                if (element.dataset && element.dataset.intlcalenProcessed === '1') return;
                const dataAttr = element.getAttribute('data-date');
                const dateTimeAttr = element.getAttribute('datetime') || element.dateTime;

                let dateToConvert = null;
                let preservePrefix = null;

                if (dataAttr) {
                    const d = new Date(dataAttr);
                    if (!isNaN(d)) dateToConvert = d;
                }

                if (!dateToConvert && dateTimeAttr) {
                    const d = new Date(dateTimeAttr);
                    if (!isNaN(d)) dateToConvert = d;
                }

                // Admin list table case: status<br>date text
                if (!dateToConvert && /<br\s*\/?/i.test(element.innerHTML)) {
                    const parsed = parseDateFromAdminCell(element.innerHTML);
                    if (parsed.date) {
                        dateToConvert = parsed.date;
                        preservePrefix = parsed.prefix;
                    }
                }

                // Media 'uploaded' block in admin (e.g., attachment details)
                if (!dateToConvert && __isAdmin && element.classList && element.classList.contains('uploaded')) {
                    const parsedMedia = parseMediaUploaded(element);
                    if (parsedMedia.date) {
                        dateToConvert = parsedMedia.date;
                        preservePrefix = parsedMedia.labelHTML;
                    }
                }

                // Fallback: try raw textContent
                if (!dateToConvert) {
                    const d = new Date(element.textContent);
                    if (!isNaN(d)) dateToConvert = d;
                }

                if (dateToConvert) {
                    const convertedDate = formatter.format(dateToConvert);
                    if (preservePrefix !== null) {
                        // If preservePrefix looks like a label (<strong>...</strong>), join with a space; if it's a status text, keep <br>
                        if (/^\s*<strong[\s>]/i.test(preservePrefix)) {
                            element.innerHTML = `${preservePrefix} ${convertedDate}`;
                        } else {
                            element.innerHTML = `${preservePrefix}<br>${convertedDate}`;
                        }
                    } else {
                    element.textContent = convertedDate;
                    }
                    if (element.dataset) element.dataset.intlcalenProcessed = '1';
                }
            } catch (error) {
                console.warn('Date conversion failed for:', element, error);
            }
        }

        // Create mutation observer to watch for new dates
        const mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                // Check for added nodes
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        // Check if the added node itself matches the selector
                        if (node.matches("<?php echo esc_js($final_selector); ?>") && (!node.dataset || node.dataset.intlcalenProcessed !== '1')) {
                            <?php if (get_option('intlCalen_lazy_loading', 1) && !is_admin()): ?>
                            observer.observe(node);
                            <?php else: ?>
                            convertDate(node);
                            <?php endif; ?>
                        }
                        
                        // Check for matching elements within the added node
                        const newDates = node.querySelectorAll("<?php echo esc_js($final_selector); ?>");
                        newDates.forEach(element => {
                            if (element.dataset && element.dataset.intlcalenProcessed === '1') return;
                            <?php if (get_option('intlCalen_lazy_loading', 1) && !is_admin()): ?>
                            observer.observe(element);
                            <?php else: ?>
                            convertDate(element);
                            <?php endif; ?>
                        });
                    }
                });
            });
        });

        // Configure the observer to watch for changes in the DOM
        mutationObserver.observe(document.body, {
            childList: true,
            subtree: true
        });

        <?php if (get_option('intlCalen_lazy_loading', 1) && !is_admin()): ?>
        // Create intersection observer for lazy loading
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    convertDate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '50px'
        });

        // Observe initial date elements
        document.querySelectorAll("<?php echo esc_js($final_selector); ?>")
            .forEach(element => { if (!element.dataset || element.dataset.intlcalenProcessed !== '1') observer.observe(element); });
        <?php else: ?>
        // Convert all initial dates immediately
        document.querySelectorAll("<?php echo esc_js($final_selector); ?>")
            .forEach(el => { if (!el.dataset || el.dataset.intlcalenProcessed !== '1') convertDate(el); });
        // Extra pass for admin list tables to mirror old intlCalenDashboard behavior
        if (__isAdmin) {
            const adminNodes = document.querySelectorAll('td.date, td.column-date, td.date.column-date, div.uploaded');
            adminNodes.forEach(el => { if (!el.dataset || el.dataset.intlcalenProcessed !== '1') convertDate(el); });
        }
        <?php endif; ?>
        
        // Stop observing when user leaves page
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                mutationObserver.disconnect();
            } else {
                // Reconnect when page is visible again
                mutationObserver.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        });
            
    } catch (error) {
        console.error('Calendar initialization failed:', error);
    }
    </script>
    <?php
}

/**
 * Admin footer loader to reuse intlCalen() in dashboard when enabled.
 */
function intlCalen_admin_footer_loader() {
    intlCalen();
}

/**
 * Cache manager for date conversions
 */
class IntlCalen_Cache {
    private static $cache_group = 'intlcal_dates';
    private static $cache_expiration = 3600; // 1 hour

    /**
     * Get cached date HTML
     */
    public static function get_cached_date($key) {
        return wp_cache_get($key, self::$cache_group);
    }

    /**
     * Set date HTML in cache
     */
    public static function set_cached_date($key, $value) {
        wp_cache_set($key, $value, self::$cache_group, self::$cache_expiration);
    }

    /**
     * Generate cache key for date
     */
    public static function generate_key($date, $format, $type = 'post') {
        return "intlcal_{$type}_" . md5($date . $format . get_locale());
    }
}

/**
 * Add class to post dates with caching
 */
function intlCalen_add_date_class($the_date, $format, $post) {
    // Skip caching if disabled
    if (!get_option('intlCalen_enable_caching', 1)) {
        $timestamp = get_post_timestamp($post);
        return sprintf(
            '<span class="wp-intl-date" data-date="%s">%s</span>',
            date('Y-m-d H:i:s', $timestamp),
            $the_date
        );
    }
    
    // Generate cache key
    $cache_key = IntlCalen_Cache::generate_key($the_date, $format, 'post');
    
    // Check cache
    $cached_result = IntlCalen_Cache::get_cached_date($cache_key);
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    // Generate HTML
    $timestamp = get_post_timestamp($post);
    $result = sprintf(
        '<span class="wp-intl-date" data-date="%s">%s</span>',
        date('Y-m-d H:i:s', $timestamp),
        $the_date
    );
    
    // Cache result
    IntlCalen_Cache::set_cached_date($cache_key, $result);
    
    return $result;
}

// Add class to modified dates
function intlCalen_add_modified_date_class($the_modified_date, $format, $post) {
    if (function_exists('get_post_timestamp')) {
        $timestamp = get_post_timestamp($post, 'modified');
    } else {
        $timestamp = (int) get_post_modified_time('U', true, $post);
    }
    return sprintf(
        '<span class="wp-intl-date" data-date="%s">%s</span>',
        date('Y-m-d H:i:s', $timestamp),
        $the_modified_date
    );
}

// Add class to archive dates
function intlCalen_add_archive_date_class($link_html) {
    // Archive links already contain machine-readable dates in their URLs
    return str_replace('<a', '<a class="wp-intl-date"', $link_html);
}

// Add class to comment dates
function intlCalen_add_comment_date_class($date, $format, $comment) {
    $timestamp = strtotime($comment->comment_date);
    return sprintf(
        '<span class="wp-intl-date" data-date="%s">%s</span>',
        date('Y-m-d H:i:s', $timestamp),
        $date
    );
}

/**
 * Determines if date processing should occur
 */
function intlCalen_should_process() {
    // Skip processing if disabled
    if (!get_option('intlCalen_auto_detect', 0)) {
        return false;
    }
    
    // Skip in admin area unless enabled
    if (is_admin() && !get_option('intlCalen_admin_enabled', 0)) {
        return false;
    }
    
    // Skip for feeds
    if (is_feed()) {
        return false;
    }
    
    // Skip for REST API requests
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }
    
    // Skip for bots/crawlers
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $bot_patterns = array(
            'bot', 'crawler', 'spider', 'slurp', 'googlebot',
            'bingbot', 'yandex', 'baidu'
        );
        
        foreach ($bot_patterns as $pattern) {
            if (stripos($_SERVER['HTTP_USER_AGENT'], $pattern) !== false) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Initialize filters based on processing check
 */
function intlCalen_initialize_filters() {
    if (!intlCalen_should_process()) {
        return;
    }
    
    // Add filters only if processing is allowed
    add_filter('get_the_date', 'intlCalen_add_date_class', 10, 3);
    add_filter('get_the_modified_date', 'intlCalen_add_modified_date_class', 10, 3);
    add_filter('get_archives_link', 'intlCalen_add_archive_date_class');
    add_filter('get_comment_date', 'intlCalen_add_comment_date_class', 10, 3);
}
add_action('init', 'intlCalen_initialize_filters');

// Add hooks for the main functionality
add_action('wp_footer', 'intlCalen');
// Use a normal admin footer hook and guard execution with option
add_action('admin_footer', 'intlCalen_admin_footer_loader');