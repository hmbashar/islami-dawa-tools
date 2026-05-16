# Badri Members Module

This document explains the **আজীবন বদরী সদস্য/সদস্যা** feature inside the Islami Dawa Tools plugin.

## Admin menu

All Badri member features are organized under the existing plugin menu:

```text
Dashboard → Islami Dawa Tools
```

Important pages:

```text
বদরী সদস্য
বদরী সদস্য সেটিংস
```

Main settings URL example:

```text
/wp-admin/admin.php?page=islami-dawa-tools-badri-settings
```

Member list URL:

```text
/wp-admin/edit.php?post_type=badri_member
```

## Custom post type

The module registers this custom post type:

```text
badri_member
```

When a visitor submits the frontend form, a new `badri_member` post is created with:

```text
post_status = pending
```

Admin must review and publish the member before the member appears in the public grid.

## Shortcodes

### Member submission form

```text
[badri_member_form]
```

### Public member grid

```text
[badri_members_grid]
```

Optional attributes:

```text
[badri_members_grid per_page="12" columns="3"]
```

Pagination is enabled by default for the public grid. This is important for large member lists, for example 100+ published members.

Pagination uses a dedicated query parameter to avoid WordPress page pagination conflicts:

```text
/badri-members/?badri_page=2
```

You can disable pagination only when needed:

```text
[badri_members_grid per_page="12" columns="3" pagination="no"]
```

## Frontend form fields

Default fields include:

- সদস্য/সদস্যার নাম
- পিতা/স্বামীর নাম
- মোবাইল নং
- পেশা
- অনুদান ধরন
- অনুদানের পরিমাণ: অংকে
- কাস্টম অনুদানের পরিমাণ, shown only when `অন্যান্য` is selected
- অনুদানের পরিমাণ: কথায়
- স্থায়ী ঠিকানা
- স্থায়ী জেলা
- বর্তমান ঠিকানা
- বর্তমান জেলা
- সদস্যের ছবি
- ছবি প্রকাশের অনুমতি
- পাবলিক তথ্য প্রকাশের অনুমতি
- Random CAPTCHA
- Extra fields from the form builder

## Premium photo upload UI

The `সদস্যের ছবি` field uses a premium drag/drop uploader.

Supported behavior:

- Click to select image
- Drag and drop image
- Image preview before submit
- File name preview
- JPG, PNG, WEBP only
- Max file size controlled from settings
- Validation errors shown with SweetAlert2

Uploaded image is saved as the Badri member post **Featured Image**.

## Photo and privacy behavior

If the member allows public info and photo display:

- Public grid shows the uploaded photo
- Public grid shows selected public fields

If the member chooses to hide public information:

- Public grid shows only the member name
- Public grid shows first-character avatar instead of photo
- Other information is shown as `xxx`

If the member hides only the photo:

- Public grid shows first-character avatar
- Other allowed public information can still appear

## AJAX and SweetAlert2

The form submits with AJAX when JavaScript is available.

SweetAlert2 is used for:

- Success message
- Required field validation errors
- CAPTCHA errors
- Photo type/size errors
- General submission errors

Fallback behavior:

- If JavaScript fails, normal WordPress `admin-post.php` submission still works.

Default success popup:

```text
Title: ধন্যবাদ!
Message: আপনার তথ্য সফলভাবে জমা হয়েছে। অ্যাডমিন যাচাই করার পর আপনার সাথে যোগাযোগ করা হবে।
```

## Random CAPTCHA

CAPTCHA is not static.

It generates a random math challenge each time the form loads.

Admin can manage:

- Enable/disable CAPTCHA
- Minimum number
- Maximum number
- Operation type: addition, subtraction, mixed
- CAPTCHA label text

The CAPTCHA answer is stored using a secure temporary token and deleted after validation.

## Settings tabs

Go to:

```text
Dashboard → Islami Dawa Tools → বদরী সদস্য সেটিংস
```

Tabs include:

```text
ড্যাশবোর্ড
ফরম
মেসেজ
গ্রিড
ইমেইল
CAPTCHA
ফরম বিল্ডার
```

### Dashboard tab

Shows a modern overview dashboard with:

- Total members
- Published members
- Pending members
- Hidden/private members
- Donation type insights
- Public visibility insights
- Photo/member insights
- Recent applications
- Shortcode reference
- Active settings overview

### Form tab

Controls:

- Form title
- Form description
- Submit button text
- Extra section kicker text
- Extra section title text
- Photo max upload size

### Messages tab

Controls:

- Success title
- Success message
- Error message
- Validation title
- Required field message
- Custom amount error message
- CAPTCHA error message
- Photo type error message
- Photo size error message
- Processing message

### Grid tab

Controls:

- Grid title
- Grid description
- Empty grid message

### Email tab

Controls:

- Admin notification email
- Admin email subject
- Admin email body

### CAPTCHA tab

Controls:

- Enable CAPTCHA
- Min/max random number
- Operation type
- CAPTCHA label

### Form Builder tab

Admins can add extra fields without editing code.

Supported field types:

- Text
- Number
- Email
- Textarea
- Select
- Date

Each extra field supports:

- Field label
- Field key
- Placeholder
- Select options
- Required toggle
- Show in public grid toggle

Extra fields are saved as post meta for the `badri_member` post.

## Admin member edit screen

The Badri member post edit screen includes a styled meta box with grouped sections:

- Personal information
- Donation information
- Address information
- Extra fields
- Privacy/photo settings

The uploaded photo is managed as the WordPress Featured Image.

## Member list page

URL:

```text
/wp-admin/edit.php?post_type=badri_member
```

The list page has a premium dashboard-style UI with:

- Large header
- Quick action buttons
- Stat cards
- Insight cards
- Styled filters
- Styled list table
- Photo/avatar column
- Status badges
- Donation type badges
- Visibility badges

Available filters:

- Status
- Donation type
- Public visibility
- Year
- Month

## Security checklist

Implemented security measures:

- Frontend nonce verification
- AJAX nonce verification
- Admin settings nonce / Settings API handling
- Member meta box nonce verification
- Autosave and revision checks
- Capability checks with `current_user_can()`
- Honeypot anti-spam field
- Random CAPTCHA token validation
- CAPTCHA token deletion after validation
- File upload type validation
- File upload size validation
- Sanitization before saving
- Escaping before output
- Sanitized admin filters

## Main files

Main class:

```text
Inc/BadriMembers.php
```

Frontend assets:

```text
Frontend/Elementor/Assets/badri-members.css
Frontend/Elementor/Assets/badri-members.js
```

Admin assets:

```text
Admin/assets/css/badri-members-admin.css
Admin/assets/js/badri-members-admin.js
```

Templates:

```text
Templates/badri-member-form.php
Templates/badri-members-grid.php
```

## Version history

### 1.1.3

- Updated docs with all current Badri member features.
- Added premium drag/drop UI for `সদস্যের ছবি` field.
- Added frontend image preview and filename preview before submit.
- Drag/drop photo selection works with existing AJAX validation and upload security.

### 1.1.1

- Fixed Badri member list dashboard layout overflow/crossing border issue.
- Rechecked nonce, validation, sanitization, and escaping coverage.

### 1.1.0

- Added premium dashboard-style UI for the Badri member list page.

### 1.0.9

- Polished frontend submit button.
- Fixed form builder checkbox UI.
- Added honeypot field and extra autosave/revision checks.

### 1.0.7

- Added dashboard tab.
- Added random CAPTCHA settings.
- Made extra section heading texts editable.

### 1.0.5

- Added form builder for extra fields.
- Added AJAX + SweetAlert2 improvements.
