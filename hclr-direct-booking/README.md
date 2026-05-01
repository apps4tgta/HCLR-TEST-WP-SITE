# HCLR Direct Booking Plugin

WordPress plugin providing OwnerRez API integration, availability calendars, price quotes, and direct booking forms for **Hill Country Lakes Rentals**.

---

## Requirements
- WordPress 6.0+
- PHP 8.0+
- Active OwnerRez account with API access

---

## Installation

1. Upload the `hclr-direct-booking` folder to `/wp-content/plugins/`
2. Activate the plugin in **WordPress Admin → Plugins**
3. Go to **HCLR Booking → Settings**
4. Enter your **OwnerRez email** and **API token**
5. Click **Test Connection** to verify credentials
6. Click **Sync Now** to import properties and availability

---

## Configuration

### Settings Page (`HCLR Booking → Settings`)

| Setting | Description |
|---------|-------------|
| OwnerRez Email | Your OwnerRez account email |
| OwnerRez API Token | From OwnerRez Settings → Advanced Tools → Developer/API |
| Cache Duration | How long API responses are cached (default: 1 hour) |
| Confirmation Page URL | Where to redirect after booking success |
| Min Stay Override | 0 = use OwnerRez property setting |

### Property ID Meta Box

On any WordPress **Page** or **Property** post type, a meta box lets you set the **OwnerRez Property ID**. This links the page to OwnerRez availability and pricing data.

---

## Shortcodes

### `[hclr_availability_calendar]`
Interactive availability calendar with seasonal colors.

```
[hclr_availability_calendar property_id="483733"]
```

Attributes:
- `property_id` (int) — OwnerRez property ID. Defaults to page's `_hclr_property_id` meta.

---

### `[hclr_booking_form]`
Full booking form with price breakdown and guest info.

```
[hclr_booking_form property_id="483733"]
```

Attributes:
- `property_id` (int) — Required.
- `check_in` (YYYY-MM-DD) — Pre-fill check-in date.
- `check_out` (YYYY-MM-DD) — Pre-fill check-out date.

---

### `[hclr_property_list]`
Grid of all properties with photos, rates, and links.

```
[hclr_property_list columns="3" per_page="12"]
```

---

### `[hclr_property_info]`
Output a single property field inline.

```
[hclr_property_info property_id="483733" field="name"]
```

Fields: `name`, `rate`, `bedrooms`, `bathrooms`, `sleeps`, `tagline`, `address`

---

### `[hclr_booking_confirmation]`
Show booking confirmation (place on your confirmation page).

```
[hclr_booking_confirmation]
```

---

## REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/wp-json/hclr/v1/properties` | List all properties |
| GET | `/wp-json/hclr/v1/properties/{id}` | Single property |
| GET | `/wp-json/hclr/v1/availability?property_id=X&check_in=Y&check_out=Z` | Availability |
| POST | `/wp-json/hclr/v1/quote` | Price quote |
| POST | `/wp-json/hclr/v1/booking` | Create booking |
| GET | `/wp-json/hclr/v1/booking/{id}` | Booking status |

---

## Custom Post Type

`hclr_property` — for managing property content alongside OwnerRez data.

---

## Property Meta Keys

| Key | Type | Description |
|-----|------|-------------|
| `_hclr_property_id` | int | OwnerRez property ID |
| `_hclr_nightly_rate` | float | Base nightly rate |
| `_hclr_bedrooms` | int | Bedroom count |
| `_hclr_bathrooms` | float | Bathroom count |
| `_hclr_sleeps` | int | Max occupancy |
| `_hclr_tagline` | string | Short property tagline |
| `_hclr_address` | string | Property address |
| `_hclr_amenities` | JSON | Amenity list |
| `_hclr_gallery_images` | JSON | Gallery image URLs |

---

## Database Tables

- `{prefix}hclr_bookings` — Direct booking records
- `{prefix}hclr_properties_cache` — Cached property data from OwnerRez
- `{prefix}hclr_availability_cache` — Daily availability & rates cache

---

## Changelog

### 1.0.0 (May 2026)
- Initial release
- OwnerRez V2 API integration
- Availability calendar with seasonal color system
- Direct booking form with price quotes
- REST API endpoints
- Background sync (hourly cron)
- Admin dashboard (settings, properties, bookings, sync status)
