# Badri Members Module

This module adds the “আজীবন বদরী সদস্য/সদস্যা” member submission and public listing system.

## Admin menu

All Badri member management is now under the existing plugin menu:

```text
Dashboard → Islami Dawa Tools
```

Submenus:

```text
বদরী সদস্য
বদরী সদস্য সেটিংস
```

The settings URL is similar to:

```text
/wp-admin/admin.php?page=islami-dawa-tools-badri-settings
```

## Shortcodes

### Submission form

```text
[badri_member_form]
```

### Public member grid

```text
[badri_members_grid]
```

Optional:

```text
[badri_members_grid per_page="12" columns="3"]
```

## Form behavior

When a user submits the form:

1. The member is created as a `badri_member` post.
2. Post status is `pending`.
3. Admin reviews the application.
4. Admin publishes the post.
5. The member appears in the public grid.

## Photo behavior

The form includes a member photo upload field.

Uploaded image is stored as the post Featured Image.

If the user allows photo display and public info display, the grid shows the photo.

If the user hides public information, the grid shows only the member name and a first-letter avatar.

## Privacy behavior

If user selects:

```text
আমাকে পাবলিক তালিকায় গোপন রাখুন
```

Then the public grid shows:

- Name
- First letter avatar
- Other fields as `xxx`

If user selects:

```text
আমার তথ্য প্রকাশ করা যাবে
```

Then the public grid shows submitted information after admin publishes it.

## AJAX and SweetAlert2

The form submits with AJAX and shows SweetAlert2 messages.

If JavaScript fails, the normal WordPress admin-post fallback still works.

## Settings

Go to:

```text
Dashboard → Islami Dawa Tools → বদরী সদস্য সেটিংস
```

Admin can manage:

- Form title
- Form description
- Submit button text
- Success message
- Error message
- CAPTCHA error message
- Processing message
- Grid title
- Grid description
- Empty grid message
- Photo max upload size
- Admin notification email
- Admin email subject
- Admin email body

## Files

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
```

Templates:

```text
Templates/badri-member-form.php
Templates/badri-members-grid.php
```
