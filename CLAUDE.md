# WordPress Development - Compact Instructions

## Core Rules (NON-NEGOTIABLE)

### Security (ALWAYS)
- Sanitize all input: `sanitize_text_field()`, `sanitize_email()`, `intval()`, `wp_kses_post()`
- Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_json_encode()`
- Use nonces for forms/AJAX: `wp_nonce_field()` + `wp_verify_nonce()`
- Check capabilities: `current_user_can()` before admin operations
- Prepared statements: `$wpdb->prepare()` for ALL database queries

### Code Structure
- Namespace everything: `namespace PluginName\Admin;`
- PSR-4 autoload via composer.json
- Prefix hooks: `my_plugin_action_name` (never unprefixed)
- Function names: snake_case
- Class names: PascalCase in namespaces
- File: `<?php` opener + `if ( ! defined( 'ABSPATH' ) ) exit;`

### PHP Standard
- Minimum PHP 8.0
- Type hints on functions: `public function get_item( int $id ): array`
- PHPDoc on all classes/public methods
- Follow WordPress Coding Standards

## Plugin Structure
```
plugin-name/
├── plugin-name.php (main file with header)
├── composer.json (PSR-4 autoload)
├── /src
│   ├── class-plugin.php (bootstrap)
│   ├── Admin/ (admin classes)
│   ├── API/ (REST controllers)
│   ├── Database/ (DB operations)
│   └── Integration/ (third-party APIs)
├── /tests (unit tests)
├── /assets (CSS/JS)
├── README.md (setup instructions)
└── CHANGELOG.md (version history)
```

## Theme Structure
```
theme-name/
├── style.css (theme header)
├── functions.php (setup/hooks)
├── /src (PHP classes)
├── /templates (page.php, single.php, etc.)
├── /blocks (Gutenberg blocks)
└── /assets (CSS/JS)
```

## REST API Pattern
```php
class REST_Controller extends \WP_REST_Controller {
    protected $namespace = 'plugin/v1';
    protected $rest_base = 'items';

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [ 'methods' => 'GET', 'callback' => [ $this, 'get_items' ], 'permission_callback' => fn() => current_user_can( 'read' ) ],
            [ 'methods' => 'POST', 'callback' => [ $this, 'create_item' ], 'permission_callback' => fn() => current_user_can( 'edit_posts' ) ],
        ]);
    }

    public function get_items( $request ) {
        global $wpdb;
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}items LIMIT %d", 10 ) );
        return rest_ensure_response( $items );
    }
}
```

## Common Patterns

### Sanitize & Store
```php
$title = sanitize_text_field( $_POST['title'] ?? '' );
$content = wp_kses_post( $_POST['content'] ?? '' );
update_option( 'my_plugin_option', $title );
```

### Database Query
```php
global $wpdb;
$results = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}items WHERE user_id = %d",
    $user_id
) );
```

### Nonce Verification
```php
if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'action' ) ) {
    wp_die( 'Security check failed' );
}
```

### Custom Post Type
```php
register_post_type( 'item', [
    'public' => true,
    'show_in_rest' => true,
    'supports' => [ 'title', 'editor' ],
] );
```

## Delivery Checklist
- [ ] Input sanitized, output escaped
- [ ] Nonces on forms/AJAX
- [ ] Capability checks on admin ops
- [ ] Prepared statements for DB
- [ ] PHPDoc on classes/public methods
- [ ] Unit tests (70% coverage minimum)
- [ ] README.md (setup instructions)
- [ ] CHANGELOG.md
- [ ] composer.json
- [ ] No hardcoded credentials
- [ ] Follows WPCS
- [ ] Error handling & logging

## How to Request

```
PROJECT: Plugin/Theme Name
TYPE: [Plugin|Theme|Both]

REQUIREMENTS:
- Feature 1
- Feature 2

DATABASE:
- table_name (fields)

REST ENDPOINTS:
- GET /items
- POST /items/{id}

NOTES: Any specific constraints
```

## Key Links
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Security](https://developer.wordpress.org/plugins/security/)
- [REST API](https://developer.wordpress.org/rest-api/)

---

**That's it.** Follow these rules and deliver working, secure, production-ready code.
