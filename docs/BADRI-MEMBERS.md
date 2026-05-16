# Badri Member Feature

This plugin includes a custom post type and frontend shortcodes for the **আজীবন বদরী সদস্য/সদস্যা** system.

## Custom Post Type

Post type slug:

```txt
badri_member
```

Admin menu:

```txt
বদরী সদস্য
```

Frontend submissions are saved as **Pending Review**. Admin must review and publish before the member appears in the public grid.

## Shortcodes

### Member submission form

Add this shortcode to the member application page:

```txt
[badri_member_form]
```

### Public member grid

Add this shortcode to the public members list page:

```txt
[badri_members_grid]
```

Optional attributes:

```txt
[badri_members_grid per_page="12" columns="3"]
```

## Privacy behavior

The form includes a public visibility option:

- `আমার তথ্য প্রকাশ করা যাবে`
- `আমাকে পাবলিক তালিকায় গোপন রাখুন`

If the member chooses to hide information, the public grid shows only the member name. Other fields are displayed as:

```txt
xxx
```

## Fields stored as post meta

```txt
_badri_guardian_name
_badri_mobile
_badri_profession
_badri_donation_frequency
_badri_donation_amount
_badri_donation_amount_text
_badri_permanent_address
_badri_permanent_district
_badri_current_address
_badri_current_district
_badri_public_visibility
```

## Donation amount options

Default options:

```txt
500
1000
2000
5000
10000
other
```

Developers can customize the options with this filter:

```php
add_filter( 'islami_dawa_badri_member_amount_options', function( $options ) {
    return array(
        '1000' => '৳১,০০০',
        '2000' => '৳২,০০০',
        '5000' => '৳৫,০০০',
    );
} );
```

## Styling

CSS file:

```txt
Frontend/Elementor/Assets/badri-members.css
```

Class prefix:

```txt
.at-badri-
```

## Optional page templates

The plugin also registers two page templates:

```txt
Badri Member Form
Badri Members Grid
```

To use them:

1. Go to WordPress Admin > Pages.
2. Create a page for the form, for example: `Badri Member Application`.
3. In Page Attributes > Template, select `Badri Member Form`.
4. Create another page for the public list, for example: `Badri Members`.
5. In Page Attributes > Template, select `Badri Members Grid`.

You can also use shortcodes instead of page templates.
