# ACF Repeater Widget - User Guide

## Overview

The **ACF Repeater Widget** is an Elementor widget that allows you to dynamically display ACF (Advanced Custom Fields) repeater fields on your single posts and pages within Elementor.

## Features

- ✅ Multiple layout options (Table, List, Cards, Custom)
- ✅ Fully customizable styling
- ✅ Responsive design
- ✅ Works with all ACF repeater fields
- ✅ Easy field key configuration
- ✅ No coding required

## Installation & Requirements

### Prerequisites

1. **Elementor** - Page builder plugin
2. **Advanced Custom Fields (ACF)** - Custom fields plugin
3. **Islami Dawa Tools** - This plugin

### Activation

The widget is automatically registered when the plugin is activated and Elementor is available.

## How to Use

### Step 1: Add Widget to Elementor Page

1. Open your post/page in Elementor editor
2. Click **"Add Widget"**
3. Search for **"ACF Repeater Field"**
4. Click to add the widget

### Step 2: Configure the Widget

#### Basic Settings

1. **Repeater Field Key** - Enter your ACF repeater field key/name
   - Example: `expenditure_sector`
   - This is the same as the field name you used in ACF settings

2. **Sub-field Keys (Optional)** - Specify which sub-fields to display
   - Example: `text` or `text, amount, date`
   - Leave empty to display all sub-fields
   - Use comma-separated values for multiple fields
   - Order will be preserved as you enter them
   - Great for displaying only specific content from each repeater item

3. **Display Layout** - Choose how to display your repeater data:
   - **Table** - Best for tabular data (default)
   - **List** - Best for simple lists with multiple fields
   - **Cards** - Best for portfolio/showcase style layouts
   - **Icon + Text** - Best for lists with icons and text (new!)
   - **Custom** - Shows each item separately with all fields displayed

4. **Widget Heading** (only for Icon + Text layout) - Add a heading above the list
   - Example: "Our Services", "Key Features", "Expenditure Areas"
   - This is optional and controlled via Elementor text control

5. **List Item Icon** (only for Icon + Text layout) - Choose an icon for each item
   - Select from 1000+ Font Awesome icons
   - Default: Checkmark icon (fas fa-check)
   - The chosen icon will be displayed before each text item

6. **Columns** (for Cards layout) - Set the number of columns (1-6)

### Step 3: Style the Widget

The widget supports comprehensive styling options:

#### Container
- Border, Box Shadow, Padding
- Background color

#### Items
- Item background color
- Item border
- Item padding
- Text typography and color

#### Table-specific
- Header background color
- Header text color
- Border color

## Example: Using with expenditure_sector

### ACF Setup (Your Structure)

Your ACF repeater field structure:

```
Field Name: Expenditure Sector
Field Key: expenditure_sector

Sub-fields:
- text (Text field)          ← Main content field
- amount (Number field)      (optional)
- description (Textarea)     (optional)
- date (Date field)          (optional)
```

### Widget Configuration - Option 1: Show Only Text

To display only the 'text' sub-field:

1. **Repeater Field Key**: `expenditure_sector`
2. **Sub-field Keys**: `text` ← Single field
3. **Display Layout**: `Table` or `List`

**Result**: Only the text content will be displayed

### Widget Configuration - Option 2: Show Multiple Fields

To display text and amount:

1. **Repeater Field Key**: `expenditure_sector`
2. **Sub-field Keys**: `text, amount` ← Comma-separated
3. **Display Layout**: `Table`

**Result**: Shows both fields in columns

### Widget Configuration - Option 3: Show All Fields

To display all sub-fields:

1. **Repeater Field Key**: `expenditure_sector`
2. **Sub-field Keys**: (leave empty) ← Empty = show all
3. **Display Layout**: `Custom`

**Result**: Shows every field in the repeater row

### Styling for Your Repeater

