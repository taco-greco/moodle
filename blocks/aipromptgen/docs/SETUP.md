# Setup Guide - AI Prompt Generator for Moodle

## System Requirements

### Moodle Requirements
- Moodle 4.3 or higher
- PHP 7.2 or higher
- Database: MySQL 5.7+ / MariaDB 10.2+ / PostgreSQL 9.6+
- Web server: Apache 2.4+ / Nginx
- Browser: Recent versions of Chrome, Firefox, Safari, or Edge

### Optional Features
- Moodle Competencies enabled
- Gradebook Outcomes enabled
- OpenAI API access (for ChatGPT integration)
- Ollama local installation (for local AI model integration)

## Installation

### Method 1: Via Moodle Plugin Directory

1. Log in as an administrator
2. Go to Site Administration → Plugins → Install plugins
3. Click "Install plugins from Moodle plugins directory"
4. Search for "AI Prompt Generator"
5. Click Install
6. Confirm the installation
7. Complete the installation process

### Method 2: Manual Installation

1. Download the plugin ZIP file
2. Extract the contents to `[moodleroot]/blocks/aipromptgen`
3. Log in as an administrator
4. Visit Site Administration → Notifications
5. Follow the installation prompts
6. Verify installation completion

### Verification Steps

1. Check Site Administration → Plugins → Plugins overview
2. Look for "block_aipromptgen" in the list
3. Verify the version number matches the installed version
4. Check for any warnings or errors

## Configuration

### Global Settings

1. Navigate to Site Administration → Plugins → Blocks → AI for Teachers

#### OpenAI Integration
```
- API Key: [Your OpenAI API key]
- Model: gpt-4o-mini (default)
- Temperature: 0.7 (default)
- Max Tokens: 2048 (default)
```

#### Ollama Integration
```
- Endpoint URL: http://localhost:11434 (default)
- Model Name: llama3 (example)
- Max Tokens (num_predict): 2048 (default)
- Request Timeout: 60s (default)
- Structured Output Schema: [optional JSON]
```

### Feature Enablement

1. Enable Competencies (Optional)
   ```
   Site Administration → Advanced features
   ☑ Enable competencies
   ```

2. Enable Outcomes (Optional)
   ```
   Site Administration → Advanced features
   ☑ Enable outcomes
   ```

### Permission Setup

1. Navigate to Site Administration → Users → Permissions → Define roles

2. Configure capabilities for roles:
   ```
   block/aipromptgen:manage
   - Teacher: Allow
   - Manager: Allow
   - Student: Not set
   ```

3. Review course-level permissions:
   ```
   Course administration → Users → Permissions
   Verify role assignments
   ```

## Integration Setup

### Competencies Integration

1. Course Level
   ```
   Course → More → Competencies
   - Add competencies
   - Link to activities
   ```

2. Activity Level
   ```
   Activity settings → Competencies
   - Select relevant competencies
   - Set grading method
   ```

### Outcomes Integration

1. Global Outcomes
   ```
   Site Administration → Grades → Outcomes
   - Create standard outcomes
   - Define scales
   ```

2. Course Outcomes
   ```
   Course → More → Outcomes
   - Add course outcomes
   - Import from global
   ```

## Block Deployment

### Adding to Courses

1. Turn editing on in course
2. Add block:
   ```
   Add a block → AI Prompt Generator
   Position block as needed
   ```

3. Block settings:
   ```
   Configure block instance
   Set visibility conditions
   ```

### Default Settings

1. Site defaults:
   ```
   Site Administration → Appearance → Default blocks
   Add to standard course format
   ```

2. Course format settings:
   ```
   Course format settings
   Block layout configuration
   ```

## Testing

### Functional Testing

1. Block Display
   - Verify block appears
   - Check permission controls
   - Test responsive layout

2. Form Testing
   - Submit test prompts
   - Verify all fields work
   - Test Browse modals

3. Integration Testing
   - Test competencies display
   - Check outcomes integration
   - Verify AI provider connections

### Security Testing

1. Permission Verification
   - Test different roles
   - Verify access controls
   - Check capability inheritance

2. Data Handling
   - Verify input sanitization
   - Check output encoding
   - Test file operations

## Maintenance

### Regular Tasks

1. Version Updates
   ```
   Check for updates monthly
   Test in staging environment
   Plan upgrade windows
   ```

2. Configuration Review
   ```
   Verify settings quarterly
   Update API keys as needed
   Check integration status
   ```

3. Backup Procedures
   ```
   Include in regular backups
   Document configurations
   Store API keys securely
   ```

### Troubleshooting

1. Common Issues
   ```
   Check error logs
   Verify permissions
   Test API connections
   ```

2. Support Resources
   ```
   Documentation reference
   Community forums
   Issue tracker
   ```

## Performance Optimization

### Caching

1. Configure Moodle caching:
   ```
   Site Administration → Plugins → Caching
   Enable application cache
   Configure store instances
   ```

2. Browser caching:
   ```
   Enable JavaScript caching
   Configure asset caching
   Set appropriate TTL
   ```

### Database Optimization

1. Index maintenance:
   ```
   Regular ANALYZE
   Monitor query performance
   Optimize table structure
   ```

2. Connection settings:
   ```
   Adjust pool size
   Set timeout values
   Monitor connection usage
   ```

## Security Recommendations

### API Security

1. OpenAI API:
   ```
   Use restricted API keys
   Rotate keys regularly
   Monitor usage limits
   ```

2. Ollama Security:
   ```
   Restrict network access
   Use secure connections
   Monitor resource usage
   ```

### Data Protection

1. User data:
   ```
   Implement retention policies
   Enable data encryption
   Regular security audits
   ```

2. Compliance:
   ```
   Follow GDPR guidelines
   Document data handling
   Maintain audit trails
   ```

## Support Information

### Getting Help

1. Documentation:
   ```
   Read installation guides
   Check troubleshooting docs
   Review FAQs
   ```

2. Community Support:
   ```
   Moodle forums
   Developer community
   User groups
   ```

3. Professional Support:
   ```
   Contact plugin maintainers
   Engage Moodle partners
   Submit bug reports
   ```

## Version History

### Current Version (1.3)
- Release Date: January 31, 2026
- Status: Stable
- Key Features:
   - OpenAI and Ollama integration
   - Real-time streaming for Ollama
   - Refined Response Modal (RAW, TEXT, HTML, HTML CODE)
   - Rich Text Copy support
   - Multi-language support (EN, SR, PT, SK)
   - Competencies & Outcomes integration

### Update Process
1. Backup current installation
2. Download new version
3. Follow upgrade instructions
4. Test functionality
5. Update documentation
