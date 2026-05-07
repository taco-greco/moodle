# Developer Documentation - AI Prompt Generator

Current release: 1.3 (2026-01-31)

## Architecture Overview

### Core Components

1. **Block Class (`block_aipromptgen`):**
   - Main entry point for the plugin
   - Handles block display and initialization
   - Manages capability checks and content generation
   - Integrates with Moodle competencies and outcomes

2. **Form System (`classes/form/prompt_form.php`):**
   - Implements Moodle's form API
   - Handles all input fields and validation
   - Manages form elements and their attributes
   - Processes form submissions

3. **JavaScript Modules (`amd/src/`):**
   - `actions.js`: Handles prompt copy and file download functionality.
   - `age.js`: Age/grade range picker functionality.
   - `pickers.js`: Modal pickers for berbagai (various) form fields.
   - `stream.js`: Implements AJAX/SSE streaming for real-time AI responses.
   - `markdown.js`: Client-side Markdown rendering and text normalization.
   - `ui.js`: Main UI orchestrator, handles modal states and view switching.

### Integration Points

1. **Competencies Integration:**
```php
// Example of competencies integration
if (class_exists('\\core_competency\\api') && \core_competency\api::is_enabled()) {
    $coursecompetencies = \core_competency\api::list_course_competencies($courseid);
    // Process competencies...
}
```

2. **Outcomes Integration:**
```php
// Example of outcomes integration
if (!empty($CFG->enableoutcomes)) {
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->libdir . '/grade/grade_outcome.php');
    // Process outcomes...
}
```

## Development Guidelines

### Adding New Features

1. **Form Fields:**
   - Add field definition in `prompt_form.php`
   - Create corresponding language strings
   - Update JavaScript handlers if needed
   - Add validation rules if required

2. **Modal Pickers:**
   - Create new picker template
   - Add JavaScript handler in `pickers.js`
   - Register picker initialization in `ui.js`

### JavaScript Development

1. **Module Structure:**
```javascript
define(['jquery'], function($) {
    // Module implementation
    return {
        // Public methods
    };
});
```

2. **Building JavaScript:**
```bash
# From Moodle root directory
php admin/cli/minify.php --js
```

### Adding New Languages

1. Create new language file in `lang/[langcode]/block_aipromptgen.php`
2. Include all required strings
3. Follow Moodle's language pack guidelines

## API Documentation

### Core Methods

1. **Block Initialization:**
```php
public function init()
public function applicable_formats()
public function get_content()
```

2. **Form Methods:**
```php
protected function definition()
public function validation($data, $files)
```

3. **JavaScript APIs:**
```javascript
// Picker API
initializePicker(selector, options)
updatePickerValue(value)

// Response Modal API (in ui.js)
    setView(viewName) // raw, text, html, rich
    copyRichText(element) // copies element contents as rich text
    showStatus(message) // displays temporary toast-like notification

// Stream API (in stream.js)
    startStream(formCallback, promptEl, sendtoEl, responseEl, callback)
```

## Testing Guidelines

1. **PHPUnit Tests:**
   - Create test classes in `tests/`
   - Follow Moodle's testing conventions
   - Test all major functionality

2. **JavaScript Tests:**
   - Use Mocha for unit testing
   - Test all UI interactions
   - Verify form handling

3. **Manual Testing Checklist:**
   - Form submission
   - Modal pickers
   - Competencies integration
   - Outcomes integration
   - Language handling
   - Error scenarios

## Security Considerations

1. **Input Validation:**
   - All form inputs are type-checked
   - Use appropriate PARAM types
   - Validate before processing

2. **Capability Checks:**
   - Verify user permissions
   - Check context access
   - Follow principle of least privilege

3. **Data Processing:**
   - Clean all output
   - Use prepared statements
   - Follow Moodle security guidelines

## Extending the Plugin

### Adding New Features

1. Create new PHP classes in `classes/`
2. Add required language strings
3. Update block class if needed
4. Add settings if required

### Custom Integrations

1. **Adding AI Providers:**
```php
// Provider interface
interface ai_provider {
    public function generate_response($prompt);
    public function validate_settings();
}
```

2. **Custom Form Fields:**
```php
// Example custom form element
$mform->addElement('customtype', 'name', get_string('label', 'block_aipromptgen'), [
    'custom' => 'attributes',
]);
```

## Troubleshooting Guide

### Common Issues

1. **JavaScript Loading:**
   - Check AMD module dependencies
   - Verify file paths
   - Check browser console

2. **Form Submission:**
   - Validate form data
   - Check AJAX responses
   - Verify session handling

3. **Integration Issues:**
   - Check competencies setup
   - Verify outcomes configuration
   - Validate permissions

### Debugging

1. **Enable Debug Mode:**
```php
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;
```

2. **JavaScript Debugging:**
```javascript
// Add debug logging
console.log('Debug:', data);
```

## Performance Optimization

1. **JavaScript:**
   - Minimize DOM operations
   - Use event delegation
   - Cache jQuery selectors

2. **PHP:**
   - Optimize database queries
   - Use caching where appropriate
   - Minimize API calls

## Version Control

### Git Workflow

1. **Branch Naming:**
   - feature/[name]
   - fix/[name]
   - release/[version]

2. **Commit Messages:**
   - Clear and descriptive
   - Reference issue numbers
   - Follow conventional commits

3. **Release Process:**
   - Update version.php
   - Update changelog
   - Tag release

## Support and Resources

1. **Documentation:**
   - Moodle Docs
   - Plugin Documentation
   - API References

2. **Community:**
   - Moodle Forums
   - Developer Community
   - Issue Tracker

3. **Tools:**
   - Development Tools
   - Testing Framework
   - Build Scripts