- **Header Background**: Light gray (#f5f5f5)
- **Header Text Color**: Dark (#333)
- **Border Color**: Light gray (#ddd)
- **Item Padding**: 12px 15px
- **Text Color**: Dark (#333)

### Display Examples

**Table Layout - Text Only**:
```
| Text                    |
|-------------------------|
| My expenditure content  |
| Another item content    |
```

**Table Layout - Text + Amount**:
```
| Text                    | Amount |
|-------------------------|--------|
| My expenditure content  | 5000   |
| Another item content    | 8000   |
```

**List Layout - Text Only**:
```
- My expenditure content
- Another item content
- Third item content
```

**Cards Layout - Multiple Fields**:
```
┌──────────────────────┐  ┌──────────────────────┐
│ Text: Core content   │  │ Text: Cost details   │
│ Amount: 5000 |       │  │ Amount: 8000 |       │
└──────────────────────┘  └──────────────────────┘
```

## Layout Options Explained

### Table Layout

Best for: Data with multiple related fields

```
| Text | Amount | Description |
|------|--------|-------------|
| ...  | ...    | ...         |
```

Features:
- Automatic column generation from specified fields
- Hover effects
- Alternating row colors
- Fully responsive

### List Layout

Best for: Simple, readable lists

```
- Item 1
  - Text: Value
  - Amount: Value
  
- Item 2
  - Field Name: Value
  - Field Amount: Value
```

Features:
- Nested list structure
- Field labels and values
- Clean, readable format
- Responsive

### Cards Layout

Best for: Portfolio, gallery, or showcase style

```
┌─────────────────┐  ┌─────────────────┐
│ Field 1: Value  │  │ Field 1: Value  │
│ Field 2: Value  │  │ Field 2: Value  │
│ Field 3: Value  │  │ Field 3: Value  │
└─────────────────┘  └─────────────────┘
```

Features:
- Configurable columns (1-6)
- Shadow effects on hover
- Card elevation effect
- Grid layout

### Custom Layout

Best for: Complex data with detailed display

```
Item 1
  Field Name: Value
  Field Description: Value

Item 2
  Field Name: Value
  Field Description: Value
```

Features:
- Definition list structure
- Numbered items
- Complete field separation
- Maximum readability

### Icon + Text Layout

Best for: Lists with visual indicators and descriptions

```
Our Services
✓ Service 1 item text
✓ Service 2 item text  
✓ Service 3 item text
```

Features:
- Configurable heading at the top
- User-selectable icon (1000+ Font Awesome icons)
- Clean, minimal design
- Responsive list layout
- Hover effects

**Configuration Example:**
- Widget Heading: "Our Services"
- List Item Icon: Checkmark (fas fa-check) or your choice
- Text pulled from ACF 'text' sub-field

## Sub-field Keys Guide

The "Sub-field Keys" feature allows you to control exactly which fields from your repeater are displayed.

### When to Use Sub-field Keys

**Example Scenario: Your expenditure_sector Repeater**

```
expenditure_sector (Repeater)
├── text (Text field) 
├── amount (Number field)
├── date (Date field)
└── description (Textarea)
```

### Usage Examples

#### Example 1: Show Only Text Content

**Configuration:**
- Repeater Field Key: `expenditure_sector`
- Sub-field Keys: `text`
- Layout: `Table`

**Result:** Only the text content is displayed

#### Example 2: Show Text and Amount

**Configuration:**
- Repeater Field Key: `expenditure_sector`
- Sub-field Keys: `text, amount`
- Layout: `Table`

**Result:**
```
| Text             | Amount |
|------------------|--------|
| Content 1        | 5000   |
| Content 2        | 8000   |
```

#### Example 3: Custom Order

**Configuration:**
- Repeater Field Key: `expenditure_sector`
- Sub-field Keys: `amount, text, date`
- Layout: `Table`

**Result:** Fields appear in the order you specify (Amount first, then Text, then Date)

#### Example 4: Show All Fields

**Configuration:**
- Repeater Field Key: `expenditure_sector`
- Sub-field Keys: (empty/blank)
- Layout: `Custom`

**Result:** All sub-fields are displayed

### How to Find Your Sub-field Keys

1. Go to **WordPress Admin** → **Custom Fields** (ACF)
2. Find your repeater field (`expenditure_sector`)
3. Expand it to see sub-fields
4. The sub-field **keys** are in brackets, e.g.:
   - Text (key: `text`)
   - Amount (key: `amount`)
   - Date (key: `date`)

### Common Sub-field Key Formats

```
Single field:      text
Multiple fields:   text, amount
With spaces:       text, amount, date
Order matters:     date, text, amount (shows in this order)
```

## Styling Tips

### For Professional Tables

```
Header Background: Brand color
Header Text: White
Border Color: Light gray
Item Hover: Light background change
Row Padding: 12px 15px
```

### For Modern Cards

```
Border: 1px solid #e0e0e0
Border Radius: 8px
Box Shadow: 0 2px 8px rgba(0,0,0,0.1)
Padding: 20px
Columns: 2-3 (depends on screen)
```

### For Clean Lists

```
Border Bottom: 1px solid #e0e0e0
Padding: 15px
Background: White
Hover Background: #f9f9f9
```

## Advanced Features

### Field Name Formatting

Field names are automatically formatted:
- `revenue_sector` → "Revenue Sector"
- `total_amount` → "Total Amount"

Underscores are converted to spaces and words are capitalized.

### Responsive Design

All layouts are fully responsive:
- **Desktop**: Full-width display
- **Tablet**: Adjusted spacing and font sizes
- **Mobile**: Single column, touch-friendly

### Data Types Support

The widget automatically handles:
- **Text fields** - Display as-is
- **Number fields** - Display numeric values
- **Dates** - Display date values
- **Arrays** - Join with commas
- **Complex fields** - Intelligently formatted

### Icon + Text Layout Example

Perfect for displaying categorized lists with visual indicators.

**Configuration:**
- Repeater Field Key: `expenditure_sector`
- Sub-field Keys: `text` (show only the text field)
- Layout: `Icon + Text`
- Widget Heading: "Expenditure Areas"
- List Item Icon: Choose from Font Awesome (e.g., fas fa-chart-pie)

**Visual Result:**
```
Expenditure Areas
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 Education & Scholarships
📊 Healthcare & Wellness
📊 Community Development Projects
📊 Relief Programs
📊 Research Initiatives
```

**Use Cases:**
- Services or features lists with consistent icons
- Category listings
- Process steps
- Product features
- Benefits or advantages lists
- Budget categories (with appropriate icons)

## Troubleshooting

### "No data found for repeater field"

**Cause**: Field key doesn't match or post has no data

**Solution**: 
1. Double-check the field key in ACF
2. Ensure the current post has repeater data
3. Verify ACF is activated

### Widget shows empty

**Cause**: ACF not installed or post is not in single view

**Solution**:
1. Install and activate Advanced Custom Fields
2. Use widget only on single post templates
3. Ensure repeater has at least one row

### Styling not applied

**Cause**: Plugin CSS not loading

**Solution**:
1. Clear browser cache
2. Verify plugin is activated
3. Check browser console for errors

## Best Practices

1. **Use Descriptive Field Names** - Makes the table header clear
2. **Limit Repeater Rows** - 20-30 rows max for performance
3. **Test Responsively** - Check all layouts on mobile
4. **Use Appropriate Layout** - Match layout to data type
5. **Consistent Styling** - Use brand colors for headers

## Custom Hooks

Developers can filter the repeater data using WordPress hooks:

```php
// Filter repeater data before rendering
apply_filters('islami_dawa_repeater_data', $repeater_data, $field_key);

// Modify field labels
apply_filters('islami_dawa_repeater_field_label', $label, $field_key);
```

## Performance Considerations

- Works efficiently with up to 100-200 repeater items
- CSS is optimized and minified
- No external dependencies
- Responsive images supported

## Browser Support

- ✓ Chrome (latest)
- ✓ Firefox (latest)
- ✓ Safari (latest)
- ✓ Edge (latest)
- ✓ Mobile browsers

## Frequently Asked Questions

**Q: Can I use multiple repeater widgets on one page?**  
A: Yes, each widget can display different repeater fields.

**Q: What are "Sub-field Keys"?**  
A: These are the field names within your repeater. For example, if your repeater has fields named "text", "amount", and "date", you can specify which ones to display by entering them comma-separated: `text, amount`. Leave empty to show all.

**Q: How do I find my sub-field keys?**  
A: In ACF field settings, expand your repeater field. Each sub-field shows its key in brackets. For example: "Expenditure Text (text)" means the key is "text".

**Q: Can I change the order of sub-fields?**  
A: Yes! Enter them in your desired order in the Sub-field Keys setting. For example, `amount, text, date` will display Amount first, then Text, then Date.

**Q: Can I filter or search repeater data?**  
A: Currently no, but can be added via custom hooks.

**Q: Does it work with nested repeaters?**  
A: Currently supports flat repeater structures. Nested repeaters show as arrays.

**Q: Can I export the data?**  
A: Not through the widget, but data is standard post data.

**Q: Is there a row count limit?**  
A: No hard limit, but 200+ rows may need pagination.

## Support

For issues or feature requests, visit:
- GitHub: https://github.com/PairDevs/islami-dawa-tools
- Documentation: See DEVELOPER_GUIDE.md

---

**Version**: 1.0.0  
**Last Updated**: March 19, 2026  
**Author**: PairDevs
